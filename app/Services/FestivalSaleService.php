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
 * Nothing in here is scheduled. There is no cron and no queue worker on the
 * production host, so a sale starts when an admin switches it on and stops
 * when they switch it off, and both are synchronous.
 */
class FestivalSaleService
{
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
            $sale->load('products.variants');

            // Products dropped from the list give their prices back first,
            // while their snapshot rows are still there to give back.
            $dropped = $sale->products->reject(
                fn (Product $p) => in_array($p->id, $productIds, true)
            );

            $reverted = 0;

            foreach ($dropped as $product) {
                $reverted += $this->revertProduct($sale, $product) ? 1 : 0;
                $sale->products()->detach($product->id);
            }

            // Newly ticked products join with no snapshot: they are members,
            // not yet discounted. reconcile() below decides whether that
            // changes, based on whether the sale is live.
            $existing = $sale->products()->pluck('products.id')->all();

            foreach (array_diff($productIds, $existing) as $id) {
                $sale->products()->attach($id, [
                    'original_price' => null,
                    'original_mrp' => null,
                    'sale_price' => null,
                    'applied_at' => null,
                ]);
            }

            $outcome = $this->reconcile($sale, bumpCaches: false);
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
        // switch - it is the outermost one, and it needs to be: a run that dies
        // halfway would otherwise leave half a catalogue discounted.
        $result = DB::transaction(fn () => $this->reconcileRows($sale));

        if ($bumpCaches) {
            $this->bumpCaches();
        }

        return $result;
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

                $this->revertProduct($sale, $product);
                $product->refresh();
                $product->load('variants');
            }

            $outcome = $this->applyProduct($sale, $product, $claimed);

            if ($outcome === true) {
                $applied++;
            } elseif (is_string($outcome)) {
                $skipped[$product->id] = $outcome;
            }
        }

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

    /** Put every product this sale touched back, and forget it did. */
    public function revertAll(FestivalSale $sale): int
    {
        $reverted = DB::transaction(function () use ($sale) {
            $sale->load('products.variants');
            $count = 0;

            foreach ($sale->products as $product) {
                $count += $this->revertProduct($sale, $product) ? 1 : 0;
            }

            return $count;
        });

        $this->bumpCaches();

        return $reverted;
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

        $originalMrp = $product->mrp;

        // The strike-through price the whole storefront derives its "% off"
        // badge from is mrp. A product priced at its mrp - or with none at all -
        // would go on sale and show no saving, so the pre-sale price becomes
        // the mrp for the duration and is put back with everything else.
        $mrpNeedsRaising = $originalMrp === null || (float) $originalMrp < $base;

        $product->price = $salePrice;

        if ($mrpNeedsRaising) {
            $product->mrp = $base;
        }

        // Quietly: Product::saved() bumps the filter cache and re-syncs the
        // category pivot on every save. Neither is wanted once per product in a
        // loop over a few hundred of them; the cache is bumped once by the
        // caller instead.
        $product->saveQuietly();

        $sale->products()->updateExistingPivot($product->id, [
            'original_price' => $base,
            'original_mrp' => $originalMrp,
            'sale_price' => $salePrice,
            'applied_at' => now(),
        ]);

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

            $originalMrp = $variant->mrp;
            $variant->price = $salePrice;

            if ($originalMrp === null || (float) $originalMrp < $base) {
                $variant->mrp = $base;
            }

            // Quietly for the same reason as the product: ProductVariant::saved
            // bumps the filter cache, and this loop would bump it once a size.
            $variant->saveQuietly();

            $sale->variantPrices()->updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'original_price' => $base,
                    'original_mrp' => $originalMrp,
                    'sale_price' => $salePrice,
                ]
            );
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
            $product->price = (float) $pivot->original_price;

            // The mrp only moves back if this sale is what raised it - and only
            // to a real figure. products.mrp is NOT NULL (unlike the variants'),
            // so there is no "put the null back" case here and writing one would
            // be an integrity error rather than a restore.
            if ($pivot->original_mrp !== null
                && $this->same((float) $product->mrp, (float) $pivot->original_price)) {
                $product->mrp = (float) $pivot->original_mrp;
            }

            $product->saveQuietly();
        }

        $this->revertVariants($sale, $product);

        $sale->products()->updateExistingPivot($product->id, [
            'original_price' => null,
            'original_mrp' => null,
            'sale_price' => null,
            'applied_at' => null,
        ]);

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
                $variant->price = $snapshot->original_price === null
                    ? null
                    : (float) $snapshot->original_price;

                if ($snapshot->original_mrp === null) {
                    $variant->mrp = null;
                } elseif ($this->same((float) $variant->mrp, (float) $snapshot->original_price)) {
                    $variant->mrp = (float) $snapshot->original_mrp;
                }

                $variant->saveQuietly();
            }

            $snapshot->delete();
        }
    }

    /** Money compared the way money should be: to the paisa, not by identity. */
    private function same(float $a, float $b): bool
    {
        return abs($a - $b) < 0.005;
    }
}
