@component('mail::message')
# Your Order Is On Its Way!

Hi {{ $order->user->first_name }},

Exciting news -- your order **#{{ $order->order_number }}** has been shipped and is on its way to you!

@if($trackingNumber)
**Tracking Number:** {{ $trackingNumber }}

You can use this tracking number to follow your package's journey.

@component('mail::button', ['url' => route('account.orders.track', $order->id)])
Track Your Order
@endcomponent
@else
@component('mail::button', ['url' => route('account.orders.show', $order->id)])
View Order Details
@endcomponent
@endif

---

## Order Summary

**Order Number:** #{{ $order->order_number }}
**Shipped On:** {{ $order->shipped_at ? $order->shipped_at->format('M d, Y \a\t h:i A') : now()->format('M d, Y \a\t h:i A') }}
@if($order->expected_delivery_date)
**Expected Delivery:** {{ $order->expected_delivery_date->format('M d, Y') }}
@endif

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif | {{ $item->quantity }} | {{ format_price($item->total) }} |
@endforeach
| | **Total:** | **{{ format_price($order->total) }}** |
@endcomponent

@if($order->shipping_address_snapshot)
**Delivering To:**
{{ $order->shipping_address_snapshot['name'] ?? '' }}
{{ $order->shipping_address_snapshot['address_line_1'] ?? '' }}
@if(!empty($order->shipping_address_snapshot['address_line_2'])){{ $order->shipping_address_snapshot['address_line_2'] }}@endif
{{ $order->shipping_address_snapshot['city'] ?? '' }}, {{ $order->shipping_address_snapshot['state'] ?? '' }} {{ $order->shipping_address_snapshot['postal_code'] ?? '' }}
@endif

We will notify you once your order has been delivered. If you have any questions about your shipment, our support team is here to help.

Happy shopping!

Warm regards,
**{{ config('app.name') }}**
@endcomponent
