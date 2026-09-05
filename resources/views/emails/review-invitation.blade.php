@component('mail::message')
# How Was Your Order?

Hi {{ $order->user->first_name }},

We hope you are enjoying your recent purchase! We would love to hear your thoughts on the items from order **#{{ $order->order_number }}**.

---

## Your Items

@component('mail::table')
| Item | |
|:-----|------:|
@foreach ($order->items as $item)
{{-- route() rather than a hand-built path: this link used to be assembled as
     /products/<slug>, which is the plural path that now redirects, and when the
     product had since been deleted it fell back to /products/<id> - an id where
     the route wants a slug, so the customer was emailed a link to a 404. There
     is nothing to review if the product is gone, so it is no longer a link. --}}
| {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif | @if($item->product)[Write a Review]({{ route('product.show', $item->product) }})@else—@endif |
@endforeach
@endcomponent

---

## Get 5% Off Your Next Order!

As a thank you for sharing your experience, we will send you a **5% discount code** after you submit your review. It is our way of saying thanks for helping other parents.

@component('mail::button', ['url' => route('shop'), 'color' => 'primary'])
Review Your Purchases
@endcomponent

Your feedback helps other parents make the best choices for their kids.

Warm regards,
**{{ config('app.name') }}**
@endcomponent
