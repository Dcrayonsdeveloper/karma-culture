@component('mail::message')
# How Was Your Order?

Hi {{ $order->user->first_name }},

We hope you and your little one are enjoying your recent purchase! We would love to hear your thoughts on the items from order **#{{ $order->order_number }}**.

---

## Your Items

@component('mail::table')
| Item | |
|:-----|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif | [Write a Review]({{ url('/products/' . ($item->product ? $item->product->slug : $item->product_id)) }}) |
@endforeach
@endcomponent

---

## Get 5% Off Your Next Order!

As a thank you for sharing your experience, we will send you a **5% discount code** after you submit your review. It is our way of saying thanks for helping other parents.

@component('mail::button', ['url' => url('/products'), 'color' => 'primary'])
Review Your Purchases
@endcomponent

Your feedback helps other parents make the best choices for their kids.

Warm regards,
**{{ config('app.name', 'ForeverKids') }}**
@endcomponent
