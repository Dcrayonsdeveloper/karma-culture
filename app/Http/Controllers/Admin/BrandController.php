<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $perPage = min(max((int) request()->input('per_page', 10), 1), 100);
        $brands = Brand::withCount('products')->orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [...V::text(max: 255, min: 2), 'unique:brands,name'],
            'description' => V::textarea(required: false, max: 2000),
            'logo' => V::image(required: false, maxKb: 2048, allowGif: true),
            'is_active' => V::boolean(),
            'is_featured' => V::boolean(),
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('logo')) {
            $validated['logo_url'] = $request->file('logo')->store('brands', 'public');
        }

        unset($validated['logo']);

        Brand::create($validated);

        return redirect()->route('admin.brands.index')->with('success', 'Brand created successfully');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [...V::text(max: 255, min: 2), Rule::unique('brands', 'name')->ignore($brand->id)],
            'description' => V::textarea(required: false, max: 2000),
            'logo' => V::image(required: false, maxKb: 2048, allowGif: true),
            'is_active' => V::boolean(),
            'is_featured' => V::boolean(),
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('logo')) {
            $validated['logo_url'] = $request->file('logo')->store('brands', 'public');
        }

        unset($validated['logo']);

        $brand->update($validated);

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated successfully');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted successfully');
    }
}
