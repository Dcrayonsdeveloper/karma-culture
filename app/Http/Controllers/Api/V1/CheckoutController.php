<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $cart = Cart::where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'coupon'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.',
            ], 422);
        }

        $errors = [];
        foreach ($cart->items as $item) {
            $available = $item->variant_id
                ? $item->variant->stock_quantity
                : $item->product->stock_quantity;

            if ($available < $item->quantity) {
                $errors[] = [
                    'product' => $item->product->name,
                    'requested' => $item->quantity,
                    'available' => $available,
                ];
            }
        }

        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Some items are out of stock.',
                'errors' => $errors,
            ], 422);
        }

        $addresses = UserAddress::where('user_id', auth()->id())->get();

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => [
                    'items_count' => $cart->items->count(),
                    'subtotal' => (float) $cart->subtotal,
                    'discount' => (float) $cart->discount,
                    'total' => (float) ($cart->subtotal - $cart->discount),
                    'coupon' => $cart->coupon?->code,
                ],
                'addresses' => $addresses,
            ],
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => ['required', 'exists:user_addresses,id'],
            'billing_address_id' => ['nullable', 'exists:user_addresses,id'],
            'payment_method' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = Cart::where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'coupon'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.',
            ], 422);
        }

        // COD fraud prevention
        if ($validated['payment_method'] === 'cod') {
            $codToday = Order::where('user_id', auth()->id())
                ->whereDate('created_at', today())
                ->whereJsonContains('metadata->payment_method', 'cod')
                ->count();
            $codLimit = (int) \App\Models\Setting::get('cod_daily_limit', 3);
            if ($codToday >= $codLimit) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum {$codLimit} COD orders per day. Please use online payment.",
                ], 422);
            }

            $codMaxAmount = (float) \App\Models\Setting::get('cod_max_amount', 5000);
            $orderTotal = $cart->subtotal - $cart->discount;
            if ($codMaxAmount > 0 && $orderTotal > $codMaxAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'COD is not available for orders above ₹' . number_format($codMaxAmount) . '.',
                ], 422);
            }
        }

        $shippingAddress = UserAddress::where('user_id', auth()->id())
            ->findOrFail($validated['shipping_address_id']);
        $billingAddress = $validated['billing_address_id']
            ? UserAddress::where('user_id', auth()->id())->findOrFail($validated['billing_address_id'])
            : $shippingAddress;

        // Stock validation
        foreach ($cart->items as $item) {
            $available = $item->variant_id ? $item->variant->stock_quantity : $item->product->stock_quantity;
            if ($available < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "\"{$item->product->name}\" only has {$available} item(s) in stock.",
                ], 422);
            }
        }

        // Re-validate coupon before entering transaction
        if ($cart->coupon) {
            if (! $cart->coupon->isValid() || ! $cart->coupon->canBeUsedBy($request->user())) {
                $cart->update(['coupon_id' => null, 'discount' => 0]);
                return response()->json([
                    'success' => false,
                    'message' => 'Your coupon is no longer valid and has been removed.',
                ], 422);
            }
        }

        try {
            $order = DB::transaction(function () use ($cart, $shippingAddress, $billingAddress, $validated, $request) {
                // Lock coupon row to prevent concurrent over-redemption
                $lockedCoupon = null;
                if ($cart->coupon_id) {
                    $lockedCoupon = Coupon::lockForUpdate()->find($cart->coupon_id);
                    if (! $lockedCoupon || ! $lockedCoupon->isValid() || ! $lockedCoupon->canBeUsedBy($request->user())) {
                        throw new \RuntimeException('COUPON_INVALID');
                    }
                }

                // Re-validate stock with pessimistic locking
                foreach ($cart->items as $item) {
                    if ($item->variant_id) {
                        $locked = ProductVariant::lockForUpdate()->find($item->variant_id);
                    } else {
                        $locked = Product::lockForUpdate()->find($item->product_id);
                    }
                    $available = $locked->stock_quantity;

                    if ($available < $item->quantity) {
                        throw new \RuntimeException("STOCK:{$item->product->name}:{$available}");
                    }
                }

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
                    'metadata' => ['payment_method' => $validated['payment_method'], 'source' => 'api'],
                ]);

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

                    if ($item->variant_id) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    } else {
                        $item->product->decrement('stock_quantity', $item->quantity);
                    }
                    $item->product->increment('sales_count', $item->quantity);
                }

                // Update coupon usage with locked row
                if ($lockedCoupon) {
                    $lockedCoupon->increment('times_used');
                    CouponUsage::create([
                        'coupon_id'       => $lockedCoupon->id,
                        'user_id'         => $request->user()->id,
                        'order_id'        => $order->id,
                        'discount_amount' => $cart->discount,
                    ]);
                }

                $cart->items()->delete();
                $cart->update(['coupon_id' => null, 'discount' => 0]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'COUPON_INVALID') {
                $cart->update(['coupon_id' => null, 'discount' => 0]);
                return response()->json(['success' => false, 'message' => 'Your coupon is no longer valid.'], 422);
            }
            if (str_starts_with($e->getMessage(), 'STOCK:')) {
                [, $name, $available] = explode(':', $e->getMessage(), 3);
                return response()->json(['success' => false, 'message' => "\"{$name}\" only has {$available} item(s) in stock."], 422);
            }
            throw $e;
        }

        OrderPlaced::dispatch($order, 'api');

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'status' => $order->status,
            ],
        ], 201);
    }
}
