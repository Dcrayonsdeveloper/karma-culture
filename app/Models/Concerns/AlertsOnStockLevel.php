<?php

namespace App\Models\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockAlertService;

/**
 * Puts a notification on the admin bell when a shelf crosses into low or empty.
 *
 * Hooked onto `updated` rather than `saved`, and reading the pair
 * (getOriginal, current), for exactly the reason the warehouse mirror beside it
 * does: checkout takes stock down with decrement() and the PayU refund puts it
 * back with increment(), and both fire `updated` WITHOUT firing `saved`.
 * Hooking the tidier-looking event would miss every sale in the shop.
 *
 * Reading the before/after pair here is also what makes the alert a transition
 * rather than a state. Production had 326 products and 7,056 sizes already
 * sitting under their threshold when this was written; anything that announced
 * the current state - a nightly sweep, say - would have written tens of
 * thousands of rows across four admins on its first run and buried the bell it
 * was meant to serve. A crossing can only happen once per restock, so nothing
 * that was already low says anything until somebody refills it and it runs
 * down again.
 *
 * Deliberately NOT guarded by InventoryStockService::$writingSaleableTotal, the
 * way mirrorStockToWarehouses is. That flag is set while the warehouse screens
 * write the saleable total, which is precisely the admin-adjusts-stock case
 * this is here to report - skipping it would silence the one path an admin is
 * most likely to test.
 */
trait AlertsOnStockLevel
{
    public static function bootAlertsOnStockLevel(): void
    {
        static::updated(function ($model) {
            if (! $model->wasChanged('stock_quantity')) {
                return;
            }

            // Cheapest questions first, because this runs on every stock write
            // in the shop. Both of these decide the answer without touching the
            // container or the database.
            if (StockAlertService::$muted) {
                return;
            }

            $before = (int) $model->getOriginal('stock_quantity');
            $after = (int) $model->stock_quantity;

            // Only a shelf that got SMALLER can have got worse: severity runs
            // ok -> low -> out as the quantity falls, so a restock - or a
            // no-op write - can never cross into a worse band. Returning here
            // is what keeps a size's parent product from being fetched for
            // every restocked row of an import.
            if ($after >= $before) {
                return;
            }

            $alerts = app(StockAlertService::class);

            // Two shapes of the same event, because a size is judged by its
            // product's threshold and has to be named with its product.
            if ($model instanceof ProductVariant) {
                $alerts->variantChanged($model, $before, $after);
            } elseif ($model instanceof Product) {
                $alerts->productChanged($model, $before, $after);
            }
        });
    }
}
