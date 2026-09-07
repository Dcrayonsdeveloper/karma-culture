<?php

namespace App\Http\Controllers;

use App\Models\FestivalSale;
use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FestivalSaleController extends Controller
{
    /**
     * The page the "Introductory Offer" button in the header opens.
     *
     * The products here are already carrying their discounted price - the sale
     * wrote it into the catalogue when it went live - so this page needs no
     * per-card price override. It is an ordinary filtered listing with a banner
     * on top, which is also why sorting by "Biggest Discount" works on it.
     */
    public function show(Request $request, FestivalSale $festivalSale): View
    {
        abort_unless($festivalSale->is_active, 404);

        $productIds = $festivalSale->products()->pluck('products.id')->all();

        $filters = ProductFilters::for(
            $request,
            fn () => Product::query()
                ->where('is_active', true)
                // The empty-list guard matters: whereIn with [] matches every
                // row, which would put the whole catalogue behind a sale banner.
                ->whereIn('products.id', $productIds ?: [0]),
            [
                'action' => route('festival-sale.show', $festivalSale),
                'reset' => route('festival-sale.show', $festivalSale),
                'default_sort' => 'discount',
            ],
        );

        return view('festival-sales.show', [
            'festivalSale' => $festivalSale,
            // slug is needed: the product card links to the category and brand,
            // and route binding resolves those by slug.
            'products' => $filters->results(24, ['category:id,name,slug', 'brand:id,name,slug', 'images']),
            'filterPanel' => $filters->facets([
                'empty' => [
                    'title' => 'Nothing in this sale yet',
                    'text' => 'The offer is being put together - check back shortly.',
                    'url' => route('shop'),
                    'label' => 'Continue shopping',
                ],
            ]),
        ]);
    }
}
