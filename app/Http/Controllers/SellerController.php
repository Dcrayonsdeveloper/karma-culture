<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function show(Request $request, Seller $seller): View
    {
        abort_unless($seller->isApproved(), 404);

        $query = Product::query()
            ->where('is_active', true)
            ->where('seller_id', $seller->id)
            ->with(['category', 'primaryImage']);

        $sortBy = $request->get('sort', 'newest');
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'bestselling' => $query->orderBy('sales_count', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(24)->withQueryString();

        return view('sellers.show', compact('seller', 'products'));
    }
}
