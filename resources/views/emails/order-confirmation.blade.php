@component('mail::message')
# Thank You for Your Order!

Hi {{ $order->user->first_name }},

Great news -- your order has been confirmed and is being prepared with care. Here are your order details:

**Order Number:** #{{ $order->order_number }}
**Order Date:** {{ $order->created_at->format('M d, Y \a\t h:i A') }}

---

## Order Summary

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif | {{ $item->quantity }} | {{ format_price($item->total) }} |
@endforeach
@endcomponent

@component('mail::table')
| | |
|:---|---:|
| **Subtotal** | {{ format_price($order->subtotal) }} |
@if($order->discount > 0)
| **Discount** | -{{ format_price($order->discount) }} |
@endif
@if($order->tax > 0)
| **Tax** | {{ format_price($order->tax) }} |
@endif
| **Shipping** | {{ $order->shipping_cost > 0 ? format_price($order->shipping_cost) : 'Free' }} |
| **Total** | **{{ format_price($order->total) }}** |
@endcomponent

@if($order->shipping_address_snapshot)
**Shipping Address:**
{{ $order->shipping_address_snapshot['name'] ?? '' }}
{{ $order->shipping_address_snapshot['address_line_1'] ?? '' }}
@if(!empty($order->shipping_address_snapshot['address_line_2'])){{ $order->shipping_address_snapshot['address_line_2'] }}@endif
{{ $order->shipping_address_snapshot['city'] ?? '' }}, {{ $order->shipping_address_snapshot['state'] ?? '' }} {{ $order->shipping_address_snapshot['postal_code'] ?? '' }}
@endif

@if($order->expected_delivery_date)
**Expected Delivery:** {{ $order->expected_delivery_date->format('M d, Y') }}
@endif

@component('mail::button', ['url' => route('account.orders.show', $order->id)])
View Your Order
@endcomponent

We will send you another email once your order has been shipped. If you have any questions, feel free to reach out to our support team.

Thank you for shopping with us!

Warm regards,
**{{ config('app.name') }}**
@endcomponent
