# Fast Checkout System

## [AI-REF] Amazon-Inspired Checkout Experience

---

## Overview

The checkout system is designed for speed and simplicity, inspired by Amazon's one-click checkout. Goals:
- Complete checkout in under 30 seconds for returning users
- Minimize form fields and clicks
- Provide real-time validation
- Support multiple payment methods
- Handle edge cases gracefully

---

## Checkout Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         Cart Page                                │
│  • Review items                                                  │
│  • Apply coupon                                                  │
│  • View totals                                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Checkout Page                               │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Step 1: Shipping Address (auto-selected for returning) │    │
│  │  • Saved addresses (select)                             │    │
│  │  • Add new address (form)                               │    │
│  └─────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Step 2: Shipping Method                                │    │
│  │  • Standard (free)                                      │    │
│  │  • Express (₹99)                                        │    │
│  └─────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Step 3: Payment Method                                 │    │
│  │  • Saved cards                                          │    │
│  │  • UPI                                                  │    │
│  │  • Net banking                                          │    │
│  │  • Wallets                                              │    │
│  │  • COD                                                  │    │
│  └─────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Order Summary (sticky on desktop)                      │    │
│  │  • Items                                                │    │
│  │  • Subtotal                                             │    │
│  │  • Discount                                             │    │
│  │  • Shipping                                             │    │
│  │  • Tax                                                  │    │
│  │  • Total                                                │    │
│  │  [Place Order - ₹X,XXX]                                 │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Payment Processing                            │
│  • Razorpay modal / redirect                                     │
│  • Loading state                                                 │
│  • Error handling                                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Order Confirmation                             │
│  • Order number                                                  │
│  • Estimated delivery                                            │
│  • Order details                                                 │
│  • Continue shopping                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## API Endpoints

### Validate Checkout

```http
POST /api/v1/checkout/validate
Authorization: Bearer {token}
Content-Type: application/json

{
    "cart_id": 123
}

Response:
{
    "success": true,
    "data": {
        "valid": true,
        "cart": {
            "items": [...],
            "subtotal": 4999.00,
            "item_count": 2
        },
        "issues": []
    }
}

# If issues exist:
{
    "success": true,
    "data": {
        "valid": false,
        "issues": [
            {
                "type": "out_of_stock",
                "product_id": 123,
                "message": "iPhone 15 Pro is out of stock",
                "action": "remove"
            },
            {
                "type": "quantity_exceeded",
                "product_id": 456,
                "available": 2,
                "requested": 5,
                "message": "Only 2 units available"
            }
        ]
    }
}
```

### Get Shipping Rates

```http
POST /api/v1/checkout/shipping-rates
Authorization: Bearer {token}
Content-Type: application/json

{
    "address_id": 1,
    "items": [
        {"product_id": 123, "quantity": 1},
        {"product_id": 456, "quantity": 2}
    ]
}

Response:
{
    "success": true,
    "data": {
        "rates": [
            {
                "id": "standard",
                "name": "Standard Delivery",
                "price": 0,
                "estimated_days": "5-7",
                "description": "Free shipping"
            },
            {
                "id": "express",
                "name": "Express Delivery",
                "price": 99.00,
                "estimated_days": "2-3",
                "description": "Faster delivery"
            },
            {
                "id": "same_day",
                "name": "Same Day Delivery",
                "price": 199.00,
                "estimated_days": "Today",
                "description": "Order before 2 PM",
                "available": true,
                "cutoff_time": "14:00"
            }
        ]
    }
}
```

### Create Order

```http
POST /api/v1/checkout/create-order
Authorization: Bearer {token}
Content-Type: application/json

{
    "shipping_address_id": 1,
    "billing_address_id": 1,
    "shipping_method": "standard",
    "payment_method": "razorpay",
    "coupon_code": "SAVE10",
    "use_wallet_balance": true,
    "credit_note_code": null,
    "notes": "Please leave at door"
}

Response:
{
    "success": true,
    "data": {
        "order": {
            "id": 789,
            "order_number": "ORD-2026012900001",
            "status": "pending",
            "payment_status": "pending",
            "subtotal": 4999.00,
            "discount": 499.90,
            "shipping_cost": 0,
            "tax": 809.82,
            "total": 5308.92,
            "items": [
                {
                    "id": 1,
                    "product_id": 123,
                    "name": "iPhone 15 Pro",
                    "quantity": 1,
                    "price": 4999.00
                }
            ]
        },
        "payment": {
            "gateway": "razorpay",
            "order_id": "order_abc123xyz",
            "amount": 530892,
            "currency": "INR",
            "key": "rzp_live_xxxxx"
        }
    }
}
```

### Confirm Payment

```http
POST /api/v1/checkout/confirm-payment
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 789,
    "payment_id": "pay_abc123",
    "signature": "razorpay_signature"
}

Response:
{
    "success": true,
    "data": {
        "order": {
            "id": 789,
            "order_number": "ORD-2026012900001",
            "status": "confirmed",
            "payment_status": "paid"
        },
        "redirect_url": "/orders/ORD-2026012900001"
    }
}
```

---

## Implementation

### Checkout Action

```php
// app/Actions/Checkout/ProcessCheckout.php
namespace App\Actions\Checkout;

use App\DTOs\Checkout\CheckoutDTO;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Inventory\InventoryService;
use App\Services\Payment\PaymentService;
use App\Services\Shipping\ShippingService;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\CartEmptyException;
use Illuminate\Support\Facades\DB;

readonly class ProcessCheckout
{
    public function __construct(
        private CartService $cart,
        private InventoryService $inventory,
        private ShippingService $shipping,
        private PaymentService $payment,
    ) {}

    public function execute(CheckoutDTO $data): Order
    {
        return DB::transaction(function () use ($data) {
            // 1. Get and validate cart
            $cart = $this->cart->getCart($data->userId);

            if ($cart->items->isEmpty()) {
                throw new CartEmptyException();
            }

            // 2. Validate stock availability
            $stockIssues = $this->inventory->validateStock($cart->items);
            if ($stockIssues->isNotEmpty()) {
                throw new InsufficientStockException($stockIssues);
            }

            // 3. Calculate totals
            $totals = $this->calculateTotals($cart, $data);

            // 4. Create order
            $order = $this->createOrder($cart, $data, $totals);

            // 5. Create order items
            $this->createOrderItems($order, $cart);

            // 6. Reserve inventory
            $this->inventory->reserve($cart->items, $order);

            // 7. Apply coupon usage
            if ($data->couponCode) {
                $this->applyCouponUsage($order, $data->couponCode);
            }

            // 8. Clear cart
            $this->cart->clear($cart);

            // 9. Prepare payment
            $paymentOrder = $this->payment->createPaymentOrder($order);
            $order->setRelation('paymentDetails', $paymentOrder);

            return $order;
        });
    }

    private function calculateTotals(Cart $cart, CheckoutDTO $data): array
    {
        $subtotal = $cart->items->sum(fn($item) =>
            $item->price * $item->quantity
        );

        $discount = 0;
        if ($data->couponCode) {
            $discount = $this->calculateDiscount($subtotal, $data->couponCode);
        }

        $shipping = $this->shipping->calculateRate(
            $data->shippingAddressId,
            $data->shippingMethod,
            $cart->items
        );

        $taxableAmount = $subtotal - $discount;
        $tax = $this->calculateTax($cart->items, $taxableAmount);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $subtotal - $discount + $shipping + $tax,
        ];
    }

    private function createOrder(Cart $cart, CheckoutDTO $data, array $totals): Order
    {
        $shippingAddress = UserAddress::findOrFail($data->shippingAddressId);
        $billingAddress = UserAddress::findOrFail($data->billingAddressId);

        return Order::create([
            'order_number' => $this->generateOrderNumber(),
            'user_id' => $data->userId,
            'shipping_address_id' => $data->shippingAddressId,
            'billing_address_id' => $data->billingAddressId,
            'shipping_address_snapshot' => $shippingAddress->toArray(),
            'billing_address_snapshot' => $billingAddress->toArray(),
            'coupon_id' => $data->couponCode ?
                Coupon::where('code', $data->couponCode)->value('id') : null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'shipping_cost' => $totals['shipping'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
            'notes' => $data->notes,
            'source' => 'web',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = Order::whereDate('created_at', today())->count() + 1;
        return sprintf('ORD-%s%05d', $date, $sequence);
    }
}
```

### Checkout Livewire Component

```php
// app/Livewire/Checkout/CheckoutPage.php
namespace App\Livewire\Checkout;

use App\Actions\Checkout\ProcessCheckout;
use App\DTOs\Checkout\CheckoutDTO;
use App\Models\UserAddress;
use Livewire\Component;

class CheckoutPage extends Component
{
    public $cart;
    public $addresses;
    public $shippingAddressId;
    public $billingAddressId;
    public $shippingMethod = 'standard';
    public $paymentMethod = 'razorpay';
    public $couponCode = '';
    public $notes = '';

    public $shippingRates = [];
    public $totals = [];
    public $processing = false;
    public $error = null;

    protected $listeners = ['addressAdded' => 'refreshAddresses'];

    public function mount()
    {
        $this->cart = auth()->user()->cart()->with('items.product.images')->first();
        $this->addresses = auth()->user()->addresses;

        // Auto-select default address
        $defaultAddress = $this->addresses->where('is_default', true)->first();
        if ($defaultAddress) {
            $this->shippingAddressId = $defaultAddress->id;
            $this->billingAddressId = $defaultAddress->id;
        }

        $this->loadShippingRates();
        $this->calculateTotals();
    }

    public function updatedShippingAddressId()
    {
        $this->loadShippingRates();
        $this->calculateTotals();
    }

    public function updatedShippingMethod()
    {
        $this->calculateTotals();
    }

    public function applyCoupon()
    {
        $this->validate(['couponCode' => 'required|string']);

        $result = app(ValidateCoupon::class)->execute(
            $this->couponCode,
            $this->cart->subtotal,
            auth()->id()
        );

        if (!$result->valid) {
            $this->addError('couponCode', $result->message);
            return;
        }

        $this->calculateTotals();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Coupon applied successfully!',
        ]);
    }

    public function placeOrder(ProcessCheckout $action)
    {
        $this->validate([
            'shippingAddressId' => 'required|exists:user_addresses,id',
            'billingAddressId' => 'required|exists:user_addresses,id',
            'shippingMethod' => 'required|in:standard,express,same_day',
            'paymentMethod' => 'required|in:razorpay,cod',
        ]);

        $this->processing = true;
        $this->error = null;

        try {
            $order = $action->execute(new CheckoutDTO(
                userId: auth()->id(),
                shippingAddressId: $this->shippingAddressId,
                billingAddressId: $this->billingAddressId,
                shippingMethod: $this->shippingMethod,
                paymentMethod: $this->paymentMethod,
                couponCode: $this->couponCode ?: null,
                notes: $this->notes ?: null,
            ));

            if ($this->paymentMethod === 'razorpay') {
                $this->dispatch('initializePayment', [
                    'orderId' => $order->paymentDetails->id,
                    'amount' => $order->paymentDetails->amount,
                    'key' => config('services.razorpay.key'),
                ]);
            } else {
                // COD - redirect to confirmation
                return redirect()->route('orders.show', $order->order_number);
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.checkout.checkout-page');
    }
}
```

---

## Payment Integration

### Razorpay Service

```php
// app/Services/Payment/RazorpayService.php
namespace App\Services\Payment;

use App\Models\Order;
use Razorpay\Api\Api;

class RazorpayService implements PaymentGatewayInterface
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    public function createOrder(Order $order): PaymentOrder
    {
        $razorpayOrder = $this->api->order->create([
            'amount' => (int) ($order->total * 100), // Paise
            'currency' => 'INR',
            'receipt' => $order->order_number,
            'notes' => [
                'order_id' => $order->id,
            ],
        ]);

        return new PaymentOrder(
            id: $razorpayOrder->id,
            amount: $razorpayOrder->amount,
            currency: $razorpayOrder->currency,
            key: config('services.razorpay.key'),
        );
    }

    public function verifyPayment(string $paymentId, string $orderId, string $signature): bool
    {
        $expectedSignature = hash_hmac(
            'sha256',
            $orderId . '|' . $paymentId,
            config('services.razorpay.secret')
        );

        return hash_equals($expectedSignature, $signature);
    }

    public function capturePayment(string $paymentId, int $amount): PaymentResult
    {
        $payment = $this->api->payment->fetch($paymentId);
        $captured = $payment->capture(['amount' => $amount]);

        return new PaymentResult(
            success: $captured->status === 'captured',
            transactionId: $captured->id,
            amount: $captured->amount / 100,
            method: $captured->method,
        );
    }
}
```

### Payment JavaScript

```javascript
// resources/js/checkout.js
function initializeRazorpay(orderId, amount, key) {
    const options = {
        key: key,
        amount: amount,
        currency: 'INR',
        order_id: orderId,
        name: 'ShopVerse',
        description: 'Order Payment',
        prefill: {
            name: document.querySelector('[data-customer-name]').value,
            email: document.querySelector('[data-customer-email]').value,
            contact: document.querySelector('[data-customer-phone]').value,
        },
        theme: {
            color: '#ee7a14', // Primary brand color
        },
        handler: function(response) {
            // Send to server for verification
            Livewire.dispatch('paymentCompleted', {
                paymentId: response.razorpay_payment_id,
                orderId: response.razorpay_order_id,
                signature: response.razorpay_signature,
            });
        },
        modal: {
            ondismiss: function() {
                Livewire.dispatch('paymentCancelled');
            },
        },
    };

    const razorpay = new Razorpay(options);
    razorpay.open();
}

// Listen for Livewire event
document.addEventListener('livewire:initialized', () => {
    Livewire.on('initializePayment', (data) => {
        initializeRazorpay(data.orderId, data.amount, data.key);
    });
});
```

---

## Checkout UI

```html
{{-- resources/views/livewire/checkout/checkout-page.blade.php --}}
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-neutral-900 mb-8">Checkout</h1>

    <div class="lg:grid lg:grid-cols-3 lg:gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Shipping Address --}}
            <section class="bg-white border border-neutral-200 rounded-lg p-6">
                <h2 class="text-lg font-medium text-neutral-900 mb-4">Shipping Address</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($addresses as $address)
                        <label
                            class="relative flex p-4 border rounded-lg cursor-pointer transition-colors"
                            :class="shippingAddressId === {{ $address->id }}
                                ? 'border-primary-500 bg-primary-50'
                                : 'border-neutral-200 hover:border-neutral-300'"
                        >
                            <input
                                type="radio"
                                wire:model.live="shippingAddressId"
                                value="{{ $address->id }}"
                                class="sr-only"
                            >
                            <div>
                                <p class="font-medium text-neutral-900">{{ $address->label }}</p>
                                <p class="text-sm text-neutral-600">
                                    {{ $address->address_line_1 }}, {{ $address->city }}
                                </p>
                                <p class="text-sm text-neutral-500">{{ $address->phone }}</p>
                            </div>
                            @if($shippingAddressId === $address->id)
                                <x-icons.check class="absolute top-4 right-4 w-5 h-5 text-primary-500" />
                            @endif
                        </label>
                    @endforeach
                </div>

                <button
                    type="button"
                    wire:click="$dispatch('openAddressModal')"
                    class="mt-4 text-sm text-primary-500 hover:text-primary-600"
                >
                    + Add new address
                </button>
            </section>

            {{-- Shipping Method --}}
            <section class="bg-white border border-neutral-200 rounded-lg p-6">
                <h2 class="text-lg font-medium text-neutral-900 mb-4">Shipping Method</h2>

                <div class="space-y-3">
                    @foreach($shippingRates as $rate)
                        <label
                            class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-colors"
                            :class="shippingMethod === '{{ $rate['id'] }}'
                                ? 'border-primary-500 bg-primary-50'
                                : 'border-neutral-200 hover:border-neutral-300'"
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    wire:model.live="shippingMethod"
                                    value="{{ $rate['id'] }}"
                                    class="text-primary-500 focus:ring-primary-500"
                                >
                                <div>
                                    <p class="font-medium text-neutral-900">{{ $rate['name'] }}</p>
                                    <p class="text-sm text-neutral-500">{{ $rate['estimated_days'] }} business days</p>
                                </div>
                            </div>
                            <span class="font-medium text-neutral-900">
                                @if($rate['price'] == 0)
                                    FREE
                                @else
                                    ₹{{ number_format($rate['price']) }}
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Payment Method --}}
            <section class="bg-white border border-neutral-200 rounded-lg p-6">
                <h2 class="text-lg font-medium text-neutral-900 mb-4">Payment Method</h2>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-4 border border-neutral-200 rounded-lg cursor-pointer hover:border-neutral-300">
                        <input type="radio" wire:model="paymentMethod" value="razorpay" class="text-primary-500">
                        <div class="flex-1">
                            <p class="font-medium text-neutral-900">Pay Online</p>
                            <p class="text-sm text-neutral-500">Cards, UPI, Net Banking, Wallets</p>
                        </div>
                        <div class="flex gap-2">
                            <img src="/images/visa.svg" class="h-6">
                            <img src="/images/upi.svg" class="h-6">
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-4 border border-neutral-200 rounded-lg cursor-pointer hover:border-neutral-300">
                        <input type="radio" wire:model="paymentMethod" value="cod" class="text-primary-500">
                        <div>
                            <p class="font-medium text-neutral-900">Cash on Delivery</p>
                            <p class="text-sm text-neutral-500">Pay when you receive</p>
                        </div>
                    </label>
                </div>
            </section>
        </div>

        {{-- Order Summary (Sticky) --}}
        <div class="lg:col-span-1 mt-8 lg:mt-0">
            <div class="bg-white border border-neutral-200 rounded-lg p-6 sticky top-24">
                <h2 class="text-lg font-medium text-neutral-900 mb-4">Order Summary</h2>

                {{-- Items --}}
                <div class="space-y-3 mb-4">
                    @foreach($cart->items as $item)
                        <div class="flex gap-3">
                            <img src="{{ $item->product->primaryImage?->url }}" class="w-12 h-12 object-cover rounded">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-neutral-900 truncate">{{ $item->product->name }}</p>
                                <p class="text-sm text-neutral-500">Qty: {{ $item->quantity }}</p>
                            </div>
                            <p class="text-sm font-medium">₹{{ number_format($item->total) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-neutral-200 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Subtotal</span>
                        <span>₹{{ number_format($totals['subtotal']) }}</span>
                    </div>
                    @if($totals['discount'] > 0)
                        <div class="flex justify-between text-sm text-success-600">
                            <span>Discount</span>
                            <span>-₹{{ number_format($totals['discount']) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Shipping</span>
                        <span>
                            @if($totals['shipping'] == 0) FREE @else ₹{{ number_format($totals['shipping']) }} @endif
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Tax</span>
                        <span>₹{{ number_format($totals['tax']) }}</span>
                    </div>
                </div>

                <div class="border-t border-neutral-200 mt-4 pt-4">
                    <div class="flex justify-between font-medium text-lg">
                        <span>Total</span>
                        <span>₹{{ number_format($totals['total']) }}</span>
                    </div>
                </div>

                {{-- Coupon --}}
                <div class="mt-4">
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model="couponCode"
                            placeholder="Coupon code"
                            class="flex-1 px-3 py-2 text-sm border border-neutral-200 rounded-md focus:ring-2 focus:ring-primary-500"
                        >
                        <button
                            wire:click="applyCoupon"
                            class="px-4 py-2 text-sm font-medium text-primary-500 border border-primary-500 rounded-md hover:bg-primary-50"
                        >
                            Apply
                        </button>
                    </div>
                    @error('couponCode')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Place Order Button --}}
                <button
                    wire:click="placeOrder"
                    wire:loading.attr="disabled"
                    class="w-full mt-6 px-6 py-3 text-base font-medium text-white bg-primary-500 rounded-md hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <span wire:loading.remove>Place Order - ₹{{ number_format($totals['total']) }}</span>
                    <span wire:loading class="flex items-center justify-center gap-2">
                        <x-icons.spinner class="w-5 h-5 animate-spin" />
                        Processing...
                    </span>
                </button>

                @if($error)
                    <p class="mt-4 text-sm text-error-500 text-center">{{ $error }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
```

---

## Acceptance Criteria

### Checkout Flow
- [ ] User can select saved address
- [ ] User can add new address during checkout
- [ ] Shipping rates load based on address
- [ ] Shipping method selection updates total
- [ ] Payment method selection works
- [ ] Coupon code validation works
- [ ] Order total calculates correctly
- [ ] Place order creates order in database

### Payment
- [ ] Razorpay modal opens correctly
- [ ] Payment success creates confirmed order
- [ ] Payment failure shows error message
- [ ] COD orders created without payment
- [ ] Payment webhook updates order status

### Post-Order
- [ ] Order confirmation page displays
- [ ] Confirmation email sent
- [ ] Cart is cleared
- [ ] Inventory is updated

### Error Handling
- [ ] Out of stock items prevented
- [ ] Invalid coupon shows error
- [ ] Payment timeout handled
- [ ] Network errors handled gracefully

