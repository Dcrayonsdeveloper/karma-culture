<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryLocationController extends Controller
{
    /**
     * inventory_locations.code is varchar(20) and UNIQUE - the old max:50 was
     * accepted here and then truncated by MySQL. The charset keeps it usable as
     * a warehouse reference ("WH-001") without letting markup in.
     */
    private function rules(?InventoryLocation $location = null): array
    {
        return [
            'name' => V::text(max: 255, min: 2),
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('inventory_locations', 'code')->ignore($location?->id),
            ],
            'address' => V::addressLine(required: false, max: 255),
            'is_active' => V::boolean(),
        ];
    }

    public function index(): View
    {
        $perPage = min(max((int) request()->input('per_page', 10), 1), 100);
        $locations = InventoryLocation::withCount('stocks')
            ->withSum('stocks as units_count', 'quantity')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.inventory.locations.index', compact('locations'));
    }

    /**
     * What this warehouse holds: one line per product (or per size), with the
     * picker used to put another product on its shelves.
     */
    public function show(Request $request, InventoryLocation $location): View
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $stocks = $location->stocks()
            ->with(['product:id,name,sku,stock_quantity,low_stock_threshold', 'variant:id,product_id,name,sku,stock_quantity'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->whereHas('product', fn ($product) => $product
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"));
            })
            ->orderBy(Product::select('name')->whereColumn('products.id', 'inventory_stocks.product_id'))
            ->orderBy('variant_id')
            ->paginate($perPage)
            ->withQueryString();

        $totals = $location->stocks()->toBase()->selectRaw(
            'COUNT(*) as lines, COALESCE(SUM(quantity), 0) as units, '.
            'COALESCE(SUM(reserved_quantity), 0) as reserved, COALESCE(SUM(available_quantity), 0) as available'
        )->first();

        // The picker offers every product with its sizes, so a warehouse can
        // stock a whole product or just one variant of it.
        $products = Product::select('id', 'name', 'sku', 'stock_quantity')
            ->with('variants:id,product_id,name,stock_quantity')
            ->orderBy('name')
            ->get();

        return view('admin.inventory.locations.show', compact('location', 'stocks', 'totals', 'products'));
    }

    public function create(): View
    {
        return view('admin.inventory.locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        InventoryLocation::create($validated);

        return redirect()->route('admin.inventory.locations.index')->with('success', 'Location created');
    }

    public function edit(InventoryLocation $location): View
    {
        return view('admin.inventory.locations.edit', compact('location'));
    }

    public function update(Request $request, InventoryLocation $location): RedirectResponse
    {
        $validated = $request->validate($this->rules($location));

        $location->update($validated);

        return redirect()->route('admin.inventory.locations.index')->with('success', 'Location updated');
    }

    public function destroy(InventoryLocation $location): RedirectResponse
    {
        // Stock rows cascade with the location, so deleting a warehouse that
        // still holds units would erase the record of where they are while the
        // storefront carries on offering them.
        if ($location->stocks()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', 'This location still holds stock. Remove its products before deleting it.');
        }

        $location->delete();

        return redirect()->route('admin.inventory.locations.index')->with('success', 'Location deleted');
    }
}
