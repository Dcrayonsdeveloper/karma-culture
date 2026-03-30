<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function show(Seller $seller): JsonResponse
    {
        if ($seller->status !== 'approved') {
            abort(404);
        }

        return response()->json([
            'data' => [
                'id' => $seller->id,
                'business_name' => $seller->business_name,
                'slug' => $seller->slug,
                'description' => $seller->description,
                'logo_url' => $seller->logo_url,
                'banner_url' => $seller->banner_url,
                'rating' => $seller->rating,
                'total_reviews' => $seller->total_reviews,
                'total_products' => $seller->total_products,
            ],
        ]);
    }

    public function products(Request $request, Seller $seller): JsonResponse
    {
        if ($seller->status !== 'approved') {
            abort(404);
        }

        $products = Product::where('seller_id', $seller->id)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->paginate($request->per_page ?? 20);

        return response()->json($products);
    }
}
