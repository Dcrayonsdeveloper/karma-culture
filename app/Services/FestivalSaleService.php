<?php

namespace App\Services;

use App\Models\FestivalSale;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Writes a festival sale into the catalogue, and takes it back out again.
 *
 * The whole feature rests on one decision: the discounted figure goes into
 * products.price (and product_variants.price) rather than being resolved when
 * a page renders. That is what makes it show up in the places that read price
 * in SQL rather than in PHP - the price-range filter, the "On Sale" facet, the
 * "Biggest Discount" sort, the home page's price bands - and what makes
 * Cart::repriceItems() charge it without a line of new code.
 *
 * The price of that decision is that it is destructive, so every write here is
 * paired with a snapshot of what it replaced, and every restore checks that
 * what it is about to overwrite is still the figure this sale put there. An
 * admin who edits a product's price during a sale keeps their edit; the sale
 * ending does not silently undo their work.
 *
 * Nothing in here is scheduled. A sale starts when an admin switches it on and
 * stops when they switch it off, and both are synchronous - which is only
 * viable because the writes are batched. Row by row, through the models, a sale
 * over the full 1,231-product catalogue took over ten minutes and would have
 * been a gateway timeout on the Save button; the catalogue is 8,000 variants
 * deep and Product::save() carries slug machinery that has no business running
 * because a price changed. So the run decides everything in memory and leaves
 * through {@see flush()} in a few dozen statements instead of ~27,000.
 */
class FestivalSaleService
{
    /** How many rows go into one CASE statement. */
    private const CHUNK = 200;

    /** @var array<int, array{price: float, mrp: float|null}> product id => new figures */
    private array $productPrices = [];

    /** @var array<int, array{price: float|null, mrp: float|null}> variant id => new figures */
    private array $variantPrices = [];

    /** @var array<int, array<string, mixed>> product id => festival_sale_products row */
    private array $pivotRows = [];

    /** @var array<int, array<string, mixed>> variant id => snapshot row */
    private array $snapshotRows = [];

    /** @var array<int, int> variant ids whose snapshot has been consumed */
    private array $snapshotDeletes = [];

    /**
     * Point a sale at exactly this set of products, then make the catalogue
     * agree with the sale's current on/off state.
     *
     * @param  array<int, int>  $productIds
     * @return array{applied: int, reverted: int, skipped: array<int, string>}
     */
    public function sync(FestivalSale $sale, array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        $result = DB::transaction(function () use ($sale, $productIds) {
            $this->reset();
            $sale->load('products.variants');

            // Products dropped from the list give their prices back first,
            // while their snapshot rows are still there to give back.
            $dropped = $sale->products->reject(
                fn (Product $p) => in_array($p->id, $productIds, true)
            );

            $reverted = 0;

            foreach ($dropped as $product) {
                $reverted += $this->revertProduct($sale, $product) ? 1 : 0;
            }

            // Flushed before the detach, or the pivot resets queued above would
            // be written to rows that no longer exist.
            $this->flush($sale);

            if ($dropped->isNotEmpty()) {
                $sale->products()->detach($dropped->pluck('id')->all());
            }

            // Newly ticked products join with no snapshot: they are members,
            // not yet discounted. reconcile() below decides whether that
            // changes, based on whether the sale is live.
            $existing = $sale->products()->pluck('products.id')->all();
            $new = array_values(array_diff($productIds, $existing));

            if ($new !== []) {
                $now = now();
                DB::table('festival_sale_products')->insert(array_map(fn ($id) => [
                    'festival_sale_id' => $sale->id,
                    'product_id' => $id,
                    'original_price' => null,
                    'original_mrp' => null,
                    'sale_price' => null,
                    'applied_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $new));
            }

            $outcome = $this->reconcileRows($sale);
            $outcome['reverted'] += $reverted;

            return $outcome;
        });

        $this->bumpCaches();

        return $result;
    }

    /**
     * Make the catalogue match the sale's is_active flag.
     *
     * Also the repair path: a member whose stored sale_price no longer matches
     * what the current percentage would produce is put back and re-discounted,
     * which is how an admin changing 20% to 30% mid-sale reaches the shop.
     *
     * @return array{applied: int, reverted: int, skipped: array<int, string>}
     */
    public function reconcile(FestivalSale $sale, bool $bumpCaches = true): array
    {
        // Nested inside sync()'s transaction when it is called from there,
        // which Laravel handles as a savepoint. On its own - the Start/End
        // switch - it is the outermost one, and it needs to be: a run that died
        // halfway would otherwise leave half a catalogue discounted.
        $result = DB::transaction(function () use ($sale) {
            $this->reset();

            return $this->reconcileRows($sale);
        });

        if ($bumpCaches) {
            $this->bumpCaches();
        }

        return $result;
    }

    /** Put every product this sale touched back, and forget it did. */
    public function revertAll(FestivalSale $sale): int
    {
        $reverted = DB::transaction(function () use ($sale) {
            $this->reset();
            $sale->load('products.variants');
            $count = 0;

            foreach ($sale->products as $product) {
                $count += $this->revertProduct($sale, $product) ? 1 : 0;
            }

            $this->flush($sale);

            return $count;
        });

        $this->bumpCaches();

        return $reverted;
    }

    /**
     * @return array{applied: int, reverted: int, skipped: array<int, string>}
     */
    private function reconcileRows(FestivalSale $sale): array
    {
        $applied = 0;
        $reverted = 0;
        $skipped = [];

        $sale->load('products.variants');

        // Asked once for the whole run rather than once a product. A sale over
        // the full catalogue is 1,231 products and this was 1,231 identical
        // three-table joins.
        $claimed = $this->claimedElsewhere($sale);

        foreach ($sale->products as $product) {
            $pivot = $product->pivot;
            $isApplied = $pivot->applied_at !== null;

            if (! $sale->is_active) {
                if ($isApplied && $this->revertProduct($sale, $product)) {
                    $reverted++;
                }

                continue;
            }

            if ($isApplied) {
                // Still priced the way this sale priced it at the current
                // percentage? Then there is nothing to do.
                $expected = $sale->priceFor((float) $pivot->original_price);

                if ($this->same($expected, (float) $pivot->sale_price)) {
                    continue;
                }

                // revertProduct restores the model IN MEMORY as well as queuing
                // the write, so the apply below computes from the original
                // price. Re-reading from the database here instead would find
                // the discounted figure - nothing has been written yet - and
                // take the new percentage off it, compounding the sale.
                $this->revertProduct($sale, $product);
            }

            $outcome = $this->applyProduct($sale, $product, $claimed);

            if ($outcome === true) {
                $applied++;
            } elseif (is_string($outcome)) {
                $skipped[$product->id] = $outcome;
            }
        }

        $this->flush($sale);

        return ['applied' => $applied, 'reverted' => $reverted, 'skipped' => $skipped];
    }

    /**
     * Products another LIVE sale has already discounted, by id.
     *
     * A product cannot be in two live sales at once: the second would snapshot
     * the first one's discounted figure as "the original", and the real price
     * would be lost the moment either of them ended.
     *
     * @return array<int, string>  product id => the sale that holds it
     */
    private function claimedElsewhere(FestivalSale $sale): array
    {
        return DB::table('festival_sale_products')
            ->join('festival_sales', 'festival_sales.id', '=', 'festival_sale_products.festival_sale_id')
            ->where('festival_sale_products.festival_sale_id', '!=', $sale->id)
            ->whereNotNull('festival_sale_products.applied_at')
            ->where('festival_sales.is_active', true)
            ->pluck('festival_sales.name', 'festival_sale_products.product_id')
            ->all();
    }

    /**
     * Discount one product and each of its sizes.
     *
     * @param  array<int, string>  $claimed  product id => sale already holding it
     * @return true|string  true when priced, or a reason it was left alone
     */
    private function applyProduct(FestivalSale $sale, Product $product, array $claimed = []): bool|string
    {
        $base = (float) $product->price;

        if ($base <= 0) {
            return 'has no price to discount';
        }

        if (isset($claimed[$product->id])) {
            return 'already discounted by '.$claimed[$product->id];
        }

        $salePrice = $sale->priceFor($base);

        if ($salePrice >= $base) {
            return 'the discount would not lower its price';
        }

        $originalMrp = $product->mrp === null ? null : (float) $product->mrp;

        // The strike-through price the whole storefront derives its "% off"
        // badge from is mrp. A product priced AT or above its mrp would go on
        // sale and show no saving, so the pre-sale price stands in as the mrp
        // for the duration and is put back with everything else.
        $newMrp = ($originalMrp === null || $originalMrp < $base) ? $base : $originalMrp;

        $product->price = $salePrice;
        $product->mrp = $newMrp;
        $this->productPrices[$product->id] = ['price' => $salePrice, 'mrp' => $newMrp];

        $this->pivotRows[$product->id] = [
            'original_price' => $base,
            'original_mrp' => $originalMrp,
            'sale_price' => $salePrice,
            'applied_at' => now(),
        ];

        $this->applyVariants($sale, $product);

        return true;
    }

    /**
     * The sizes, which carry their own prices and their own snapshots.
     *
     * A variant with a null price inherits the product's, which we have just
     * changed, so there is nothing to write for it - and writing one would turn
     * an inheriting size into an independently-priced one for good.
     */
    private function applyVariants(FestivalSale $sale, Product $product): void
    {
        foreach ($product->variants as $variant) {
            if ($variant->price === null) {
                continue;
            }

            $base = (float) $variant->price;

            if ($base <= 0) {
                continue;
            }

            $salePrice = $sale->priceFor($base);

            if ($salePrice >= $base) {
                continue;
            }

            $originalMrp = $variant->mrp === null ? null : (float) $variant->mrp;
            $newMrp = ($originalMrp === null || $originalMrp < $base) ? $base : $originalMrp;

            $variant->price = $salePrice;
            $variant->mrp = $newMrp;
            $this->variantPrices[$variant->id] = ['price' => $salePrice, 'mrp' => $newMrp];

            $this->snapshotRows[$variant->id] = [
                'original_price' => $base,
                'original_mrp' => $originalMrp,
                'sale_price' => $salePrice,
            ];
        }
    }

    /**
     * Give a product its pre-sale price back.
     *
     * Only where the shop is still charging what this sale set. If an admin has
     * repriced the product since, that newer figure is theirs and the sale
     * ending is not a reason to overwrite it - the snapshot is simply dropped.
     */
    private function revertProduct(FestivalSale $sale, Product $product): bool
    {
        $pivot = $product->pivot;

        if (! $pivot || $pivot->applied_at === null) {
            return false;
        }

        if ($this->same((float) $product->price, (float) $pivot->sale_price)) {
            $originalPrice = (float) $pivot->original_price;
            $mrp = $product->mrp === null ? null : (float) $product->mrp;

            // The mrp only moves back if this sale is what raised it - and only
            // to a real figure. products.mrp is NOT NULL (unlike the variants'),
            // so there is no "put the null back" case here and writing one would
            // be an integrity error rather than a restore.
            if ($pivot->original_mrp !== null && $this->same((float) $mrp, $originalPrice)) {
                $mrp = (float) $pivot->original_mrp;
            }

            $product->price = $originalPrice;
            $product->mrp = $mrp;
            $this->productPrices[$product->id] = ['price' => $originalPrice, 'mrp' => $mrp];
        }

        $this->revertVariants($sale, $product);

        $this->pivotRows[$product->id] = [
            'original_price' => null,
            'original_mrp' => null,
            'sale_price' => null,
            'applied_at' => null,
        ];

        // The in-memory pivot has to agree, or reconcileRows would still see
        // this product as applied when it re-reads it in the same pass.
        $pivot->applied_at = null;

        return true;
    }

    private function revertVariants(FestivalSale $sale, Product $product): void
    {
        $variantIds = $product->variants->pluck('id');

        if ($variantIds->isEmpty()) {
            return;
        }

        $snapshots = $sale->variantPrices()
            ->whereIn('product_variant_id', $variantIds)
            ->get()
            ->keyBy('product_variant_id');

        foreach ($product->variants as $variant) {
            $snapshot = $snapshots->get($variant->id);

            if (! $snapshot) {
                continue;
            }

            if ($this->same((float) $variant->price, (float) $snapshot->sale_price)) {
                $price = $snapshot->original_price === null ? null : (float) $snapshot->original_price;
                $mrp = $variant->mrp === null ? null : (float) $variant->mrp;

                if ($snapshot->original_mrp === null) {
                    $mrp = null;
                } elseif ($price !== null && $this->same((float) $mrp, $price)) {
                    $mrp = (float) $snapshot->original_mrp;
                }

                $variant->price = $price;
                $variant->mrp = $mrp;
                $this->variantPrices[$variant->id] = ['price' => $price, 'mrp' => $mrp];
            }

            $this->snapshotDeletes[] = $variant->id;
            unset($this->snapshotRows[$variant->id]);
        }
    }

    /** Everything the run decided, in a few dozen statements. */
    private function flush(FestivalSale $sale): void
    {
        $this->bulkPrices('products', $this->productPrices);
        $this->bulkPrices('product_variants', $this->variantPrices);

        if ($this->snapshotDeletes !== []) {
            foreach (array_chunk(array_unique($this->snapshotDeletes), self::CHUNK) as $chunk) {
                DB::table('festival_sale_variant_prices')
                    ->where('festival_sale_id', $sale->id)
                    ->whereIn('product_variant_id', $chunk)
                    ->delete();
            }
        }

        $now = now();

        if ($this->snapshotRows !== []) {
            $rows = [];

            foreach ($this->snapshotRows as $variantId => $row) {
                $rows[] = $row + [
                    'festival_sale_id' => $sale->id,
                    'product_variant_id' => $variantId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                DB::table('festival_sale_variant_prices')->upsert(
                    $chunk,
                    ['festival_sale_id', 'product_variant_id'],
                    ['original_price', 'original_mrp', 'sale_price', 'updated_at'],
                );
            }
        }

        if ($this->pivotRows !== []) {
            $rows = [];

            foreach ($this->pivotRows as $productId => $row) {
                $rows[] = $row + [
                    'festival_sale_id' => $sale->id,
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                DB::table('festival_sale_products')->upsert(
                    $chunk,
                    ['festival_sale_id', 'product_id'],
                    ['original_price', 'original_mrp', 'sale_price', 'applied_at', 'updated_at'],
                );
            }
        }

        $this->reset();
    }

    /**
     * One UPDATE per chunk, with a CASE arm per row.
     *
     * The table name is a constant at every call site, never input; the ids and
     * the figures are bound.
     *
     * @param  array<int, array{price: float|null, mrp: float|null}>  $writes
     */
    private function bulkPrices(string $table, array $writes): void
    {
        if ($writes === []) {
            return;
        }

        foreach (array_chunk($writes, self::CHUNK, true) as $chunk) {
            $ids = array_keys($chunk);
            $priceArms = [];
            $mrpArms = [];
            $bindings = [];

            foreach ($chunk as $id => $vals) {
                $priceArms[] = 'WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $vals['price'];
            }

            foreach ($chunk as $id => $vals) {
                $mrpArms[] = 'WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $vals['mrp'];
            }

            $sql = "update `{$table}` set "
                .'`price` = case `id` '.implode(' ', $priceArms).' end, '
                .'`mrp` = case `id` '.implode(' ', $mrpArms).' end '
                .'where `id` in ('.implode(', ', array_fill(0, count($ids), '?')).')';

            DB::update($sql, array_merge($bindings, $ids));
        }
    }

    private function reset(): void
    {
        $this->productPrices = [];
        $this->variantPrices = [];
        $this->pivotRows = [];
        $this->snapshotRows = [];
        $this->snapshotDeletes = [];
    }

    /**
     * One bump for a whole run rather than one per product.
     *
     * The derived filter rails are cached for six hours behind kk_filter_ver,
     * and a sale that does not retire them goes on advertising yesterday's
     * price bands on the home page.
     */
    private function bumpCaches(): void
    {
        ProductVariant::bumpFilterCache();
        FestivalSale::forgetLive();
    }

    /** Money compared the way money should be: to the paisa, not by identity. */
    private function same(float $a, float $b): bool
    {
        return abs($a - $b) < 0.005;
    }
}
