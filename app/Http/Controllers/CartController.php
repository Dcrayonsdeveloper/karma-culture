<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Rules\ValidationRules as V;
use App\Support\OfferClaims;
use App\Support\ProductOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
<<<<<<< HEAD
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
=======
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = $this->getOrCreateCart();

        // Auto-apply only ran when the cart last changed, so a coupon created
        // or activated afterwards never reached a cart that was already sitting
        // there. Re-evaluate on view - unless the customer removed the coupon
        // themselves, which must not spring back.
        //
        // The dismissal now suppresses the auto-apply pass rather than the whole
        // recalculation. It used to skip recalculate() outright, which also
        // skipped the delivery charge, the tax and the subtotal: a shopper who
        // had once removed a coupon saw this page quote whatever was last
        // written to the cart row, so a shipping setting changed afterwards
        // never reached them and the summary and the checkout disagreed.
        if ($cart->items()->exists()) {
            $cart->recalculate(skipAutoApply: session('coupon_dismissed', false));
        }

        // An offer claimed from the exit popup, honoured now that we know who
        // the customer is. Deliberately AFTER recalculate(): the auto-apply
        // pass has run by this point, so applyTo() compares against the real
        // incumbent rather than a coupon that is about to appear underneath it.
        $claimedOffer = OfferClaims::applyTo($cart, request()->user());

        // images, not primaryImage: primary_image_url reads the whole set so it
        // can fall back past a video, and eager-loading only the primary row made
        // that a query per line.
        $cart->load(['items.product.images', 'items.variant']);

        // "You May Also Like" - products related to the cart's items (else popular).
        $recommended = $this->recommendedForCart($cart);

        return view('cart.index', compact('cart', 'recommended', 'claimedOffer'));
    }

    /**
     * Recommended products for the cart page - same category as cart items,
     * topped up with other active products so the section always shows 4.
     */
    private function recommendedForCart(Cart $cart): Collection
    {
        $productIds = $cart->items->pluck('product_id')->filter()->unique()->all();
        $with = ['category', 'brand', 'images'];

        $query = Product::where('is_active', true)->with($with)->whereHas('images');
        if (! empty($productIds)) {
            $categoryIds = Product::whereIn('id', $productIds)->pluck('category_id')->unique()->filter()->all();
            $query->whereNotIn('id', $productIds)
                ->when(! empty($categoryIds), fn ($q) => $q->whereIn('category_id', $categoryIds));
        }
        $recommended = $query->inStockFirst()->inRandomOrder()->take(4)->get();

        if ($recommended->count() < 4) {
            $exclude = $recommended->pluck('id')->merge($productIds)->all();
            $recommended = $recommended->concat(
                Product::where('is_active', true)
                    ->whereNotIn('id', $exclude)
                    ->whereHas('images')
                    ->with($with)
                    ->inStockFirst()
                    ->inRandomOrder()
                    ->take(4 - $recommended->count())
                    ->get()
            );
        }

        // The top-up runs as its own query, so concat can drop an available
        // product behind a sold-out one. Re-sort the joined row once.
        return collect($recommended)
            ->sortBy(fn (Product $p) => $p->isInStock() ? 0 : 1)
            ->values();
    }

    public function data(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        // images, not primaryImage: primary_image_url reads the whole set so it
        // can fall back past a video, and eager-loading only the primary row made
        // that a query per line.
        $cart->load(['items.product.images', 'items.variant']);

        $items = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'size' => $item->size,
                'colour' => $item->colour,
                'texture' => $item->texture,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'product_name' => $item->product->name ?? '',
                'variant_name' => $item->variant->name ?? null,
                // Every other field here guards against the product having been
                // deleted out from under the cart line; image was the one that did
                // not, so a cart holding a since-deleted product 500d the whole
                // endpoint. primary_image_url is also what the rest of the app
                // sends: it resolves the path, fingerprints it, skips a video and
                // falls back to the placeholder instead of answering null when the
                // product has gallery images but no main one.
                'image' => $item->product?->primary_image_url,
                'slug' => $item->product->slug ?? '',
                // Sent for the same reason /cart/recommendations sends it: without
                // it the drawer had to build '/product/' + item.slug by hand.
                'url' => $item->product ? route('product.show', $item->product) : null,
            ];
        });

        return response()->json([
            'items' => $items,
            'cart_count' => $cart->items->sum('quantity'),
            // cart_-prefixed like every other cart endpoint. This one answered with
            // bare subtotal/discount/total, so the endpoint whose whole job is to
            // report cart state was the one that spelled it differently.
            'cart_subtotal' => (float) $cart->subtotal,
            'cart_discount' => (float) $cart->discount,
            // Delivery and tax travel with the rest of the money. The cart page
            // works its own subtotal out from the line rows so quantity changes
            // feel instant, but it must not work the DELIVERY charge out too:
            // that is ShippingCharge's job, and a second implementation of the
            // threshold rule in JavaScript is exactly how a shopper ends up
            // shown one total and billed another. Sent, not recomputed.
            'cart_shipping' => (float) $cart->shipping,
            'cart_tax' => (float) $cart->tax,
            'cart_total' => (float) $cart->total,
        ]);
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // is_active matters: ProductController::show 404s an inactive
            // product, so without it the only way to buy a withdrawn line was
            // to POST its id straight to this endpoint.
            //
            // whereNull('deleted_at') matters for the opposite reason. Products
            // are soft-deleted - Admin\ProductController::destroy calls delete()
            // and leaves is_active alone - but Rule::exists runs a raw query
            // that no model scope touches, so every deleted product still
            // satisfied is_active = true and passed the rule. The lookup below
            // then applied the soft-delete scope, missed, and threw; Laravel
            // turned that into a 404 carrying the ORM's own sentence, and
            // convertExceptionToArray keeps an HttpException's message even
            // with APP_DEBUG off. So a shopper who pressed Add to Cart on a
            // page an admin had just removed the product from read "No query
            // results for model [App\Models\Product] 12." in the toast: our
            // class name and a row id, shown to a customer. The id arrived on a
            // field, so the answer belongs on that field as an ordinary 422.
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            // Charset and length only - whether these are OPTIONS THIS PRODUCT
            // ACTUALLY OFFERS is checked below, once the product is loaded.
            'size' => V::text(required: false, max: 50),
            'colour' => V::text(required: false, max: 60),
            'texture' => V::text(required: false, max: 60),
            'quantity' => V::quantity(max: 99),
        ], [
            // Without this the scoped rule answers a withdrawn product with
            // Laravel's "The selected product id is invalid." - a sentence about
            // a request field, shown to somebody who pressed Add to Cart on a
            // page that had simply gone stale. Same words as the lookup below
            // uses when it loses the same race, so the two cannot disagree.
            'product_id.exists' => 'That product is no longer available.',
            'variant_id.exists' => 'That option is no longer available for this product.',
        ]);

        if ($validator->fails() && $request->wantsJson()) {
            return $this->invalid($validator);
        }

        // Throws for a browser form post, exactly as $request->validate() did:
        // the redirect-with-errors is what puts each message back under its own
        // input. Only the JSON caller is answered above, and only because the
        // framework's own JSON body speaks a different dialect to this
        // controller's - see invalid() for what that cost.
        $validated = $validator->validate();

        // find(), not findOrFail(): the rule above has already proved this id
        // names a product the storefront sells, so a miss here can only be a
        // race with an admin withdrawing it between validation and this line.
        // That deserves the same field message as any other unusable
        // product_id - one the shopper can act on - rather than an exception
        // rendered to them as a 404 body.
        $product = Product::where('is_active', true)->find($validated['product_id']);

        if (! $product) {
            return $this->failed($request, 'That product is no longer available.', 'product_id');
        }

        // Check stock
        $variantId = $validated['variant_id'] ?? null;
        $size = $validated['size'] ?? null;
        $colour = $validated['colour'] ?? null;
        $texture = $validated['texture'] ?? null;

        // exists:product_variants,id proves the variant exists, not that it
        // belongs to THIS product. A mismatched pair used to make find()
        // return null and the ->stock_quantity read fatal.
        // is_active is part of the same question: the product page only ever
        // offers active rows, so an inactive one did not come from the page.
        $variant = $variantId
            ? $product->variants()->where('is_active', true)->find($variantId)
            : null;

        if ($variantId && ! $variant) {
            return $this->failed($request, 'That option is no longer available for this product.', 'variant_id');
        }

        // size, colour and texture are free-text POST fields that get written to
        // the cart line, carried onto order_items and printed on the invoice.
        // Bounding the charset is not enough - "Size: XXXL" for a product sold
        // only in S/M, or any string at all, was accepted and shipped. They are
        // held to the same list the product page renders.
        $options = ProductOptions::for($product);

        $checks = [
            'size' => [$size, $options->sizes, fn (string $v) => $options->offersSize($v)],
            'colour' => [$colour, $options->colourNames(), fn (string $v) => $options->offersColour($v)],
            'texture' => [$texture, $options->textures, fn (string $v) => $options->offersTexture($v)],
        ];

        foreach ($checks as $field => [$chosen, $offered, $accepts]) {
            if ($chosen === null || $accepts($chosen)) {
                continue;
            }

            $error = $offered->isEmpty()
                ? 'This product is not sold by '.$field.'.'
                : 'That '.$field.' is not available for this product.';

            return $this->failed($request, $error, $field);
        }

        // The product's own flag is the storefront's sell / don't-sell switch: the
        // card paints its badge from isInStock() and the PDP hides both CTAs on it.
        // This endpoint read the quantity alone, so the cart drawer's quick-add was
        // the one door still open on a product every other surface calls sold out.
        //
        // Only the stock_status half is mirrored here. product_variants has no such
        // column, and a blanket isInStock() would also refuse a variant-stocked
        // product whose parent row happens to sit at 0.
        if ($product->stock_status !== 'in_stock') {
            return $this->failed($request, 'This item is currently out of stock.', 'quantity', ['available' => 0]);
        }

        $stockQuantity = $variant ? $variant->stock_quantity : $product->stock_quantity;

        if ($stockQuantity < $validated['quantity']) {
            $error = $stockQuantity > 0
                ? "Only {$stockQuantity} item(s) available in stock."
                : 'This item is currently out of stock.';

            return $this->failed($request, $error, 'quantity', ['available' => $stockQuantity]);
        }

        $cart = $this->getOrCreateCart();

        // Check if item already in cart (same product + variant + size + colour
        // + texture = same line). texture belongs in the lookup as much as the
        // other four: without it a second texture of a size and colour already
        // in the cart finds that row, fails to merge into it and hits
        // cart_items_line_texture_unique as a 1062 instead.
        $existingItem = $cart->items()
            ->where('product_id', $validated['product_id'])
            ->where('variant_id', $variantId)
            ->where('size', $size)
            ->where('colour', $colour)
            ->where('texture', $texture)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $validated['quantity'];
            if ($newQuantity > $stockQuantity) {
                $inCart = $existingItem->quantity;
                $canAdd = $stockQuantity - $inCart;
                $error = $canAdd > 0
                    ? "You already have {$inCart} in your cart. You can add up to {$canAdd} more."
                    : "You already have all {$stockQuantity} available item(s) in your cart.";

                return $this->failed($request, $error, 'quantity', [
                    'available' => $stockQuantity,
                    'in_cart' => $inCart,
                ]);
            }
            $existingItem->update(['quantity' => $newQuantity]);
        } else {
            $price = $variant ? ($variant->price ?? $product->price) : $product->price;

            // A running flash sale beats the shelf price, including a variant's
            // own price - otherwise the countdown promises a discount the
            // customer never receives.
            if ($flash = $product->flashSalePrice()) {
                $price = min((float) $price, $flash);
            }

            $cart->items()->create([
                'product_id' => $validated['product_id'],
                'variant_id' => $variantId,
                'size' => $size,
                'colour' => $colour,
                'texture' => $texture,
                'quantity' => $validated['quantity'],
                'price' => $price,
            ]);
        }

        $cart->recalculate();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart',
                'cart_count' => $cart->items->sum('quantity'),
                // Cast, like the other four. total is a decimal column, so without
                // this it left as the string "1499.00" while every sibling sent a
                // number - and a consumer comparing cart_total > 500 got string
                // semantics from this one endpoint alone.
                'cart_total' => (float) $cart->total,
            ]);
        }

        return back()->with('success', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        // Verify cart ownership
        $cart = $this->getOrCreateCart();
        abort_if($cartItem->cart_id !== $cart->id, 403);

        $validator = Validator::make($request->all(), [
            'quantity' => V::quantity(max: 99),
        ]);

        if ($validator->fails() && $request->wantsJson()) {
            return $this->invalid($validator);
        }

        $validated = $validator->validate();

        // Check stock. Null-safe, because a cart line outlives the rows it
        // points at - products are soft-deleted, variants are deleted outright
        // - and reading ->stock_quantity off the missing relation turned "that
        // item is gone" into a 500 on an ordinary quantity change.
        $stockQuantity = (int) ($cartItem->variant_id
            ? ($cartItem->variant?->stock_quantity ?? 0)
            : ($cartItem->product?->stock_quantity ?? 0));

        if ($validated['quantity'] > $stockQuantity) {
            $error = $stockQuantity > 0
                ? "Only {$stockQuantity} item(s) available in stock."
                : 'This item is currently out of stock.';

            return $this->failed($request, $error, 'quantity', ['available' => $stockQuantity]);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);
        $cart = $cartItem->cart;
        $hadCoupon = $cart->coupon_id;
        $cart->recalculate();
        $cart->refresh();
        $cart->load('coupon');

        $couponRemoved = $hadCoupon && ! $cart->coupon_id;
        $message = $couponRemoved
            ? 'Cart updated. Coupon was removed as it no longer applies.'
            : 'Cart updated';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'coupon_removed' => $couponRemoved,
                'item_total' => $cartItem->quantity * $cartItem->price,
                'cart_count' => $cart->items->sum('quantity'),
                'cart_subtotal' => (float) $cart->subtotal,
                'cart_discount' => (float) $cart->discount,
                // Changing a quantity is what carries a basket over or back under
                // the free-delivery minimum, so this is the response that has to
                // report the new charge.
                'cart_shipping' => (float) $cart->shipping,
                'cart_tax' => (float) $cart->tax,
                'cart_total' => (float) $cart->total,
                'coupon' => $cart->coupon ? $this->formatCouponData($cart->coupon, $cart) : null,
            ]);
        }

        return back()->with('success', $message);
    }

    public function destroy(CartItem $cartItem): JsonResponse|RedirectResponse
    {
        // Verify cart ownership
        $cart = $this->getOrCreateCart();
        abort_if($cartItem->cart_id !== $cart->id, 403);
        $hadCoupon = $cart->coupon_id;
        $cartItem->delete();
        $cart->recalculate();
        $cart->refresh();
        $cart->load('coupon');

        $couponRemoved = $hadCoupon && ! $cart->coupon_id;
        $message = $couponRemoved
            ? 'Item removed from cart. Coupon was removed as it no longer applies.'
            : 'Item removed from cart';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'coupon_removed' => $couponRemoved,
                'cart_count' => $cart->items->sum('quantity'),
                'cart_subtotal' => (float) $cart->subtotal,
                'cart_discount' => (float) $cart->discount,
                'cart_shipping' => (float) $cart->shipping,
                'cart_tax' => (float) $cart->tax,
                'cart_total' => (float) $cart->total,
                'coupon' => $cart->coupon ? $this->formatCouponData($cart->coupon, $cart) : null,
            ]);
        }

        return back()->with('success', $message);
    }

    public function clear(): JsonResponse|RedirectResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete();
        $cart->update([
            'coupon_id' => null,
            'discount' => 0,
        ]);
        $cart->recalculate();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared',
            ]);
        }

        return back()->with('success', 'Cart cleared.');
    }

    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // Was an unbounded 'string', so a megabyte of text was strtoupper'd
            // and looked up on every attempt. 50 is the coupons.code column
            // width and the ceiling Admin\CouponController creates codes under,
            // so no findable code is excluded.
            'code' => V::text(max: 50),
        ]);

        // The coupon box has no charset filter on the way in - deliberately, so
        // a code is never silently rewritten as it is typed - which makes this
        // the one place a shopper meets a rule failure by ordinary accident:
        // pasting "<b>SALE" trips the no-markup rule. Answered by the framework
        // that arrived with no `error` key, so the cart page fell through to
        // "Invalid coupon" - a different and untrue reason for the refusal.
        if ($validator->fails() && $request->wantsJson()) {
            return $this->invalid($validator);
        }

        $validated = $validator->validate();

        // Entering a code is a fresh decision: stop suppressing auto-apply.
        session()->forget('coupon_dismissed');

        $cart = $this->getOrCreateCart();
        $cart->load(['items.product', 'coupon']);

        // Prevent stacking - if a coupon is already applied, reject
        if ($cart->coupon_id) {
            return $this->failed($request, 'A coupon is already applied. Remove it first to apply a different one.', 'code');
        }

        // Another hand-written copy of the validity predicate, and this one
        // forgot the usage cap: a coupon that had been redeemed its maximum
        // number of times still applied here with a success message, then
        // silently contributed no discount because Cart::discount and the
        // checkout both gate on isValid(). The scope checks all four rules.
        $coupon = Coupon::where('code', strtoupper($validated['code']))
            ->statusIs(Coupon::STATUS_ACTIVE)
            ->first();

        if (! $coupon) {
            return $this->failed($request, 'Invalid or expired coupon code.', 'code');
        }

        // Check minimum order amount (not for BOGO - BOGO checks quantity instead)
        if ($coupon->type !== 'buy_x_get_y' && $coupon->min_order_amount && $cart->subtotal < $coupon->min_order_amount) {
            $message = 'This coupon requires a minimum order of '.format_price($coupon->min_order_amount).'.';

            return $this->failed($request, $message, 'code');
        }

        // Check global usage limit
        if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
            return $this->failed($request, 'This coupon has reached its usage limit.', 'code');
        }

        // Check per-user usage limit
        if (auth()->check() && $coupon->usage_per_user) {
            $userUsage = Order::where('user_id', auth()->id())
                ->where('coupon_id', $coupon->id)
                ->count();
            if ($userUsage >= $coupon->usage_per_user) {
                return $this->failed($request, 'You have already used this coupon the maximum number of times.', 'code');
            }
        }

        // Calculate discount using the model
        $discount = $coupon->calculateDiscount((float) $cart->subtotal, $cart->items);

        if ($discount <= 0 && $coupon->type !== 'free_shipping') {
            $message = $coupon->type === 'buy_x_get_y'
                ? 'Your cart does not meet the quantity requirements for this offer.'
                : 'This coupon cannot be applied to your cart.';

            return $this->failed($request, $message, 'code');
        }

        $cart->update([
            'coupon_id' => $coupon->id,
            'discount' => $discount,
        ]);
        $cart->recalculate();
        $cart->refresh();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Coupon applied successfully',
                'cart_discount' => (float) $cart->discount,
                // A discount can drop a basket back under the free-delivery
                // minimum, and a free_shipping coupon waives the charge outright -
                // both change this figure, so it is sent with the discount.
                'cart_shipping' => (float) $cart->shipping,
                'cart_tax' => (float) $cart->tax,
                'cart_total' => (float) $cart->total,
                'coupon' => $this->formatCouponData($coupon, $cart),
            ]);
        }

        return back()->with('success', 'Coupon applied successfully.');
    }

    public function removeCoupon(): JsonResponse|RedirectResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->update([
            'coupon_id' => null,
            'discount' => 0,
        ]);
        // Remember the removal for this session, so viewing the cart does not
        // silently put the coupon back.
        session(['coupon_dismissed' => true]);

        $cart->recalculate(skipAutoApply: true);
        $cart->refresh();
        $cart->load('coupon');

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Coupon removed',
                'cart_subtotal' => (float) $cart->subtotal,
                'cart_discount' => (float) $cart->discount,
                'cart_shipping' => (float) $cart->shipping,
                'cart_tax' => (float) $cart->tax,
                'cart_total' => (float) $cart->total,
                'coupon' => $cart->coupon ? $this->formatCouponData($cart->coupon, $cart) : null,
            ]);
        }

        return back()->with('success', 'Coupon removed.');
    }

    /**
     * One envelope for every refusal these endpoints hand back.
     *
     * A business failure answered with {error: "..."} while the framework's own
     * failure on the same endpoint answered with {message, errors} - two
     * dialects out of one URL. Every consumer picked one and read only that, so
     * `error` won and a real validation message arrived as an undefined key and
     * collapsed into a hardcoded string: "Failed to add to cart", "Failed to
     * update", "Invalid coupon" - none of them the reason. Each refusal now
     * carries all three keys, saying the same sentence three ways so no reader
     * can miss it:
     *
     *   error   - what the drawer and the cart page have read since they were
     *             written, kept so this stays a fix and not a breaking change;
     *   message - the key window.kkApiError() reads, and the framework's own;
     *   errors  - the {field: [messages]} map, so the message can be put under
     *             the input it belongs to instead of floating in a toast.
     *
     * A browser form post is unchanged: it still redirects back with the
     * sentence flashed as `error`.
     *
     * @param  array<string, mixed>  $extra  Machine-readable context (available
     *                                       stock, quantity already in the cart)
     *                                       the page uses to adjust its inputs.
     */
    protected function failed(Request $request, string $message, ?string $field = null, array $extra = []): JsonResponse|RedirectResponse
    {
        if (! $request->wantsJson()) {
            return back()->with('error', $message);
        }

        $body = [
            'error' => $message,
            'message' => $message,
        ];

        if ($field !== null) {
            // Wrapped in an array because that is what Laravel sends per field
            // and what every reader unwraps; a bare string is read a character
            // at a time.
            $body['errors'] = [$field => [$message]];
        }

        return response()->json($body + $extra, 422);
    }

    /**
     * A rule failure, in the same envelope as everything else.
     *
     * ValidationException's JSON body has no `error` key, which is the one key
     * the cart drawer and the cart page read - so the endpoint's most precise
     * messages ("The code field must not contain HTML.") were the only ones
     * that never reached the shopper. Re-emitting them here costs nothing: the
     * first message is what the framework itself puts in `message`, and the
     * full per-field map still travels under `errors` for anything that renders
     * inline.
     */
    protected function invalid(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        $errors = $validator->errors();

        return response()->json([
            'error' => $errors->first(),
            'message' => $errors->first(),
            'errors' => $errors->messages(),
        ], 422);
    }

    protected function formatCouponData(Coupon $coupon, Cart $cart): array
    {
        $data = [
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'auto_apply' => $coupon->auto_apply,
        ];

        if ($coupon->type === 'buy_x_get_y' && $coupon->conditions) {
            $data['buy_qty'] = (int) ($coupon->conditions['buy_qty'] ?? 0);
            $data['get_qty'] = (int) ($coupon->conditions['get_qty'] ?? 0);
        }

        return $data;
    }

    public function recommendations(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->load('items');
        $productIds = $cart->items->pluck('product_id')->toArray();

        if (empty($productIds)) {
            return response()->json(['products' => []]);
        }

        $categoryIds = Product::whereIn('id', $productIds)->pluck('category_id')->unique()->toArray();

        $products = Product::where('is_active', true)
            ->whereNotIn('id', $productIds)
            ->whereIn('category_id', $categoryIds)
            ->whereHas('images')
            ->with('images')
            // Filtered, not merely sorted: every tile in the drawer is a bare
            // "Add to Cart" with no room for an Out of Stock badge, so a sold-out
            // one is a button that can only ever return an error toast. The cart
            // PAGE keeps inStockFirst() instead - it renders full product cards,
            // which do carry the badge.
            ->inStock()
            ->inRandomOrder()
            ->take(6)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'image' => $p->primary_image_url,
                'url' => route('product.show', $p),
            ]);

        return response()->json(['products' => $products]);
    }

    protected function getOrCreateCart(): Cart
    {
        if (auth()->check()) {
            $cart = Cart::firstOrCreate(
                ['user_id' => auth()->id()],
                ['session_id' => null]
            );
        } else {
            $cart = Cart::firstOrCreate(
                ['session_id' => session()->getId()],
                ['user_id' => null]
            );
        }

        // Remove orphaned items whose product was deleted - otherwise the cart
        // page crashes on route('product.show', null) / null property reads.
        //
        // Recalculate when that actually removed something. The stored subtotal
        // and total were worked out with those lines still in them, and nothing
        // else recomputes them, so the cart went on reporting the old money
        // beside an empty basket: /cart/data answered items: [], cart_count: 0
        // and cart_total: 500 in the same breath, and checkout reads the same
        // stored total. delete() returns the row count, so the common path -
        // nothing orphaned - costs one query and no recalculation.
        if ($cart->items()->whereDoesntHave('product')->delete() > 0) {
            $cart->recalculate();
        }

        return $cart;
    }
}
