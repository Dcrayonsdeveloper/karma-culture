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

    public function edit(FlashSale $flashSale): View
    {
        $flashSale->load('products');

        return view('admin.flash-sales.edit', compact('flashSale'));
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $flashSale->update($validated);

        return redirect()->route('admin.flash-sales.index')->with('success', 'Flash sale updated');
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        $flashSale->delete();

        return redirect()->route('admin.flash-sales.index')->with('success', 'Flash sale deleted');
    }
}
