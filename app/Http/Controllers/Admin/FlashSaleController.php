<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FlashSaleController extends Controller
{
    public function index(): View
    {
        $perPage = request()->input('per_page', 10);
        $flashSales = FlashSale::withCount('products')->latest()->paginate($perPage)->withQueryString();

        return view('admin.flash-sales.index', compact('flashSales'));
    }

    public function create(): View
    {
        return view('admin.flash-sales.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        FlashSale::create($validated);

        return redirect()->route('admin.flash-sales.index')->with('success', 'Flash sale created');
    }

    /** Products a sale can include: live and sellable, cheapest name-first. */
    private function selectableProducts()
    {
        return \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price']);
    }

    public function edit(FlashSale $flashSale): View
    {
        $flashSale->load('products');

        $flashSale->load('products');

        return view('admin.flash-sales.edit', compact('flashSale'))->with('allProducts', $this->selectableProducts());
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
            'products' => 'nullable|array',
            'products.*.product_id' => 'nullable|integer|exists:products,id',
            'products.*.sale_price' => 'nullable|numeric|min:0',
            'products.*.stock_limit' => 'nullable|integer|min:0',
        ]);

        $flashSale->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $request->boolean('is_active'),
        ]);

        // Rebuild the line-up from the form. sold_count is preserved for rows
        // that survive, so a running sale does not reset its stock limits.
        $existing = $flashSale->products->keyBy('id');
        $sync = [];

        foreach ($request->input('products', []) as $row) {
            $productId = (int) ($row['product_id'] ?? 0);

            if ($productId <= 0 || isset($sync[$productId])) {
                continue;
            }

            $sync[$productId] = [
                'sale_price' => (float) ($row['sale_price'] ?? 0),
                'stock_limit' => ($row['stock_limit'] ?? '') === '' ? null : (int) $row['stock_limit'],
                'sold_count' => $existing[$productId]->pivot->sold_count ?? 0,
            ];
        }

        $flashSale->products()->sync($sync);

        return redirect()->route('admin.flash-sales.edit', $flashSale)->with('success', 'Flash sale updated');
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        $flashSale->delete();

        return redirect()->route('admin.flash-sales.index')->with('success', 'Flash sale deleted');
    }
}
