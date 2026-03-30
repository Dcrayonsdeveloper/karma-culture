<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        if (!auth()->check()) {
            if ($request->wantsJson()) {
                return response()->json(['items' => []], 401);
            }
            return redirect()->route('login');
        }

        if ($request->wantsJson()) {
            $items = Wishlist::where('user_id', auth()->id())
                ->select('id', 'product_id')
                ->get();

            return response()->json(['items' => $items]);
        }

        $wishlistItems = Wishlist::query()
            ->where('user_id', auth()->id())
            ->with(['product.category', 'product.primaryImage'])
            ->latest()
            ->paginate(24);

        return view('wishlist.index', compact('wishlistItems'));
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
