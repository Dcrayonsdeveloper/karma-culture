<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::where('seller_id', $request->user()->seller->id)
            ->select('id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold')
            ->orderBy('name')
            ->paginate(30);

        return view('seller.inventory.index', compact('products'));
    }

    public function lowStock(Request $request): View
    {
        $products = Product::where('seller_id', $request->user()->seller->id)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->paginate(30);

        return view('seller.inventory.low-stock', compact('products'));
    }

    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        if ($product->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        $validated = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return back()->with('success', 'Stock updated successfully');
    }
}
