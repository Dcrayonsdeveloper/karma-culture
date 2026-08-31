<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Resolve the active cart for the current visitor.
     * Logged-in users are matched by user_id; guests by their session id
     * (same scheme CartController uses to build the cart).
     */
    private function currentCart(array $with = []): ?Cart
    {
        $query = Cart::query()->with($with);

        return auth()->check()
            ? $query->where('user_id', auth()->id())->first()
            : $query->where('session_id', session()->getId())->first();
    }

    /**
     * Which payment methods can currently be offered.
     * Online needs the PayU toggle plus both credentials; COD follows its
     * admin toggle but is forced on when online is unavailable, so checkout
     * can never end up with no way to pay.
     */
    public static function availablePaymentMethods(): array
    {
        $onlineReady = Setting::get('payu_enabled', '0') === '1'
            && Setting::get('payu_merchant_key', '') !== ''
            && Setting::get('payu_merchant_salt', '') !== '';
        $codEnabled = Setting::get('cod_enabled', '1') === '1' || ! $onlineReady;

        return array_keys(array_filter(['cod' => $codEnabled, 'online' => $onlineReady]));
    }

    public function index(): View|RedirectResponse
    {
        $cart = $this->currentCart(['items.product', 'items.variant', 'coupon']);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return view('checkout.index', [
            'cart'           => $cart,
            'paymentMethods' => self::availablePaymentMethods(),
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name'      => ['required', 'string', 'max:120'],
            'email'          => ['required', 'email', 'max:160'],
            'phone'          => ['required', 'regex:/^[6-9]\d{9}$/'],
            'address_line_1' => ['required', 'string', 'max:200'],
            'address_line_2' => ['nullable', 'string', 'max:200'],
            'city'           => ['required', 'string', 'max:80'],
            'state'          => ['required', 'string', 'max:80'],
            'postal_code'    => ['required', 'regex:/^\d{6}$/'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:'.implode(',', self::availablePaymentMethods())],
        ], [
            'phone.regex'       => 'Please enter a valid 10-digit mobile number.',
            'postal_code.regex' => 'Please enter a valid 6-digit PIN code.',
            'payment_method.in' => 'Please choose an available payment method.',
        ]);

        $cart = $this->currentCart(['items.product', 'items.variant', 'coupon']);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Drop an applied coupon if it is no longer valid (guests don't get
        // per-user checks, but expired/disabled coupons must not be honoured).
        if ($cart->coupon && ! $cart->coupon->isValid()) {
            $cart->update(['coupon_id' => null, 'discount' => 0, 'total' => $cart->subtotal]);
            return redirect()->route('checkout.index')
                ->with('error', 'Your coupon "' . $cart->coupon->code . '" is no longer valid and has been removed. Please review your order.');
        }

        $addressSnapshot = [
            'name'           => $validated['full_name'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'],
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city'           => $validated['city'],
            'state'          => $validated['state'],
            'postal_code'    => $validated['postal_code'],
            'country'        => 'IN',
        ];

        try {
            $order = DB::transaction(function () use ($cart, $addressSnapshot, $validated, $request) {
                // Re-validate stock inside the transaction with row locks.
                foreach ($cart->items as $item) {
                    $locked = $item->variant_id
                        ? \App\Models\ProductVariant::lockForUpdate()->find($item->variant_id)
                        : \App\Models\Product::lockForUpdate()->find($item->product_id);
                    $available = $locked->stock_quantity ?? 0;

                    if ($available < $item->quantity) {
                        throw new \App\Exceptions\InsufficientStockException(
                            "\"{$item->product->name}\" only has {$available} item(s) in stock. Please update your cart."
                        );
                    }
                }

                // Create the order. user_id is null for guests (column is nullable);
                // the guest's contact + address live in the snapshot and metadata.
                $order = Order::create([
                    'user_id'                  => auth()->id(),
                    'status'                   => 'pending',
                    'payment_status'           => 'pending',
                    'subtotal'                 => $cart->subtotal,
                    'discount'                 => $cart->discount,
                    'shipping_cost'            => 0,
                    'tax'                      => 0,
                    'total'                    => $cart->subtotal - $cart->discount,
                    'coupon_id'                => $cart->coupon_id,
                    'shipping_address_id'      => null,
                    'billing_address_id'       => null,
                    'shipping_address_snapshot' => $addressSnapshot,
                    'billing_address_snapshot'  => $addressSnapshot,
                    'notes'                    => strip_tags($validated['notes'] ?? ''),
                    'ip_address'               => $request->ip(),
                    'user_agent'               => substr((string) $request->userAgent(), 0, 500),
                    'source'                   => 'web',
                    'metadata'                 => [
                        'guest_email'     => $validated['email'],
                        'guest_phone'     => $validated['phone'],
                        'guest_checkout'  => ! auth()->check(),
                        'payment_pending' => true,
                        'payment_method'  => $validated['payment_method'],
                    ],
                ]);

                foreach ($cart->items as $item) {
                    // Re-read price from the product to prevent tampering.
                    $currentPrice = $item->variant_id
                        ? ($item->variant->price ?? $item->product->price)
                        : $item->product->price;

                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $item->product_id,
                        'variant_id'   => $item->variant_id,
                        'seller_id'    => $item->product->seller_id,
                        'product_name' => $item->product->name,
                        'sku'          => $item->product->sku ?? '',
                        'variant_name' => $item->variant?->attributeValues->pluck('value')->join(' / '),
                        'size'         => $item->size,
                        'colour'       => $item->colour,
                        'quantity'     => $item->quantity,
                        'mrp'          => $item->product->mrp ?? $currentPrice,
                        'price'        => $currentPrice,
                        'tax'          => 0,
                        'discount'     => 0,
                        'total'        => $currentPrice * $item->quantity,
                    ]);

                    if ($item->variant_id) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    } else {
                        $item->product->decrement('stock_quantity', $item->quantity);
                    }

                    $item->product->increment('sales_count', $item->quantity);
                }

                // Record coupon usage only for logged-in users (CouponUsage is
                // keyed to a user). Guests still receive the cart discount.
                if (auth()->check() && $cart->coupon_id) {
                    $lockedCoupon = Coupon::lockForUpdate()->find($cart->coupon_id);
                    if ($lockedCoupon) {
                        $lockedCoupon->increment('times_used');
                        CouponUsage::create([
                            'coupon_id'       => $lockedCoupon->id,
                            'user_id'         => auth()->id(),
                            'order_id'        => $order->id,
                            'discount_amount' => $cart->discount,
                        ]);
                    }
                }

                // Empty the cart.
                $cart->items()->delete();
                $cart->update(['coupon_id' => null, 'discount' => 0]);

                return $order;
            });
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        // Let a guest view this order's confirmation later (session ownership).
        $recent = session('guest_order_ids', []);
        $recent[] = $order->id;
        session(['guest_order_ids' => array_values(array_slice(array_unique($recent), -10))]);

        OrderPlaced::dispatch($order, 'web');

        // Online payments hand off to PayU (order stays payment_status=pending
        // until the gateway callback confirms). COD goes straight to the
        // confirmation page.
        if ($validated['payment_method'] === 'online') {
            return redirect()->route('payu.initiate', $order);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order): View
    {
        $ownsAsUser  = auth()->check() && $order->user_id === auth()->id();
        $ownsAsGuest = in_array($order->id, session('guest_order_ids', []), true);
        abort_unless($ownsAsUser || $ownsAsGuest, 403);

        $order->load(['items.product']);

        return view('checkout.success', compact('order'));
    }

    public function failed(): View
    {
        return view('checkout.failed');
    }
}
