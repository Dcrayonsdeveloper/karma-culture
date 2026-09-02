<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealsController extends Controller
{
    public function index(Request $request): View
    {
        // The storefront lists on is_active alone. Requiring status=approved
        // here emptied the page: every live product is status=draft.
        $filters = ProductFilters::for(
            $request,
            fn () => Product::query()
                ->where('is_active', true)
                ->whereNotNull('products.mrp')
                ->whereColumn('products.price', '<', 'products.mrp'),
            [
                'action' => route('deals'),
                'reset' => route('deals'),
                'default_sort' => 'discount',
            ],
        );

        return view('deals.index', [
            'products' => $filters->results(20),
            'filterPanel' => $filters->facets([
                // Everything on this page is on sale by definition, so the checkbox
                // would be a control that can only ever do nothing.
                'show_on_sale' => false,
                'sorts' => [
                    'discount' => 'Biggest Discount',
                    'newest' => 'Newest',
                    'price_asc' => 'Price: Low to High',
                    'price_desc' => 'Price: High to Low',
                    'rating' => 'Best Rating',
                    'bestselling' => 'Bestselling',
                ],
                'empty' => [
                    'title' => 'No deals available',
                    'text' => 'Check back soon for new deals and discounts.',
                    'url' => route('shop'),
                    'label' => 'Browse all products',
                ],
            ]),
        ]);
    }
}
