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
            // images is a relation, not a column. Inside the colon list it was
            // read as one, so the query asked products for an "images" column and
            // the endpoint 500'd for anyone whose wishlist was not empty. Nested
            // relations load as their own entry - the same shape the working
            // Api/V1/Product/ProductController::show already uses.
            ->with(['product:id,name,slug,price,mrp', 'product.images'])
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
