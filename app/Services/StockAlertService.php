<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\StockLevel;
use Illuminate\Support\Facades\Log;

/**
 * Tells the admin bell when a shelf has just gone low, or just gone empty.
 *
 * The shop had no in-app signal for either. `stock:check-low` mails a digest at
 * 08:00 and nothing has ever reached the bell, so a size that sold out at
 * lunchtime stayed sold out until somebody happened to open the inventory page.
 *
 * Raised from {@see \App\Models\Concerns\AlertsOnStockLevel}, on the same
 * `updated` hook the warehouse mirror already runs on - which is the only
 * point every sale, restock, admin adjustment and importer converges on.
 */
class StockAlertService
{
    /**
     * Set while a bulk run is rewriting stock and does not want to narrate it.
     *
     * The Excel importer rewrites every SKU in the sheet in one pass and size
     * retirement deletes variants 500 at a time; a notification per row would
     * be thousands of rows across every admin for one action nobody needs
     * telling about. Same shape as InventoryStockService::$writingSaleableTotal,
     * for the same reason.
     */
    public static bool $muted = false;

    /**
     * Run $work without announcing any shelf it moves.
     *
     * try/finally rather than a plain reset: a bulk import that throws
     * half-way must not leave the whole app silent for the rest of the process.
     */
    public static function muted(callable $work): mixed
    {
        $was = self::$muted;
        self::$muted = true;

        try {
            return $work();
        } finally {
            self::$muted = $was;
        }
    }

    public function __construct(private NotificationService $notifications) {}

    /**
     * Announce a product's own shelf crossing into low or empty.
     */
    public function productChanged(Product $product, int $before, int $after): void
    {
        if (! $this->worthWatching($product)) {
            return;
        }

        $threshold = (int) $product->low_stock_threshold;

        $this->announce(
            StockLevel::for($before, $threshold),
            StockLevel::for($after, $threshold),
            $product->name,
            $after,
            $threshold,
            ['product_id' => $product->id],
        );
    }

    /**
     * Announce one size crossing into low or empty.
     *
     * product_variants carries no threshold of its own, so a size is judged by
     * the product's - which is the figure the admin set and the only one that
     * exists. The product is named as well as the size: "M" alone in a bell
     * that serves the whole catalogue says nothing.
     */
    public function variantChanged(ProductVariant $variant, int $before, int $after): void
    {
        // The parent is what carries the threshold and the name, and a variant
        // reached through decrement() during checkout has not loaded it. Only
        // the two columns are needed, so the row is not hydrated whole.
        $product = $variant->relationLoaded('product')
            ? $variant->product
            : Product::select('id', 'name', 'low_stock_threshold', 'is_active', 'deleted_at')
                ->find($variant->product_id);

        // A size on a product nobody can buy is not news either, and a size
        // switched off in the sizes editor is one the shop has stopped
        // offering - its shelf running down says nothing.
        if (! $product || ! $this->worthWatching($product) || $variant->is_active === false) {
            return;
        }

        $threshold = (int) $product->low_stock_threshold;

        // The size as the sizes editor shows it. Older rows store the whole
        // variant name ("Block Print Kurti - Indigo - L"), which would put the
        // product name in the bell twice.
        $size = ProductVariant::sizeLabel($variant->name);

        $this->announce(
            StockLevel::for($before, $threshold),
            StockLevel::for($after, $threshold),
            $size === '' ? $product->name : "{$product->name} ({$size})",
            $after,
            $threshold,
            [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'variant_label' => $size,
            ],
        );
    }

    /**
     * Is this product one the shop is actually selling?
     *
     * Drafts and switched-off products are not worth a bell. The Excel
     * importer creates every new row `is_active => false` and then writes its
     * stock, and the daily low-stock email has always filtered the same way
     * (CheckLowStock filters is_active) - an alert about a product no shopper
     * can reach is noise the admin cannot act on.
     *
     * Soft-deleted rows are excluded for the same reason: a product in the bin
     * still has a stock figure, and SizeRetirementService moves stock around
     * while retiring things.
     */
    private function worthWatching(Product $product): bool
    {
        return (bool) $product->is_active && $product->deleted_at === null;
    }

    /**
     * Write one admin notification, but only for a shelf that got worse.
     *
     * Written inline rather than deferred to DB::afterCommit, deliberately.
     * Both checkouts decrement inside a transaction, so writing here means a
     * sale that rolls back takes its notification with it - which is the
     * outcome afterCommit would be reached for anyway. And afterCommit would
     * cost more than it bought: RefreshDatabase wraps every test in a
     * transaction it never commits (Foundation/Testing/RefreshDatabase.php
     * sets the manager, then begins), and this framework version has no
     * test-mode exemption, so every callback would be silently dropped in the
     * whole suite.
     *
     * The one thing inline gives up is the toast on a checkout slower than the
     * poller's five-second overlap: the row is timestamped when it is written
     * but only visible at commit. The notification itself still lands, and the
     * unread badge counts it, because that query has no time window.
     *
     * A failure here is logged and dropped. This runs inside checkout's
     * transaction and inside the admin adjust screen's - turning an
     * unwritable notification into an exception would cost a customer the
     * order they had already paid for, to save an alert about it.
     */
    private function announce(
        string $before,
        string $after,
        string $label,
        int $quantity,
        int $threshold,
        array $data,
    ): void {
        if (self::$muted || ! StockLevel::worsened($before, $after)) {
            return;
        }

        $out = $after === StockLevel::OUT;

        try {
            $this->notifications->notifyAdmins(
                $out ? 'product_out_of_stock' : 'product_low_stock',
                $out ? 'Out of Stock' : 'Low Stock',
                $out
                    ? "{$label} is out of stock."
                    : "{$label} is down to {$quantity} left (threshold {$threshold}).",
                $data + [
                    'quantity' => $quantity,
                    'threshold' => $threshold,
                    'level' => $after,
                    'previous_level' => $before,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify admins of a stock level change', [
                'data' => $data,
                'level' => $after,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
