<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Rules\ValidationRules as V;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $query = Product::select('id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold')
            // Which warehouse holds what, shown per row.
            ->with(['inventoryStocks' => fn ($stocks) => $stocks
                ->select('id', 'product_id', 'variant_id', 'location_id', 'quantity')
                ->where('quantity', '>', 0)
                ->with('location:id,name,code')]);

        if ($request->filled('location')) {
            $locationId = (int) $request->input('location');
            $query->whereHas('inventoryStocks', fn ($stocks) => $stocks
                ->where('location_id', $locationId)
                ->where('quantity', '>', 0));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'in_stock' => $query->where('stock_quantity', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('low_stock_threshold')
                          ->orWhereColumn('stock_quantity', '>', 'low_stock_threshold');
                    }),
                'low_stock' => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->where('stock_quantity', '>', 0),
                'out_of_stock' => $query->where('stock_quantity', '<=', 0),
                default => null,
            };
        }

        $products = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $stats = [
            'total'         => Product::count(),
            'in_stock'      => Product::where('stock_quantity', '>', 0)->count(),
            'low_stock'     => Product::whereNotNull('low_stock_threshold')
                                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                ->where('stock_quantity', '>', 0)->count(),
            'out_of_stock'  => Product::where('stock_quantity', '<=', 0)->count(),
        ];

        $locations = InventoryLocation::orderBy('name')->get(['id', 'name', 'code', 'is_default']);

        return view('admin.inventory.index', compact('products', 'stats', 'locations'));
    }

    public function lowStock(): View
    {
        $perPage = min(max((int) request()->input('per_page', 10), 1), 100);
        $products = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->paginate($perPage)->withQueryString();

        return view('admin.inventory.low-stock', compact('products'));
    }

    public function outOfStock(): View
    {
        $perPage = min(max((int) request()->input('per_page', 10), 1), 100);
        $products = Product::where('stock_quantity', '<=', 0)
            ->orderBy('name')
            ->paginate($perPage)->withQueryString();

        return view('admin.inventory.out-of-stock', compact('products'));
    }

    /**
     * Adjust one product's stock at one warehouse.
     *
     * Stock lives on a shelf, so an adjustment has to name the shelf it happens
     * on - without one, the product total and the locations screen told
     * different stories. The warehouse defaults to the main one, which is where
     * a single-warehouse shop's stock already is.
     */
    public function updateStock(Request $request, Product $product, InventoryStockService $inventory): RedirectResponse
    {
        $request->validate([
            'location_id' => ['nullable', Rule::exists('inventory_locations', 'id')],
        ]);

        $location = $request->filled('location_id')
            ? InventoryLocation::findOrFail((int) $request->input('location_id'))
            : $inventory->defaultLocation();

        $heldHere = (int) InventoryStock::where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('location_id', $location->id)
            ->value('quantity');

        $validated = $request->validate([
            // stock_quantity is an UNSIGNED INT column, so both ends matter:
            // a negative quantity turned "remove" into a stock increase, and
            // removing more than is on the shelf threw a DB error instead of a
            // field message.
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
                Rule::when(
                    $request->input('type') === 'remove',
                    ['max:'.$heldHere],
                ),
            ],
            'type' => V::option(['add', 'remove', 'set']),
            'reason' => V::text(required: false, max: 255),
        ], [
            'quantity.max' => $request->input('type') === 'remove'
                ? "You cannot remove more than the {$heldHere} units held at {$location->name}."
                : 'The quantity is too large.',
        ]);

        $inventory->adjust(
            $location,
            $product,
            null,
            $validated['type'],
            (int) $validated['quantity'],
            $validated['reason'] ?? null,
            auth()->id(),
        );

        return back()->with('success', 'Stock updated successfully');
    }

    public function movements(Request $request): View
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $movements = InventoryMovement::with(['product:id,name,sku', 'location:id,name,code', 'createdBy:id,first_name,last_name'])
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.inventory.movements', compact('movements'));
    }
}
