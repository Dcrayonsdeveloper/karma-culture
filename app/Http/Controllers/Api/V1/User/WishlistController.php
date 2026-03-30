<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = $request->user()->wishlists()
            ->with('product:id,name,slug,price,mrp,images')
            ->latest()
            ->paginate(20);

        return response()->json($wishlists);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $exists = $request->user()->wishlists()->where('product_id', $product->id)->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Product already in wishlist',
            ], 409);
        }

        $request->user()->wishlists()->create([
            'product_id' => $product->id,
        ]);

        return response()->json([
            'message' => 'Product added to wishlist',
        ], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        return response()->json([
            'message' => 'Product removed from wishlist',
        ]);
    }
}
