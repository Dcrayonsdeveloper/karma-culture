<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Rules\ValidationRules as V;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The products a warehouse stocks.
 *
 * Every change here moves the location's line and the product's saleable total
 * together - see InventoryStockService - so the warehouse view and the
 * storefront never disagree about how much there is.
 */
class InventoryLocationStockController extends Controller
{
    public function __construct(private readonly InventoryStockService $stock)
    {
    }

    /**
     * Put a product (or one of its sizes) on this warehouse's shelves.
     *
     * A product already stocked here is topped up rather than duplicated: the
     * (product, variant, location) row is unique, and a second line would make
     * the same units countable twice.
     */
    public function store(Request $request, InventoryLocation $location): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            // A size only belongs on this line if it belongs to the product.
            'variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('product_id', $request->input('product_id')),
            ],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => V::text(required: false, max: 255),
        ], [
            'product_id.required' => 'Choose a product to stock here.',
            'variant_id.exists' => 'That size does not belong to the selected product.',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $this->stock->adjust(
            $location,
            $product,
            $validated['variant_id'] ?? null,
            'add',
            (int) $validated['quantity'],
            $validated['reason'] ?? 'Added to location',
            auth()->id(),
        );

        return back()->with('success', "{$product->name} stocked at {$location->name}");
    }

    /**
     * Add to, remove from, or set the units this warehouse holds of one line.
     */
    public function update(Request $request, InventoryLocation $location, InventoryStock $stock): RedirectResponse
    {
        abort_unless($stock->location_id === $location->id, 404);

        $onHand = (int) $stock->quantity;

        $validated = $request->validate([
            'type' => V::option(['add', 'remove', 'set']),
            // Removing more than the shelf holds is a typo, not a request for
            // negative stock - the quantity columns are UNSIGNED either way.
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
                Rule::when($request->input('type') === 'remove', ['max:'.$onHand]),
            ],
            'reason' => V::text(required: false, max: 255),
        ], [
            'quantity.max' => $request->input('type') === 'remove'
                ? "You cannot remove more than the {$onHand} units held here."
                : 'The quantity is too large.',
        ]);

        $product = $stock->product;

        abort_if($product === null, 404);

        $this->stock->adjust(
            $location,
            $product,
            $stock->variant_id,
            $validated['type'],
            (int) $validated['quantity'],
            $validated['reason'] ?? null,
            auth()->id(),
        );

        return back()->with('success', 'Stock updated');
    }

    /**
     * Stop stocking a product here. Its units leave the saleable total too.
     */
    public function destroy(InventoryLocation $location, InventoryStock $stock): RedirectResponse
    {
        abort_unless($stock->location_id === $location->id, 404);

        $this->stock->removeLine($stock, 'Removed from '.$location->name, auth()->id());

        return back()->with('success', 'Product removed from this location');
    }
}
