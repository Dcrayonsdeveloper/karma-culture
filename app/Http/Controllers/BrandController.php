<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('brands.index', compact('brands'));
    }

    public function show(Request $request, Brand $brand): View
    {
        abort_unless($brand->is_active, 404);

        // The same sidebar as everywhere else. This page used to offer sorting and
        // nothing more, so a shopper who opened a brand with sixty products had no
        // way to narrow to their size or a price they could afford.
        $filters = ProductFilters::for(
            $request,
            fn () => Product::query()->where('is_active', true)->where('products.brand_id', $brand->id),
            [
                'action' => route('brands.show', $brand),
                'reset' => route('brands.show', $brand),
            ],
        );

        return view('brands.show', [
            'brand' => $brand,
            'products' => $filters->results(),
            // The Brand facet is dropped: every product here is this brand, so the
            // list would hold exactly one row and no choice to make.
            'filterPanel' => $filters->facets([
                'brands' => collect(),
                'empty' => [
                    'title' => 'No products found',
                    'text' => "This brand doesn't have any products yet.",
                    'url' => route('brands.index'),
                    'label' => 'View all brands',
                ],
            ]),
        ]);
    }
}
