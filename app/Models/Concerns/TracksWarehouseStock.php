<?php

namespace App\Models\Concerns;

use App\Services\InventoryStockService;

/**
 * Keeps the warehouse shelves in step with a model's own stock figure.
 *
 * The product form, the importer, checkout and the storefront all write
 * stock_quantity directly, and inventory_stocks used to sit beside them
 * untouched - so the warehouse view was seed data that described nothing.
 * Reporting the change here means every one of those paths lands on a shelf
 * without any of them having to know that shelves exist.
 */
trait TracksWarehouseStock
{
    public static function bootTracksWarehouseStock(): void
    {
        static::created(function ($model) {
            $model->mirrorStockToWarehouses(0, (int) $model->stock_quantity);
        });

        static::updated(function ($model) {
            // increment()/decrement() fire "updated" without firing "saved",
            // which is how checkout takes stock down - hooking the wrong one
            // would miss every sale.
            if ($model->wasChanged('stock_quantity')) {
                $model->mirrorStockToWarehouses(
                    (int) $model->getOriginal('stock_quantity'),
                    (int) $model->stock_quantity,
                );
            }
        });
    }

    /** [product id, variant id] the stock figure belongs to. */
    abstract public function warehouseStockKey(): array;

    protected function mirrorStockToWarehouses(int $before, int $after): void
    {
        if ($before === $after || InventoryStockService::$writingSaleableTotal) {
            return;
        }

        [$productId, $variantId] = $this->warehouseStockKey();

        app(InventoryStockService::class)->mirror($productId, $variantId, $after - $before);
    }
}
