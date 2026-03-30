<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $cart = Cart::where('user_id', auth()->id())->with(['items.product', 'items.variant'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $addresses = UserAddress::where('user_id', auth()->id())->get();
        $defaultAddress = $addresses->where('is_default', true)->first() ?? $addresses->first();

        $paymentSettings = Setting::where('group', 'payment')->pluck('value', 'key');

        return view('checkout.index', compact('cart', 'addresses', 'defaultAddress', 'paymentSettings'));
    }

    public function process(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => ['required', 'exists:user_addresses,id'],
            'billing_address_id' => ['nullable', 'exists:user_addresses,id'],
            'same_billing_address' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = Cart::where('user_id', auth()->id())->with(['items.product', 'items.variant', 'coupon'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // COD fraud prevention: limit COD orders per user per day
        if ($validated['payment_method'] === 'cod') {
            $codToday = Order::where('user_id', auth()->id())
                ->whereDate('created_at', today())
                ->whereJsonContains('metadata->payment_method', 'cod')
                ->count();
            $codLimit = (int) Setting::get('cod_daily_limit', 3);
            if ($codToday >= $codLimit) {
                return redirect()->route('checkout.index')
                    ->with('error', "You can place a maximum of {$codLimit} COD orders per day. Please use an online payment method.");
            }

            $codMaxAmount = (float) Setting::get('cod_max_amount', 5000);
            $orderTotal = $cart->subtotal - $cart->discount;
            if ($codMaxAmount > 0 && $orderTotal > $codMaxAmount) {
                return redirect()->route('checkout.index')
                    ->with('error', "COD is not available for orders above ₹" . number_format($codMaxAmount) . ". Please use an online payment method.");
            }
        }

        // Re-validate coupon at checkout time — it may have expired or been deactivated since it was applied
        if ($cart->coupon) {
            if (! $cart->coupon->isValid() || ! $cart->coupon->canBeUsedBy($request->user())) {
                $cart->update(['coupon_id' => null, 'discount' => 0, 'total' => $cart->subtotal]);
                return redirect()->route('checkout.index')
                    ->with('error', 'Your coupon "' . $cart->coupon->code . '" has expired or is no longer valid. Please review your order.');
            }
        }

        // Validate address belongs to authenticated user
        $shippingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($validated['shipping_address_id']);
        $billingAddressId = $validated['same_billing_address']
            ? $shippingAddress->id
            : ($validated['billing_address_id'] ?? $shippingAddress->id);
        $billingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($billingAddressId);

        try {
            $order = DB::transaction(function () use ($cart, $shippingAddress, $billingAddress, $validated, $request) {
                // Lock the coupon row first to prevent concurrent over-redemption
                $lockedCoupon = null;
                if ($cart->coupon_id) {
                    $lockedCoupon = Coupon::lockForUpdate()->find($cart->coupon_id);
                    if (! $lockedCoupon || ! $lockedCoupon->isValid() || ! $lockedCoupon->canBeUsedBy($request->user())) {
                        throw new \RuntimeException('COUPON_INVALID');
                    }
                }

                // Re-validate stock INSIDE transaction with pessimistic locking
                foreach ($cart->items as $item) {
                    if ($item->variant_id) {
                        $locked = \App\Models\ProductVariant::lockForUpdate()->find($item->variant_id);
                        $available = $locked->stock_quantity;
                    } else {
                        $locked = \App\Models\Product::lockForUpdate()->find($item->product_id);
                        $available = $locked->stock_quantity;
                    }

                    if ($available < $item->quantity) {
                        throw new \App\Exceptions\InsufficientStockException(
                            "\"{$item->product->name}\" only has {$available} item(s) in stock. Please update your cart."
                        );
                    }
                }

                // Create order
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'status' => 'confirmed',
                    'payment_status' => 'pending',
                    'subtotal' => $cart->subtotal,
                    'discount' => $cart->discount,
                    'shipping_cost' => 0,
                    'tax' => 0,
                    'total' => $cart->subtotal - $cart->discount,
                    'coupon_id' => $cart->coupon_id,
                    'shipping_address_id' => $shippingAddress->id,
                    'billing_address_id' => $billingAddress->id,
                    'shipping_address_snapshot' => [
                        'name' => $shippingAddress->full_name,
                        'phone' => $shippingAddress->phone,
                        'address_line_1' => $shippingAddress->address_line_1,
                        'address_line_2' => $shippingAddress->address_line_2,
                        'city' => $shippingAddress->city,
                        'state' => $shippingAddress->state,
                        'postal_code' => $shippingAddress->postal_code,
                        'country' => $shippingAddress->country,
                    ],
                    'billing_address_snapshot' => [
                        'name' => $billingAddress->full_name,
                        'address_line_1' => $billingAddress->address_line_1,
                        'city' => $billingAddress->city,
                        'state' => $billingAddress->state,
                        'postal_code' => $billingAddress->postal_code,
                        'country' => $billingAddress->country,
                    ],
                    'notes' => strip_tags($validated['notes'] ?? ''),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent(), 0, 500),
                    'metadata' => ['payment_method' => $validated['payment_method']],
                ]);

                // Create order items and decrement stock atomically
                foreach ($cart->items as $item) {
                    // Re-read price from product to prevent price tampering
                    $currentPrice = $item->variant_id
                        ? ($item->variant->price ?? $item->product->price)
                        : $item->product->price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'seller_id' => $item->product->seller_id,
                        'product_name' => $item->product->name,
                        'sku' => $item->product->sku ?? '',
                        'variant_name' => $item->variant?->attributeValues->pluck('value')->join(' / '),
                        'quantity' => $item->quantity,
                        'mrp' => $item->product->mrp ?? $currentPrice,
                        'price' => $currentPrice,
                        'tax' => 0,
                        'discount' => 0,
                        'total' => $currentPrice * $item->quantity,
                    ]);

                    // Update product stock with locking
                    if ($item->variant_id) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    } else {
                        $item->product->decrement('stock_quantity', $item->quantity);
                    }

                    $item->product->increment('sales_count', $item->quantity);
                }

                // Update coupon usage (locked coupon row prevents race conditions)
                if ($lockedCoupon) {
                    $lockedCoupon->increment('times_used');
                    CouponUsage::create([
                        'coupon_id'       => $lockedCoupon->id,
                        'user_id'         => $request->user()->id,
                        'order_id'        => $order->id,
                        'discount_amount' => $cart->discount,
                    ]);
                }

                // Clear cart
                $cart->items()->delete();
                $cart->update(['coupon_id' => null, 'discount' => 0]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'COUPON_INVALID') {
                $cart->update(['coupon_id' => null, 'discount' => 0]);
                return redirect()->route('checkout.index')
                    ->with('error', 'Your coupon is no longer valid and has been removed. Please review your order.');
            }
            throw $e;
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        OrderPlaced::dispatch($order, 'web');

        // If PayU payment, redirect to PayU gateway
        if ($validated['payment_method'] === 'payu') {
            return redirect()->route('payu.initiate', $order);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load(['items.product']);

        return view('checkout.success', compact('order'));
    }

    public function failed(): View
    {
        return view('checkout.failed');
    }
}
