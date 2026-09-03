<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Support\OfferClaims;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * The states the shipping form offers, and the only values it accepts.
     *
     * The list used to live in an inline php block in checkout/index.blade.php
     * while the server took any string up to 80 characters, so the select was
     * decoration: a direct POST could ship an order to "state=<anything>". It
     * lives here now so the rendered options and the Rule::in that validates
     * them can never drift apart.
     */
    public const STATES = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat',
        'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh',
        'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand',
        'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir', 'Ladakh',
        'Lakshadweep', 'Puducherry',
    ];

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
        // getBool, not === '1'. cod_enabled is seeded with type 'boolean', so
        // the model casts it back to a real bool and the string comparison was
        // always false - Cash on Delivery vanished from checkout the moment
        // PayU was configured, and no amount of toggling it in the admin
        // brought it back.
        $onlineReady = Setting::getBool('payu_enabled', false)
            && Setting::get('payu_merchant_key', '') !== ''
            && Setting::get('payu_merchant_salt', '') !== '';
        $codEnabled = Setting::getBool('cod_enabled', true) || ! $onlineReady;

        return array_keys(array_filter(['cod' => $codEnabled, 'online' => $onlineReady]));
    }

    public function index(): View|RedirectResponse
    {
        $cart = $this->currentCart(['items.product', 'items.variant', 'coupon']);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Bring the stored totals up to date before quoting them.
        //
        // This screen rendered $cart->shipping, $cart->tax and $cart->subtotal
        // straight off the row, and nothing here refreshed them - so a cart
        // built before delivery charging was switched on carried shipping = 0
        // and the summary said FREE however the shipping settings were filled
        // in. Any settings change after the customer last touched their cart
        // had the same effect: prices, tax and delivery all quoted from
        // whenever the cart was last written.
        //
        // The cart page has always done this on load; this is the same rule on
        // the last screen that quotes a total.
        //
        // skipAutoApply follows the same dismissal the cart page honours. With
        // auto-apply on unconditionally, a coupon the shopper had removed was
        // put back the moment they reached this page: the cart quoted one Total
        // Amount, the checkout quoted a smaller one, and because a discount
        // moves the basket against the free-delivery minimum the two screens
        // could disagree about the delivery charge as well. process() already
        // passes skipAutoApply: true for the same reason.
        $cart->recalculate(skipAutoApply: session('coupon_dismissed', false));
        $cart->refresh()->load(['items.product', 'items.variant', 'coupon']);

        $user = request()->user();

        // Both checkout routes are auth-gated, so this is the point at which a
        // claim made by a guest - possibly days ago, on another device - finally
        // has an account to be matched against. It is also the last screen that
        // quotes a total, so the discount has to be settled before it renders.
        $claimedOffer = OfferClaims::applyTo($cart, $user);

        if ($claimedOffer['attached_now']) {
            $cart->refresh()->load(['items.product', 'items.variant', 'coupon']);
        }

        return view('checkout.index', [
            'cart'           => $cart,
            'claimedOffer'   => $claimedOffer,
            'paymentMethods' => self::availablePaymentMethods(),
            // Saved addresses are the whole point of the account Addresses page;
            // without these the customer retypes the same details every order.
            'addresses'      => $user
                ? $user->addresses()->orderBy('is_default', 'desc')->orderBy('id')->get()
                : collect(),
            'prefill'        => $user,
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        // The typed address fields are only mandatory when the customer is not
        // shipping to one of their saved addresses. Prepending this to the
        // shared rule set keeps every charset/length rule in place while
        // swapping 'required' for the conditional form - 'nullable' does not
        // suppress required_without, which is an implicit rule.
        $whenTyped = 'required_without:address_id';

        $validated = $request->validate([
            // Scoped to the signed-in user so a guessed id cannot ship an order
            // to somebody else's saved address.
            'address_id'     => ['nullable', 'integer', Rule::exists('user_addresses', 'id')
                                    ->where('user_id', $request->user()?->id ?? 0)],
            'full_name'      => [$whenTyped, ...V::name(required: false)],
            // Was /^[6-9]\d{9}$/, which rejected the "+91 98765 43210" and
            // "098765-43210" forms customers actually type. IndianMobile strips
            // the decoration and the trunk/country prefix before testing the
            // ten digits, so the client pattern can tolerate them too.
            'phone'          => [$whenTyped, ...V::mobile(required: false)],
            'address_line_1' => [$whenTyped, ...V::addressLine(required: false)],
            'address_line_2' => V::addressLine(required: false),
            'city'           => [$whenTyped, ...V::text(required: false, max: 100)],
            // The <select> was decoration until now: the server took any string.
            'state'          => [$whenTyped, ...V::option(self::STATES, required: false)],
            // Was /^\d{6}$/, which accepted "012345"; no Indian PIN starts at 0.
            'postal_code'    => [$whenTyped, ...V::pincode(required: false)],
            'notes'          => V::textarea(required: false, max: 500),
            'payment_method' => V::option(self::availablePaymentMethods()),
        ], [
            'payment_method.in' => 'Please choose an available payment method.',
            'state.in'          => 'Please choose a state from the list.',
            'postal_code.regex' => 'Please enter a valid 6-digit PIN code.',
        ]);

        // The confirmation address is the account's, not the form's: the box
        // on checkout is a read-only display with no name attribute now, so an
        // 'email' arriving in the POST is ignored rather than trusted. Both
        // checkout routes sit behind the auth middleware (routes/web.php), so
        // there is always a user to read it from.
        $validated['email'] = $request->user()->email;

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

        $savedAddress = ! empty($validated['address_id']) && $request->user()
            ? $request->user()->addresses()->find($validated['address_id'])
            : null;

        $addressSnapshot = $savedAddress ? [
            'name'           => trim($savedAddress->first_name . ' ' . $savedAddress->last_name),
            'email'          => $validated['email'],
            'phone'          => $savedAddress->phone,
            'address_line_1' => $savedAddress->address_line_1,
            'address_line_2' => $savedAddress->address_line_2,
            'city'           => $savedAddress->city,
            'state'          => $savedAddress->state,
            'postal_code'    => $savedAddress->postal_code,
            'country'        => $savedAddress->country ?: 'IN',
        ] : [
            'name'           => $validated['full_name'],
            'email'          => $validated['email'],
            // Store the canonical ten digits, not the decorated input, so the
            // order-tracking lookup and the SMS/Shiprocket handoff all see one
            // shape. IndianMobile has already proved it normalises.
            'phone'          => IndianMobile::normalize($validated['phone'] ?? null),
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city'           => $validated['city'],
            'state'          => $validated['state'],
            'postal_code'    => $validated['postal_code'],
            'country'        => 'IN',
        ];

        try {
            $order = DB::transaction(function () use ($cart, $addressSnapshot, $savedAddress, $validated, $request) {
                // Recompute the money from the live product rows before any of
                // it is copied onto the order. Nothing here has ever been read
                // from the request, but the cart's stored subtotal/discount are
                // only as fresh as the last cart mutation, while the order
                // LINES below re-read each price from the product - so a flash
                // sale that started or ended while the customer sat on this
                // page left orders.subtotal disagreeing with the sum of its own
                // order_items, in whichever direction the timing favoured.
                // skipAutoApply: an order is not the place to attach a coupon
                // the customer was never shown; one already on the cart is
                // still honoured and re-costed.
                $cart->recalculate(skipAutoApply: true);
                $cart->refresh();
                $cart->load(['items.product', 'items.variant', 'coupon']);

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
                    // Was hardcoded to 0 with the total ignoring it, so a
                    // configured delivery charge was shown at checkout (once the
                    // view stopped saying FREE) and then not billed.
                    'shipping_cost'            => $cart->shipping,
                    // Hardcoded to 0 with the total ignoring it, so tax was
                    // worked out on the cart, shown to nobody and billed to
                    // nobody - the same shape the shipping charge was in.
                    'tax'                      => $cart->tax,
                    'total'                    => $cart->subtotal - $cart->discount + $cart->shipping + $cart->tax,
                    'coupon_id'                => $cart->coupon_id,
                    'shipping_address_id'      => $savedAddress?->id,
                    'billing_address_id'       => $savedAddress?->id,
                    'shipping_address_snapshot' => $addressSnapshot,
                    'billing_address_snapshot'  => $addressSnapshot,
                    'notes'                    => strip_tags($validated['notes'] ?? ''),
                    'ip_address'               => $request->ip(),
                    'user_agent'               => substr((string) $request->userAgent(), 0, 500),
                    'source'                   => 'web',
                    'metadata'                 => [
                        'guest_email'     => $validated['email'],
                        // phone is required_without:address_id, so it is absent
                        // from $validated whenever a saved address is chosen -
                        // reading it directly 500'd every saved-address order.
                        // Fall back to the phone on the address snapshot.
                        'guest_phone'     => $validated['phone'] ?? $addressSnapshot['phone'] ?? null,
                        'guest_checkout'  => ! auth()->check(),
                        'payment_pending' => true,
                        'payment_method'  => $validated['payment_method'],
                    ],
                ]);

                foreach ($cart->items as $item) {
                    // The line price, re-derived from the product rows moments
                    // ago by the recalculate() above and never read from the
                    // request. It is taken from the cart line rather than
                    // re-read here because repriceItems() applies the running
                    // flash sale as well as the shelf/variant price: reading
                    // the shelf price directly wrote the UNDISCOUNTED figure
                    // onto order_items while orders.subtotal held the
                    // discounted one, so a flash-sale order's lines did not add
                    // up to its own total.
                    $currentPrice = (float) $item->price;

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

                // Count the redemption for guests too. Checkout is guest-first,
                // so incrementing only for logged-in users left usage_limit
                // unenforceable - a single-use code could be redeemed forever.
                // The per-user CouponUsage row still needs a user_id, so that
                // part stays gated; the global counter does not.
                if ($cart->coupon_id) {
                    $lockedCoupon = Coupon::lockForUpdate()->find($cart->coupon_id);

                    if ($lockedCoupon) {
                        // Re-check the limit under the lock: two concurrent
                        // checkouts could both pass the cart-page check.
                        if ($lockedCoupon->usage_limit && $lockedCoupon->times_used >= $lockedCoupon->usage_limit) {
                            throw new \App\Exceptions\InsufficientStockException(
                                'The coupon "' . $lockedCoupon->code . '" has reached its usage limit. Please review your order.'
                            );
                        }

                        $lockedCoupon->increment('times_used');

                        if (auth()->check()) {
                            CouponUsage::create([
                                'coupon_id'       => $lockedCoupon->id,
                                'user_id'         => auth()->id(),
                                'order_id'        => $order->id,
                                'discount_amount' => $cart->discount,
                            ]);
                        }
                    }
                }

                // Empty the cart.
                $cart->items()->delete();
                $cart->update(['coupon_id' => null, 'discount' => 0]);

                // The flag means "I removed the coupon from THIS basket", and
                // this basket is now an order. Left set it outlives the order
                // and suppresses every coupon - auto-applied and claimed alike -
                // on the customer's next basket for the rest of the session.
                session()->forget('coupon_dismissed');

                return $order;
            });
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        // Let a guest view this order's confirmation later (session ownership).
        $recent = session('guest_order_ids', []);
        $recent[] = $order->id;
        session(['guest_order_ids' => array_values(array_slice(array_unique($recent), -10))]);

        // A COD order has nothing left to wait for - there is no gateway to hear
        // back from - so confirming it is the placement itself. Leaving it
        // "pending" meant every cash order sat unconfirmed until an admin
        // noticed and clicked Confirm, and the customer's own order page said
        // "Pending" next to a payment that was never going to move either.
        // Prepaid orders stay pending until the PayU callback confirms.
        // Dispatched before OrderPlaced so the fraud listener can still put a
        // blocked order on hold on top of this.
        if ($validated['payment_method'] !== 'online') {
            $order->updateStatus('confirmed', null, 'Order placed (Cash on Delivery)');
        }

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
