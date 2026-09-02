<?php

namespace App\Services;

use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stock, per warehouse.
 *
 * inventory_stocks holds the detail - how much of a product sits in which
 * location - while products.stock_quantity (or product_variants.stock_quantity
 * for a size) is the roll-up the storefront actually sells from. The two used
 * to be written independently, so the warehouse figures were seed data that
 * never moved again. Every adjustment now goes through here: the location row
 * and the saleable total move by the same delta, and an inventory_movements
 * row records where the units went.
 */
class InventoryStockService
{
    /** Adjustment kinds the admin screens offer, mapped to movement types. */
    public const TYPES = ['add' => 'in', 'remove' => 'out', 'set' => 'adjustment'];

    /**
     * Set while this service writes a saleable total itself.
     *
     * Product reports its own stock changes back here so shelves can follow
     * them (TracksWarehouseStock). Without this flag the write below would come
     * straight back as a change to mirror, and the units would be counted
     * twice.
     */
    public static bool $writingSaleableTotal = false;

    /**
     * Where an adjustment lands when the caller does not name a warehouse.
     */
    public function defaultLocation(): InventoryLocation
    {
        return InventoryLocation::where('is_default', true)->first()
            ?? InventoryLocation::orderBy('id')->first()
            ?? InventoryLocation::create([
                'name' => 'Main Warehouse',
                'code' => 'MAIN',
                'type' => 'warehouse',
                'is_active' => true,
                'is_default' => true,
            ]);
    }

    /**
     * Add to, remove from, or set the stock a warehouse holds of one product.
     *
     * $type is a key of self::TYPES. A "remove" larger than the shelf holds is
     * clamped rather than pushed negative, and the delta applied to the
     * saleable total is whatever actually moved.
     */
    public function adjust(
        InventoryLocation $location,
        Product $product,
        ?int $variantId,
        string $type,
        int $quantity,
        ?string $reason = null,
        ?int $userId = null,
    ): InventoryStock {
        return DB::transaction(function () use ($location, $product, $variantId, $type, $quantity, $reason, $userId) {
            $stock = $this->row($product->id, $variantId, $location->id, lock: true);

            $before = (int) $stock->quantity;
            $after = match ($type) {
                'add' => $before + $quantity,
                'remove' => max(0, $before - $quantity),
                default => $quantity,
            };

            $this->write($stock, $after);
            $this->applyToSaleableTotal($product, $variantId, $after - $before);

            $this->record($product->id, $variantId, $location->id, $before, $after, [
                'type' => self::TYPES[$type] ?? 'adjustment',
                'reference_type' => 'adjustment',
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            return $stock;
        });
    }

    /**
     * Stop stocking a product at a warehouse.
     *
     * The units on that shelf are real, so they leave the saleable total with
     * the line - otherwise removing a product from its only warehouse would
     * leave the storefront selling stock that is nowhere.
     */
    public function removeLine(InventoryStock $stock, ?string $reason = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($stock, $reason, $userId) {
            $before = (int) $stock->quantity;

            if ($before > 0 && $stock->product) {
                $this->applyToSaleableTotal($stock->product, $stock->variant_id, -$before);
            }

            $this->record($stock->product_id, $stock->variant_id, $stock->location_id, $before, 0, [
                'type' => 'out',
                'reference_type' => 'adjustment',
                'reason' => $reason ?: 'Removed from location',
                'created_by' => $userId,
            ]);

            $stock->delete();
        });
    }

    /**
     * Stock a brand new first warehouse with everything the shop already has.
     *
     * A shop that only starts tracking locations today still has stock, and it
     * is all sitting in the one place it just named. Without this the warehouse
     * page would open empty and only fill up as products were edited one by
     * one. Products already accounted for elsewhere are left alone, so this is
     * safe to run against a catalogue that is partly assigned.
     *
     * Sizes are not touched: product_variants.stock_quantity is a separate
     * figure, tracked per warehouse only once someone stocks it by hand.
     */
    public function seedFromCatalogue(InventoryLocation $location): int
    {
        $seeded = 0;

        Product::query()
            ->select('id', 'stock_quantity')
            ->where('stock_quantity', '>', 0)
            ->orderBy('id')
            ->chunk(200, function ($products) use ($location, &$seeded) {
                foreach ($products as $product) {
                    $elsewhere = (int) InventoryStock::where('product_id', $product->id)
                        ->whereNull('variant_id')
                        ->sum('quantity');

                    $shortfall = (int) $product->stock_quantity - $elsewhere;

                    if ($shortfall < 1) {
                        continue;
                    }

                    $row = $this->row($product->id, null, $location->id);
                    $this->write($row, (int) $row->quantity + $shortfall);
                    $seeded++;
                }
            });

        return $seeded;
    }

    /**
     * Follow a stock figure that was changed somewhere else.
     *
     * The product form, the importer and checkout all write
     * products.stock_quantity directly. Rather than teach each of them about
     * warehouses, Product reports the change (see TracksWarehouseStock) and the
     * shelves follow: units gained land in the default warehouse, units lost
     * come off the fullest shelves first. The saleable total is already
     * correct by the time this runs, so it is deliberately not touched.
     *
     * A shop with no locations set up is not tracking stock per warehouse, and
     * is left alone.
     */
    public function mirror(int $productId, ?int $variantId, int $delta): void
    {
        if ($delta === 0 || ! InventoryLocation::query()->exists()) {
            return;
        }

        if ($delta < 0) {
            $this->consume($productId, $variantId, -$delta, 'adjustment');

            return;
        }

        // An empty shelf still counts as one: a size that sold out here is
        // restocked here, not quietly dropped.
        $row = $this->rows($productId, $variantId)->first();

        if (! $row) {
            // Sizes are tracked per warehouse only once someone puts one on a
            // shelf by hand. Opening a shelf here would double-count them
            // against the product's own line, which already holds these units.
            if ($variantId !== null) {
                return;
            }

            $row = $this->row($productId, null, $this->defaultLocation()->id, lock: true);
        }

        $before = (int) $row->quantity;

        $this->write($row, $before + $delta);
        $this->record($productId, $variantId, (int) $row->location_id, $before, $before + $delta, [
            'type' => 'in',
            'reference_type' => 'adjustment',
            'reason' => 'Stock change',
        ]);
    }

    /**
     * Take sold or written-off units off the warehouse shelves.
     *
     * The saleable total has already been decremented by whoever called this,
     * so it only spreads the same units across the location rows - the default
     * warehouse first, then the fullest - and records the movement. A line is
     * only ever touched by the column it mirrors: a size sale comes off that
     * size's shelves, never off the product's own line, or the two figures
     * would disagree. Stock that is not tracked per warehouse is left alone.
     */
    public function consume(
        int $productId,
        ?int $variantId,
        int $quantity,
        string $referenceType = 'order',
        ?int $referenceId = null,
    ): void {
        if ($quantity < 1) {
            return;
        }

        foreach ($this->rowsHolding($productId, $variantId) as $row) {
            if ($quantity < 1) {
                break;
            }

            $before = (int) $row->quantity;
            $take = min($before, $quantity);

            if ($take < 1) {
                continue;
            }

            $this->write($row, $before - $take);
            $this->record($row->product_id, $row->variant_id, $row->location_id, $before, $before - $take, [
                'type' => 'out',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            $quantity -= $take;
        }
    }

    /**
     * The warehouse line for a product, existing or new.
     *
     * MySQL treats every NULL as distinct, so the (product, variant, location)
     * unique index does not by itself stop a second product-level row being
     * inserted. Matching the null explicitly is what keeps it to one line.
     */
    private function row(int $productId, ?int $variantId, int $locationId, bool $lock = false): InventoryStock
    {
        $query = InventoryStock::where('product_id', $productId)
            ->where('location_id', $locationId);

        $variantId === null
            ? $query->whereNull('variant_id')
            : $query->where('variant_id', $variantId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first() ?? new InventoryStock([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'location_id' => $locationId,
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }

    /** Warehouse lines that hold something, emptied in a sensible order. */
    private function rowsHolding(int $productId, ?int $variantId): Collection
    {
        return $this->rows($productId, $variantId, holdingOnly: true);
    }

    /**
     * Every warehouse line for one figure: the default warehouse first, then
     * the fullest. That order is what makes a sale come off the shelf a picker
     * would reach for.
     */
    private function rows(int $productId, ?int $variantId, bool $holdingOnly = false): Collection
    {
        $query = InventoryStock::with('location')->where('product_id', $productId);

        if ($holdingOnly) {
            $query->where('quantity', '>', 0);
        }

        $variantId === null
            ? $query->whereNull('variant_id')
            : $query->where('variant_id', $variantId);

        return $query->get()
            ->sortByDesc('quantity')
            ->sortByDesc(fn (InventoryStock $row) => (int) ($row->location?->is_default ?? 0))
            ->values();
    }

    private function write(InventoryStock $stock, int $after): void
    {
        $stock->quantity = $after;
        // Reserved units cannot outlive the stock they were held against.
        $stock->reserved_quantity = min((int) $stock->reserved_quantity, $after);
        $stock->save();
    }

    private function applyToSaleableTotal(Product $product, ?int $variantId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $model = $variantId ? ProductVariant::find($variantId) : $product;

        if (! $model) {
            return;
        }

        self::$writingSaleableTotal = true;

        try {
            // Both stock_quantity columns are UNSIGNED, so a would-be negative
            // is a DB error rather than a negative stock level.
            $model->forceFill([
                'stock_quantity' => max(0, (int) $model->stock_quantity + $delta),
            ])->save();
        } finally {
            self::$writingSaleableTotal = false;
        }
    }

    private function record(int $productId, ?int $variantId, int $locationId, int $before, int $after, array $attributes = []): void
    {
        // Nothing moved, so there is nothing to log - a "+0" line on the
        // movements page is noise, not history.
        if ($before === $after) {
            return;
        }

        InventoryMovement::create(array_merge([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'location_id' => $locationId,
            'type' => $after >= $before ? 'in' : 'out',
            'quantity' => abs($after - $before),
            'quantity_before' => $before,
            'quantity_after' => $after,
        ], $attributes));
    }
}
