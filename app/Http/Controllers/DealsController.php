<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class DealsController extends Controller
{
    public function index(): View
    {
        // The storefront lists on is_active alone. Requiring status=approved
        // here emptied the page: every live product is status=draft.
        $products = Product::where('is_active', true)
            ->whereColumn('price', '<', 'mrp')
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->orderByRaw('(mrp - price) / mrp DESC')
            ->paginate(20);

        return view('deals.index', compact('products'));
    }
}
