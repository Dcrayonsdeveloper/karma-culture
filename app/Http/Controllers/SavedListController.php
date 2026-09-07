<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * A list of products the browser keeps for itself.
 *
 * The shop has two of them - the wishlist and favourites - and they are the
 * same list twice over: product ids in a plain cookie the browser owns, the
 * page rendered server-side out of that cookie with the ordinary product
 * card, and a JSON endpoint for anything drawn after the page has loaded.
 *
 * Shared rather than copied, because the parts worth getting wrong here are
 * the quiet ones: the cap on how many ids are honoured, the cast that stops a
 * hand-written cookie reaching the query, the sort that keeps the newest save
 * at the front. A second copy of those is a second set of them to drift, and
 * the drift would show up as one list quietly behaving unlike the other.
 *
 * Client-side by design: nothing here writes a row, the cookie IS the list.
 * READING is therefore open - the page and the JSON endpoint both answer a
 * signed-out visitor, and have to, because the cookie arrives on every
 * request whoever is holding it. SAVING is not: the heart and the star both
 * go through kkRequireLogin in resources/js/app.js and send a guest to the
 * login page first, the same gate the cart uses. There is no write route to
 * protect here because there is nothing to write - the gate is the button.
 */
abstract class SavedListController extends Controller
{
    /**
     * The browser cookie this list lives in.
     *
     * Whatever it returns must also appear in the `encryptCookies(except: ...)`
     * list in bootstrap/app.php. The cookie is written by JavaScript, so it
     * arrives as plain text, and Laravel's default decryption silently discards
     * it - the server then sees an empty list and renders the empty state over
     * a list the browser is holding.
     */
    abstract protected function cookieName(): string;

    /** The Blade view that renders the page. */
    abstract protected function view(): string;

    /**
     * How many ids are honoured, on the page and at the endpoint alike.
     *
     * The two have to agree: a page that renders 200 tiles from a list the
     * endpoint will only ever resolve 100 of is a page whose second half
     * disappears the moment Alpine refreshes it.
     */
    protected const MAX_IDS = 100;

    public function index(Request $request): View
    {
        // The list is stored client-side, but the cookie rides along with this
        // request like any other, so the page can be rendered from it here
        // rather than fetched again once the browser has parsed the page.
        //
        // Rendered server-side because that is what lets the grid use the same
        // <x-product-card> as the shop, the home page and the product page. The
        // client-side version had to draw its own tile out of JSON, and a second
        // card is a card that drifts: that one had lost the brand line, the
        // rating, the attribute chips, the quick-add button and the sold-out
        // treatment, and wore a different badge colour and corner radius.
        $ids = $this->savedIds($request);

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

        return view($this->view(), ['products' => $products]);
    }

    /**
     * The ids in this list's cookie, cleaned.
     *
     * Everything here is attacker-controlled - it is a cookie the browser can
     * be told to hold anything in - so it is treated as a list of numbers and
     * nothing more, and capped at the same figure the JSON endpoint takes.
     */
    protected function savedIds(Request $request): Collection
    {
        $raw = json_decode((string) $request->cookie($this->cookieName()), true);

        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(static::MAX_IDS)
            ->values();
    }

    /**
     * Product data for a list of ids.
     *
     * Readable without an account: it is what renders the page and the header
     * badge, both of which have to answer a signed-out visitor. Filling the
     * list is what takes an account, and that gate is on the button.
     */
    public function items(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->take(static::MAX_IDS)
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
}
