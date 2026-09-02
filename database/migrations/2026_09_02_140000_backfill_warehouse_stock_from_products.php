<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Put the stock that already exists onto a warehouse shelf.
 *
 * inventory_stocks was only ever written by the seeder, so the locations screen
 * described a fraction of the catalogue - a product added through the admin had
 * stock but belonged to no warehouse. Now that the warehouse view is something
 * staff work from, the two figures have to start out agreeing: the default
 * warehouse takes whatever a product's own lines do not already account for.
 *
 * Sizes are left alone. product_variants.stock_quantity is a separate figure
 * that the storefront sells from independently, and a warehouse only tracks a
 * size once someone stocks it there by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaultLocationId = DB::table('inventory_locations')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        // No warehouses configured means nothing is being tracked per location.
        if (! $defaultLocationId) {
            return;
        }

        DB::table('products')
            ->whereNull('deleted_at')
            ->select('id', 'stock_quantity')
            ->orderBy('id')
            ->chunk(500, function ($products) use ($defaultLocationId) {
                foreach ($products as $product) {
                    $this->reconcile((int) $product->id, (int) $product->stock_quantity, $defaultLocationId);
                }
            });
    }

    public function down(): void
    {
        // Opening balances: there is no previous state worth restoring.
    }

    private function reconcile(int $productId, int $stockQuantity, int $defaultLocationId): void
    {
        $lines = DB::table('inventory_stocks')
            ->where('product_id', $productId)
            ->whereNull('variant_id');

        if ((int) (clone $lines)->sum('quantity') === $stockQuantity) {
            return;
        }

        $elsewhere = (int) (clone $lines)->where('location_id', '!=', $defaultLocationId)->sum('quantity');
        $fill = max(0, $stockQuantity - $elsewhere);

        $existing = (clone $lines)->where('location_id', $defaultLocationId)->first();

        if ($existing) {
            $reserved = min((int) $existing->reserved_quantity, $fill);

            DB::table('inventory_stocks')->where('id', $existing->id)->update([
                'quantity' => $fill,
                'reserved_quantity' => $reserved,
                'available_quantity' => $fill - $reserved,
                'last_updated_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        if ($fill < 1) {
            return;
        }

        DB::table('inventory_stocks')->insert([
            'product_id' => $productId,
            'variant_id' => null,
            'location_id' => $defaultLocationId,
            'quantity' => $fill,
            'reserved_quantity' => 0,
            'available_quantity' => $fill,
            'last_updated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
