<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.primaryImage', 'items.variant']);

        // "You May Also Like" — products related to the cart's items (else popular).
        $recommended = $this->recommendedForCart($cart);

        return view('cart.index', compact('cart', 'recommended'));
    }

    /**
     * Recommended products for the cart page — same category as cart items,
     * topped up with other active products so the section always shows 4.
     */
    private function recommendedForCart(Cart $cart): Collection
    {
        $productIds = $cart->items->pluck('product_id')->filter()->unique()->all();
        $with = ['category', 'brand', 'primaryImage', 'images'];

        $query = Product::where('is_active', true)->with($with)->whereHas('images');
        if (! empty($productIds)) {
            $categoryIds = Product::whereIn('id', $productIds)->pluck('category_id')->unique()->filter()->all();
            $query->whereNotIn('id', $productIds)
                ->when(! empty($categoryIds), fn ($q) => $q->whereIn('category_id', $categoryIds));
        }
        $recommended = $query->inRandomOrder()->take(4)->get();

        if ($recommended->count() < 4) {
            $exclude = $recommended->pluck('id')->merge($productIds)->all();
            $recommended = $recommended->concat(
                Product::where('is_active', true)
                    ->whereNotIn('id', $exclude)
                    ->whereHas('images')
                    ->with($with)
                    ->inRandomOrder()
                    ->take(4 - $recommended->count())
                    ->get()
            );
        }

        return collect($recommended);
    }

    public function data(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.primaryImage', 'items.variant']);

        $items = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'size' => $item->size,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'product_name' => $item->product->name ?? '',
                'variant_name' => $item->variant->name ?? null,
                'image' => $item->product->primaryImage->first()?->url,
                'slug' => $item->product->slug ?? '',
            ];
        });

        return response()->json([
            'items' => $items,
            'cart_count' => $cart->items->sum('quantity'),
            'subtotal' => (float) $cart->subtotal,
            'discount' => (float) $cart->discount,
            'total' => (float) $cart->total,
        ]);
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'size' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Check stock
        $variantId = $validated['variant_id'] ?? null;
        $size = $validated['size'] ?? null;
        $stockQuantity = $variantId
            ? $product->variants()->find($variantId)->stock_quantity
            : $product->stock_quantity;

        if ($stockQuantity < $validated['quantity']) {
            $error = $stockQuantity > 0
                ? "Only {$stockQuantity} item(s) available in stock."
                : 'This item is currently out of stock.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $error, 'available' => $stockQuantity], 422);
            }

            return back()->with('error', $error);
        }

        $cart = $this->getOrCreateCart();

        // Check if item already in cart (same product + variant + size = same line)
        $existingItem = $cart->items()
            ->where('product_id', $validated['product_id'])
            ->where('variant_id', $variantId)
            ->where('size', $size)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $validated['quantity'];
            if ($newQuantity > $stockQuantity) {
                $inCart = $existingItem->quantity;
                $canAdd = $stockQuantity - $inCart;
                $error = $canAdd > 0
                    ? "You already have {$inCart} in your cart. You can add up to {$canAdd} more."
                    : "You already have all {$stockQuantity} available item(s) in your cart.";
                if ($request->wantsJson()) {
                    return response()->json(['error' => $error, 'available' => $stockQuantity, 'in_cart' => $inCart], 422);
                }

                return back()->with('error', $error);
            }
            $existingItem->update(['quantity' => $newQuantity]);
        } else {
            $price = $variantId
                ? $product->variants()->find($variantId)->price ?? $product->price
                : $product->price;

            $cart->items()->create([
                'product_id' => $validated['product_id'],
                'variant_id' => $variantId,
                'size' => $size,
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
                'cart_total' => $cart->total,
            ]);
        }

        return back()->with('success', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        // Verify cart ownership
        $cart = $this->getOrCreateCart();
        abort_if($cartItem->cart_id !== $cart->id, 403);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Check stock
        $stockQuantity = $cartItem->variant_id
            ? $cartItem->variant->stock_quantity
            : $cartItem->product->stock_quantity;

        if ($validated['quantity'] > $stockQuantity) {
            $error = $stockQuantity > 0
                ? "Only {$stockQuantity} item(s) available in stock."
                : 'This item is currently out of stock.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $error, 'available' => $stockQuantity], 422);
            }

            return back()->with('error', $error);
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
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $cart = $this->getOrCreateCart();
        $cart->load(['items.product', 'coupon']);

        // Prevent stacking — if a coupon is already applied, reject
        if ($cart->coupon_id) {
            $message = 'A coupon is already applied. Remove it first to apply a different one.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $coupon = Coupon::where('code', strtoupper($validated['code']))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->first();

        if (! $coupon) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Invalid or expired coupon code'], 422);
            }

            return back()->with('error', 'Invalid or expired coupon code.');
        }

        // Check minimum order amount (not for BOGO — BOGO checks quantity instead)
        if ($coupon->type !== 'buy_x_get_y' && $coupon->min_order_amount && $cart->subtotal < $coupon->min_order_amount) {
            $message = 'This coupon requires a minimum order of '.format_price($coupon->min_order_amount);
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // Check global usage limit
        if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'This coupon has reached its usage limit'], 422);
            }

            return back()->with('error', 'This coupon has reached its usage limit.');
        }

        // Check per-user usage limit
        if (auth()->check() && $coupon->usage_per_user) {
            $userUsage = Order::where('user_id', auth()->id())
                ->where('coupon_id', $coupon->id)
                ->count();
            if ($userUsage >= $coupon->usage_per_user) {
                $message = 'You have already used this coupon the maximum number of times.';
                if ($request->wantsJson()) {
                    return response()->json(['error' => $message], 422);
                }

                return back()->with('error', $message);
            }
        }

        // Calculate discount using the model
        $discount = $coupon->calculateDiscount((float) $cart->subtotal, $cart->items);

        if ($discount <= 0 && $coupon->type !== 'free_shipping') {
            $message = $coupon->type === 'buy_x_get_y'
                ? 'Your cart does not meet the quantity requirements for this offer.'
                : 'This coupon cannot be applied to your cart.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }

            return back()->with('error', $message);
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
        $cart->recalculate(skipAutoApply: true);
        $cart->refresh();
        $cart->load('coupon');

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Coupon removed',
                'cart_subtotal' => (float) $cart->subtotal,
                'cart_discount' => (float) $cart->discount,
                'cart_total' => (float) $cart->total,
                'coupon' => $cart->coupon ? $this->formatCouponData($cart->coupon, $cart) : null,
            ]);
        }

        return back()->with('success', 'Coupon removed.');
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
            ->with('primaryImage')
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

        // Remove orphaned items whose product was deleted — otherwise the cart
        // page crashes on route('product.show', null) / null property reads.
        $cart->items()->whereDoesntHave('product')->delete();

        return $cart;
    }
}
