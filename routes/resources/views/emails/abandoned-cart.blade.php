@component('mail::message')
# You Left Something Behind!

Hi {{ $cart->user->first_name ?? 'there' }},

It looks like you left some items in your cart. Here's what's waiting for you:

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach($cart->items as $item)
| {{ $item->product->name ?? $item->product_name ?? 'Product' }} | {{ $item->quantity }} | ₹{{ number_format($item->total, 2) }} |
@endforeach
@endcomponent

**Cart Total: ₹{{ number_format($cart->total, 2) }}**

These items may sell out soon — complete your purchase before they're gone!

@component('mail::button', ['url' => url('/cart'), 'color' => 'success'])
Complete Your Purchase
@endcomponent

If you have any questions, feel free to reach out to our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
