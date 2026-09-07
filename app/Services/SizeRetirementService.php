<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Support\ShopFilterCatalogue;
use Illuminate\Support\Facades\DB;

/**
 * Take one size off the catalogue.
 *
 * A product's sizes are rows in `product_variants` - there is no size column
 * anywhere else, and nothing copies a size onto the product itself. So the
 * Sizes library under Admin -> Products is a library of LABELS, and removing a
 * label from it used to leave every product still carrying that size: the row
 * kept its stock, the size kept selling on the product page, and the shop's
 * size rail kept offering it. Deleting "MD" removed the word and nothing else.
 *
 * This is the other half of that delete - it removes the size FROM THE
 * PRODUCTS, and never removes a product.
 *
 * WHICH ROWS COUNT AS THAT SIZE
 * The one definition that matters is the one the storefront rail is built
 * from, because that rail is the thing an admin is looking at when they say a
 * size is still there:
 *
 *     ShopFilterCatalogue::normaliseKey(ProductVariant::sizeLabel($name))
 *
 * the identical pairing used at {@see ShopFilterCatalogue::deriveSizes()} and
 * by {@see \App\Console\Commands\NormaliseProductOptionsToLibrary}. It is
 * matched in PHP rather than in SQL, and deliberately NOT through
 * {@see ProductVariant::scopeWhereSizeIn()}: that scope's third pattern is
 * `<size>-%`, anchored only on the left, so a size named "6" would match - and
 * delete - the "6-7Y" children's rows, along with their stock and SKUs.
 * sizeLabel() strips only a numeric suffix, and it is sizeLabel() that decides
 * what the shopper sees.
 *
 * The scan does not filter on is_active, for either the variant or its
 * product. The rail's own active bounds are about display; a hidden row still
 * holds the size, and reactivating the product would bring it back.
 *
 * WHAT HAPPENS TO EVERYTHING POINTING AT THE ROW
 * Only one real foreign key points at product_variants -
 * store_transfer_items.variant_id, which RESTRICTs - so those rows are left
 * standing and reported rather than deleted, exactly as the normalise command
 * does. Beyond that the rule is: clear the pointer wherever something could
 * still read it as live, delete the row only where the row itself becomes
 * nonsense, and never touch a financial record.
 *
 *   cart_items      - DELETED. The line cannot be bought any more, and leaving
 *                     it locks the shopper out of the whole basket: the cart
 *                     and checkout controllers read $item->variant->stock_quantity
 *                     on a null relation. Deleted through the model, so
 *                     CartItem::deleted redraws the cart's totals without it.
 *   order_items     - NOT TOUCHED AT ALL. Every invoice, packing slip, email
 *                     and returns screen reads the snapshot columns
 *                     (variant_name, size, colour, sku), so an order still
 *                     says what was bought. The dead pointer is left dangling
 *                     deliberately, because clearing it fails OPEN in two
 *                     places that read the raw column rather than the
 *                     relation: PayU's failed-payment restock
 *                     (PayUController::restoreStock) would stop being a no-op
 *                     and credit the PRODUCT's own stock with units that were
 *                     taken out of the catalogue, and Reorder would turn an
 *                     unbuyable line into a buyable one and put the retired
 *                     size back on sale. Reorder now skips a line whose size
 *                     has gone; see Account\OrderController::reorder().
 *   product_images  - pointer cleared: nothing reads it, it has no foreign key
 *   barcodes          and neither table is populated today, so there is no
 *                     fail-open path and no history to keep.
 *   inventory_stocks- cleared for us: ProductVariant's own deleting hook
 *                     ({@see \App\Models\Concerns\TracksWarehouseStock}) writes
 *                     an out-movement and clears the shelves. This is why the
 *                     rows are deleted one model at a time and never with a
 *                     query-builder mass delete, which fires no events at all.
 *   inventory_movements,
 *   pos_sale_items,
 *   pos_return_items- LEFT ALONE. A stock ledger entry and a GST invoice line
 *                     are records of something that happened, and they carry
 *                     their own product/price snapshots.
 */
class SizeRetirementService
{
    /** Ids are handled in batches this size, so one `whereIn` never grows unbounded. */
    private const BATCH = 500;

    /**
     * Every size the catalogue carries, counted.
     *
     * One pass, keyed the same way the rail keys its chips, so the admin can
     * be told what a delete is about to cost before they confirm it.
     *
     * @return array<string, array{variants: int, products: int}>
     */
    public function counts(): array
    {
        $counts = [];
        $seen = [];

        $this->eachVariant(function (object $row) use (&$counts, &$seen) {
            $key = $this->keyOf($row->name);

            if ($key === '') {
                return;
            }

            $counts[$key]['variants'] = ($counts[$key]['variants'] ?? 0) + 1;
            $counts[$key]['products'] = ($counts[$key]['products'] ?? 0)
                + (isset($seen[$key][$row->product_id]) ? 0 : 1);

            $seen[$key][$row->product_id] = true;
        });

        return $counts;
    }

    /**
     * What retiring this size would touch, without touching it.
     *
     * @return array{size: string, variants: int, products: int, carts: int, orders: int, locked: array<int, string>}
     */
    public function preview(string $size): array
    {
        return $this->plan($size)['report'];
    }

    /**
     * Take the size off every product that carries it.
     *
     * @return array{size: string, variants: int, products: int, carts: int, orders: int, locked: array<int, string>}
     */
    public function retire(string $size): array
    {
        ['report' => $report, 'ids' => $ids] = $this->plan($size);

        if ($ids !== []) {
            DB::transaction(function () use ($ids) {
                foreach (array_chunk($ids, self::BATCH) as $batch) {
                    // Through the model: CartItem::deleted is what redraws the
                    // cart's subtotal and total without the line.
                    CartItem::query()->whereIn('variant_id', $batch)->get()->each->delete();

                    DB::table('product_images')->whereIn('variant_id', $batch)->update(['variant_id' => null]);
                    DB::table('barcodes')->whereIn('variant_id', $batch)->update(['variant_id' => null]);

                    // One at a time, so the deleting hook clears each row's
                    // warehouse shelves. That also bumps the filter cache once
                    // per row, which is wasted work the bump below makes
                    // harmless - correctness over churn, as in the normalise
                    // command.
                    ProductVariant::query()->whereIn('id', $batch)->get()->each->delete();
                }
            });
        }

        // Bumped unconditionally: a size with no rows left the cached rail
        // untouched, and the rail also reads the library itself, so retiring a
        // label nothing carries still has to retire the cached answer.
        ProductVariant::bumpFilterCache();

        return $report;
    }

    /**
     * Work out what has to happen, in one pass over the catalogue.
     *
     * @return array{ids: array<int, int>, report: array{size: string, variants: int, products: int, carts: int, orders: int, locked: array<int, string>}}
     */
    private function plan(string $size): array
    {
        $matches = $this->matches($size);
        $locked = $this->lockedByTransfer(array_keys($matches));
        $ids = array_values(array_diff(array_keys($matches), array_keys($locked)));

        return [
            'ids' => $ids,
            'report' => [
                'size' => $size,
                'variants' => count($ids),
                'products' => count(array_unique(array_intersect_key($matches, array_flip($ids)))),
                'carts' => $this->countIn('cart_items', $ids),
                'orders' => $this->countIn('order_items', $ids),
                'locked' => array_values($locked),
            ],
        ];
    }

    /**
     * The variant rows this size covers.
     *
     * @return array<int, int>  variant id => product id
     */
    private function matches(string $size): array
    {
        $key = ShopFilterCatalogue::normaliseKey($size);
        $matches = [];

        if ($key === '') {
            return $matches;
        }

        $this->eachVariant(function (object $row) use ($key, &$matches) {
            if ($this->keyOf($row->name) === $key) {
                $matches[(int) $row->id] = (int) $row->product_id;
            }
        });

        return $matches;
    }

    /**
     * Rows a stock transfer refers to, which the foreign key will not let go.
     *
     * @param  array<int, int>  $ids
     * @return array<int, string>  variant id => label for the summary
     */
    private function lockedByTransfer(array $ids): array
    {
        $locked = [];

        foreach (array_chunk($ids, self::BATCH) as $batch) {
            $held = DB::table('store_transfer_items')
                ->whereIn('variant_id', $batch)
                ->distinct()
                ->pluck('variant_id');

            foreach (ProductVariant::query()->whereIn('id', $held)->get(['id', 'product_id', 'name']) as $variant) {
                $locked[(int) $variant->id] = '#'.$variant->product_id.'  '.$variant->name;
            }
        }

        return $locked;
    }

    /** @param  array<int, int>  $ids */
    private function countIn(string $table, array $ids): int
    {
        $count = 0;

        foreach (array_chunk($ids, self::BATCH) as $batch) {
            $count += DB::table($table)->whereIn('variant_id', $batch)->count();
        }

        return $count;
    }

    /**
     * Walk every size row in the catalogue, cheaply.
     *
     * product_variants.name is not indexed and the size is buried inside it,
     * so there is no query that can do this - and no query would be faster,
     * because the SQL equivalent is a leading-wildcard LIKE. Three columns of
     * a few thousand rows is a small read.
     */
    private function eachVariant(callable $callback): void
    {
        ProductVariant::query()
            ->select(['id', 'product_id', 'name'])
            ->orderBy('id')
            ->chunk(1000, function ($rows) use ($callback) {
                foreach ($rows as $row) {
                    $callback($row);
                }
            });
    }

    /** The identity the shop rail groups a variant's size under. */
    private function keyOf(?string $name): string
    {
        return ShopFilterCatalogue::normaliseKey(ProductVariant::sizeLabel($name));
    }
}
