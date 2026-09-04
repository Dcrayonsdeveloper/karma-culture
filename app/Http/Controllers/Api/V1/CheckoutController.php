<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderPlaced;
use App\Exceptions\InsufficientStockException;
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
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $cart = Cart::where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'coupon'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return $this->failed('Cart is empty.');
        }

        $outOfStock = [];
        $fieldErrors = [];

        foreach ($cart->items as $index => $item) {
            // Null-safe on both relations. A cart line outlives the rows it
            // points at - products are soft-deleted, variants are deleted
            // outright - so reading ->stock_quantity straight off a missing
            // relation turned one stale line into a 500 for the whole checkout,
            // at the exact moment the customer is trying to pay. A line whose
            // stock row has gone has nothing left to sell, which is what zero
            // means here, and it is reported as such per line.
            $available = (int) ($item->variant_id
                ? ($item->variant?->stock_quantity ?? 0)
                : ($item->product?->stock_quantity ?? 0));

            if ($available < $item->quantity) {
                $outOfStock[] = [
                    'product' => $item->product?->name,
                    'requested' => $item->quantity,
                    'available' => $available,
                ];

                $fieldErrors["items.{$index}.quantity"] = [$this->stockMessage($item->product?->name, $available)];
            }
        }

        if (! empty($outOfStock)) {
            return response()->json([
                'success' => false,
                'message' => 'Some items are out of stock.',
                // `errors` is Laravel's {field: [messages]} map everywhere else
                // in this API - including on the 422s the framework itself
                // writes for these very routes. Putting a LIST OF OBJECTS under
                // that key meant a client unwrapping errors the normal way read
                // "0" and "1" as field names and each item object as a message,
                // so the one endpoint that knew exactly which line was short
                // rendered nothing legible. The map is a real map now, keyed by
                // the cart line the message belongs to, and the structured
                // detail a client may want to lay out itself keeps its own key.
                'errors' => $fieldErrors,
                'out_of_stock_items' => $outOfStock,
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
        // Both address rules are scoped to the caller. Unscoped, `exists` asked
        // only whether the row is in the table, so ANY address id - a stranger's
        // - satisfied it, and ownership was left to the findOrFail below, which
        // answers with a 404 whose body carries the model class name and the id
        // that was sent. Deciding it here makes somebody else's address an
        // ordinary field error on the field it arrived in, and tells the caller
        // nothing about whether that id exists at all.
        $ownAddress = fn () => Rule::exists('user_addresses', 'id')->where('user_id', auth()->id());

        $validated = $request->validate([
            'shipping_address_id' => ['required', 'integer', $ownAddress()],
            'billing_address_id' => ['nullable', 'integer', $ownAddress()],
            'payment_method' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = Cart::where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'coupon'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return $this->failed('Cart is empty.');
        }

        // COD fraud prevention
        if ($validated['payment_method'] === 'cod') {
            $codToday = Order::where('user_id', auth()->id())
                ->whereDate('created_at', today())
                ->whereJsonContains('metadata->payment_method', 'cod')
                ->count();
            $codLimit = (int) \App\Models\Setting::get('cod_daily_limit', 3);
            if ($codToday >= $codLimit) {
                $message = "Maximum {$codLimit} COD orders per day. Please use online payment.";

                // Keyed to payment_method: this is not a verdict on the order,
                // it is a verdict on the one field the customer can change to
                // get past it, so the client can put the sentence under the
                // payment picker instead of floating it over the page.
                return $this->failed($message, ['payment_method' => $message]);
            }

            $codMaxAmount = (float) \App\Models\Setting::get('cod_max_amount', 5000);
            $orderTotal = $cart->subtotal - $cart->discount;
            if ($codMaxAmount > 0 && $orderTotal > $codMaxAmount) {
                $message = 'COD is not available for orders above ₹' . number_format($codMaxAmount) . '.';

                return $this->failed($message, ['payment_method' => $message]);
            }
        }

        // first(), not findOrFail(): the rules above already settled both
        // existence and ownership, so a miss here can only be a race with the
        // customer deleting the address in another tab. That deserves the same
        // field message as any other unusable address id rather than a
        // ModelNotFoundException, which renders as a 404 naming the model class
        // and the id - and which, on the Place Order button, reads to the
        // customer as the order having failed for no stated reason.
        $shippingAddress = UserAddress::where('user_id', auth()->id())
            ->whereKey($validated['shipping_address_id'])
            ->first();

        if (! $shippingAddress) {
            $message = 'That delivery address is no longer available.';

            return $this->failed($message, ['shipping_address_id' => $message]);
        }

        // Null-coalesced, because billing_address_id is optional and an absent
        // optional key is simply not IN the validated array. Reading it directly
        // raised an undefined-key error, which left the customer with a bare 500
        // "Server Error" at the moment they pressed Place Order - the one place
        // an unexplained failure costs an order - for the entirely ordinary case
        // of billing being the same as shipping.
        $billingAddressId = $validated['billing_address_id'] ?? null;
        $billingAddress = $shippingAddress;

        if ($billingAddressId) {
            $billingAddress = UserAddress::where('user_id', auth()->id())
                ->whereKey($billingAddressId)
                ->first();

            if (! $billingAddress) {
                $message = 'That billing address is no longer available.';

                return $this->failed($message, ['billing_address_id' => $message]);
            }
        }

        // Stock validation. Null-safe for the same reason validate() is: a cart
        // line whose variant row has been deleted must not be able to turn the
        // whole checkout into a 500 - it has nothing left to sell, and that is
        // exactly what the customer needs told.
        foreach ($cart->items as $index => $item) {
            $available = (int) ($item->variant_id
                ? ($item->variant?->stock_quantity ?? 0)
                : ($item->product?->stock_quantity ?? 0));

            if ($available < $item->quantity) {
                $message = $this->stockMessage($item->product?->name, $available);

                return $this->failed($message, ["items.{$index}.quantity" => $message]);
            }
        }

        // Re-validate coupon before entering transaction
        if ($cart->coupon) {
            if (! $cart->coupon->isValid() || ! $cart->coupon->canBeUsedBy($request->user())) {
                $cart->update(['coupon_id' => null, 'discount' => 0]);

                return $this->failed('Your coupon is no longer valid and has been removed.');
            }
        }

        // Set by the locked stock re-check inside the transaction, so the catch
        // below can still say WHICH line was short. The exception itself cannot
        // carry it: it is shared with the web checkout, which has no such field
        // names to report against.
        $stockField = null;

        try {
            $order = DB::transaction(function () use ($cart, $shippingAddress, $billingAddress, $validated, $request, &$stockField) {
                // Lock coupon row to prevent concurrent over-redemption
                $lockedCoupon = null;
                if ($cart->coupon_id) {
                    $lockedCoupon = Coupon::lockForUpdate()->find($cart->coupon_id);
                    if (! $lockedCoupon || ! $lockedCoupon->isValid() || ! $lockedCoupon->canBeUsedBy($request->user())) {
                        throw new \RuntimeException('COUPON_INVALID');
                    }
                }

                // Re-validate stock with pessimistic locking
                foreach ($cart->items as $index => $item) {
                    if ($item->variant_id) {
                        $locked = ProductVariant::lockForUpdate()->find($item->variant_id);
                    } else {
                        $locked = Product::lockForUpdate()->find($item->product_id);
                    }
                    // Null-safe, because the row this line points at can have
                    // been deleted since the cart was filled, and reading a
                    // property off the missing model aborted the order with a
                    // 500 rather than the sentence that says which item to
                    // remove.
                    $available = (int) ($locked?->stock_quantity ?? 0);

                    if ($available < $item->quantity) {
                        // The message is composed HERE, from the product name
                        // and the count, and thrown as the finished sentence.
                        // It used to be thrown as a sentinel - "STOCK:{name}:
                        // {available}" - and taken apart with explode() on the
                        // way out, which mangled every product whose own name
                        // contains a colon: "Kurta: Indigo Block Print" came
                        // back to the customer as '"Kurta" only has Indigo
                        // Block Print item(s) in stock.' Encoding data into an
                        // exception message and parsing it back out is what
                        // produced that; nothing needs encoding if the message
                        // is already the thing we mean to say.
                        $stockField = "items.{$index}.quantity";

                        throw new InsufficientStockException(
                            $this->stockMessage($item->product?->name, $available)
                        );
                    }
                }

                $order = Order::create([
                    'user_id' => auth()->id(),
                    // Created pending and confirmed below, the same way web
                    // checkout does it. Writing "confirmed" straight in skipped
                    // both confirmed_at and the status-history row, so an API
                    // order showed a confirmed badge over an empty timeline -
                    // and marked a prepaid order confirmed before anyone had
                    // paid for it.
                    'status' => 'pending',
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
        } catch (InsufficientStockException $e) {
            // Caught before the generic RuntimeException below because it is
            // one. Its message is already the customer's sentence, written at
            // the throw site where the product and the count were in hand, so
            // there is nothing to decode and nothing that can be mangled by a
            // product name - and no internal payload that could reach a reader
            // if the decoding ever failed.
            return $this->failed(
                $e->getMessage(),
                $stockField ? [$stockField => $e->getMessage()] : []
            );
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'COUPON_INVALID') {
                $cart->update(['coupon_id' => null, 'discount' => 0]);

                return $this->failed('Your coupon is no longer valid.');
            }

            throw $e;
        }

        // COD has no gateway to wait on, so placement is confirmation. Prepaid
        // orders stay pending until their payment callback lands.
        if ($validated['payment_method'] === 'cod') {
            $order->updateStatus('confirmed', null, 'Order placed (Cash on Delivery)');
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

    /**
     * One body for every failure these two endpoints report.
     *
     * Checkout used to answer a broken rule with {success, message} and nothing
     * else, while the framework's own 422 on the same routes is {message,
     * errors} - so a client had to guess which kind of 422 it was holding, and
     * a message that belonged under an input had nowhere to go but a banner or
     * a toast. Every failure now carries `message` for the sentence and, when
     * the failure belongs to something the caller sent, `errors` as the
     * {field: [messages]} map the framework uses. `success` stays because these
     * endpoints have always promised it.
     *
     * @param  array<string, string>  $fields
     */
    protected function failed(string $message, array $fields = [], int $status = 422): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($fields)) {
            // Laravel sends an ARRAY of messages per field and every client
            // unwraps the first one, so a single sentence is wrapped rather
            // than sent bare.
            $body['errors'] = array_map(fn (string $text) => [$text], $fields);
        }

        return response()->json($body, $status);
    }

    /**
     * The one wording for "there is not enough of this to sell you".
     *
     * Adding to the cart said "Insufficient stock available" and checking out
     * said '"X" only has N item(s) in stock.', so one rule spoke with two
     * voices about a single fact. This is the wording that names the product
     * and the count, used by every stock refusal on the way to an order.
     *
     * Deliberately identical to Api\V1\Cart\CartController::stockMessage(). The
     * two have no shared home to live in yet, so they must be changed together.
     */
    protected function stockMessage(?string $product, int $available): string
    {
        $name = trim((string) $product);
        $subject = $name !== '' ? "\"{$name}\"" : 'This item';

        return $available > 0
            ? "{$subject} only has {$available} item(s) in stock."
            : "{$subject} is out of stock.";
    }
}
