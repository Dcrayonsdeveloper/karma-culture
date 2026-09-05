<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlashSaleController extends Controller
{
    /**
     * The public page for a sale.
     *
     * The popup used to send shoppers to the home page with a query string
     * nothing read, so a sale advertising "5 products" gave them no way to see
     * which five.
     */
    public function show(Request $request, FlashSale $flashSale): View
    {
        abort_unless($flashSale->is_active, 404);

        // The sale's own per-product terms - sale price, stock limit, units sold -
        // live on the pivot, which a plain product query cannot carry, so they are
        // read once here and attached to whatever the filters return.
        $saleRows = $flashSale->products()->get(['products.id'])->keyBy('id');

        $filters = ProductFilters::for(
            $request,
            fn () => Product::query()
                ->where('is_active', true)
                ->whereIn('products.id', $saleRows->keys()->all() ?: [0]),
            [
                'action' => route('flash-sale.show', $flashSale),
                'reset' => route('flash-sale.show', $flashSale),
            ],
        );

        // slug is needed: the product card links to the category and brand, and
        // route binding resolves them by slug.
        $products = $filters->results(24, ['category:id,name,slug', 'brand:id,name,slug', 'images']);

        $products->getCollection()->each(function (Product $product) use ($saleRows) {
            if ($row = $saleRows->get($product->id)) {
                $product->setRelation('pivot', $row->pivot);
            }
        });

        $hasEnded = $flashSale->ends_at && $flashSale->ends_at->isPast();
        $hasStarted = ! $flashSale->starts_at || $flashSale->starts_at->isPast();

        return view('flash-sales.show', [
            'flashSale' => $flashSale,
            'products' => $products,
            'isLive' => $hasStarted && ! $hasEnded,
            'hasEnded' => $hasEnded,
            // Carbon 3 returns a float here, and the raw value is printed straight
            // into the countdown, so without the cast the seconds box reads "31.87421".
            'remainingSeconds' => $hasEnded ? 0 : (int) max(0, now()->diffInSeconds($flashSale->ends_at, false)),
            'filterPanel' => $filters->facets([
                'card' => 'flash-sales.partials.card',
                'empty' => [
                    'title' => 'No products in this sale yet',
                    'text' => 'Check back shortly.',
                    'url' => route('shop'),
                    'label' => 'Continue shopping',
                ],
            ]),
        ]);
    }
}
