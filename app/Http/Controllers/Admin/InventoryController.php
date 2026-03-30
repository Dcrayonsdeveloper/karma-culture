<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $request->input('per_page', 10);
        $query = Product::select('id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold');

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

        return view('admin.inventory.index', compact('products', 'stats'));
    }

    public function lowStock(): View
    {
        $perPage = request()->input('per_page', 10);
        $products = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->paginate($perPage)->withQueryString();

        return view('admin.inventory.low-stock', compact('products'));
    }

    public function outOfStock(): View
    {
        $perPage = request()->input('per_page', 10);
        $products = Product::where('stock_quantity', '<=', 0)
            ->orderBy('name')
            ->paginate($perPage)->withQueryString();

        return view('admin.inventory.out-of-stock', compact('products'));
    }

    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:add,remove,set',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldQuantity = $product->stock_quantity;

        match ($validated['type']) {
            'add' => $product->increment('stock_quantity', $validated['quantity']),
            'remove' => $product->decrement('stock_quantity', $validated['quantity']),
            'set' => $product->update(['stock_quantity' => $validated['quantity']]),
        };

        $movementType = match ($validated['type']) {
            'add' => 'in',
            'remove' => 'out',
            'set' => 'adjustment',
        };

        $defaultLocation = InventoryLocation::where('is_default', true)->first()
            ?? InventoryLocation::first()
            ?? InventoryLocation::create([
                'name' => 'Main Warehouse',
                'code' => 'MAIN',
                'type' => 'warehouse',
                'is_active' => true,
                'is_default' => true,
            ]);

        InventoryMovement::create([
            'product_id' => $product->id,
            'location_id' => $defaultLocation->id,
            'type' => $movementType,
            'reference_type' => 'adjustment',
            'quantity' => $validated['quantity'],
            'quantity_before' => $oldQuantity,
            'quantity_after' => $product->fresh()->stock_quantity,
            'reason' => $validated['reason'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Stock updated successfully');
    }

    public function movements(Request $request): View
    {
        $perPage = $request->input('per_page', 10);
        $movements = InventoryMovement::with(['product:id,name,sku', 'createdBy:id,first_name,last_name'])
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.inventory.movements', compact('movements'));
    }
}
