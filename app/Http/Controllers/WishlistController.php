<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The wishlist.
 *
 * The page and the JSON endpoint are the ordinary saved-list ones and live in
 * SavedListController, which favourites shares; what is left here is the pair
 * of signed-in write actions the wishlist has and favourites does not.
 */
class WishlistController extends SavedListController
{
    protected function cookieName(): string
    {
        return 'kk_wishlist';
    }

    protected function view(): string
    {
        return 'wishlist.index';
    }

    public function store(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $exists = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->exists();

        if (!$exists) {
            Wishlist::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist',
                'count' => Wishlist::where('user_id', auth()->id())->count(),
            ]);
        }

        return back()->with('success', 'Product added to wishlist.');
    }

    public function destroy(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist',
                'count' => Wishlist::where('user_id', auth()->id())->count(),
            ]);
        }

        return back()->with('success', 'Product removed from wishlist.');
    }
}
