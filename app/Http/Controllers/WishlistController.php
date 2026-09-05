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
    public function index(): View
    {
        // The wishlist is stored client-side, in the kk_wishlist cookie, so it
        // works for a guest. The page holds ids only; it fetches the product data
        // for them from /wishlist/items.
        return view('wishlist.index');
    }

    public function items(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->take(100)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['items' => []]);
        }

        $products = Product::whereIn('id', $ids)
            ->where('is_active', true)
            ->with(['images', 'category'])
            ->inStockFirst()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'url' => route('product.show', $p),
                'image' => $p->primary_image_url,
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'discount' => (int) ($p->discount_percentage ?? 0),
                'in_stock' => $p->isInStock(),
            ]);

        return response()->json(['items' => $products]);
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
