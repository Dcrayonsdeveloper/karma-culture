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
    public function index(Request $request): View
    {
        // The wishlist is stored client-side, in the kk_wishlist cookie, so it
        // works for a guest - but the cookie rides along with this request like
        // any other, so the page can be rendered from it here rather than
        // fetched again once the browser has parsed the page.
        //
        // Rendered server-side because that is what lets the grid use the same
        // <x-product-card> as the shop, the home page and the product page. The
        // client-side version had to draw its own tile out of JSON, and a second
        // card is a card that drifts: this one had lost the brand line, the
        // rating, the attribute chips, the quick-add button and the sold-out
        // treatment, and wore a different badge colour and corner radius.
        $ids = $this->wishlistIds($request);

        $products = $ids->isEmpty()
            ? collect()
            : Product::whereIn('id', $ids)
                ->where('is_active', true)
                // The same set the listing grid loads, so the card finds its
                // brand, category and images without a query per tile.
                ->with(['category', 'brand', 'images'])
                ->get()
                // Newest save first, which is the order the cookie is in and the
                // order the drawer shows. A database sort would answer by id and
                // shuffle the list under someone who just saved something.
                ->sortBy(fn ($product) => $ids->search($product->id))
                ->values();

        return view('wishlist.index', ['products' => $products]);
    }

    /**
     * The ids in the kk_wishlist cookie, cleaned.
     *
     * Everything here is attacker-controlled - it is a cookie the browser can
     * be told to hold anything in - so it is treated as a list of numbers and
     * nothing more, and capped at the same 100 the JSON endpoint takes.
     */
    private function wishlistIds(Request $request)
    {
        $raw = json_decode((string) $request->cookie('kk_wishlist'), true);

        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(100)
            ->values();
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
