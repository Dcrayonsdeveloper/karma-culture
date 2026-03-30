<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class DealsController extends Controller
{
    public function index(): View
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->whereColumn('price', '<', 'mrp')
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->orderByRaw('(mrp - price) / mrp DESC')
            ->paginate(20);

        return view('deals.index', compact('products'));
    }
}
