{{--
    Three fixes over the original:

    - `$cart->user->first_name` fatals on a guest cart (user_id is null). It is
      null-safe now, because the admin can send this to any cart.
    - The currency symbol was hard-coded to a rupee sign while the rest of the
      app reads it from Settings, so a store that changed currency sent mail
      quoting the wrong one. format_price() is the same helper the storefront
      and the admin use.
    - The button linked to a bare /cart, which only works for a customer who is
      still signed in on the same device. It now uses the tokenised recovery
      link when one was supplied.
--}}
@component('mail::message')
# You Left Something Behind!

Hi {{ $cart->user->first_name ?? 'there' }},

It looks like you left some items in your cart. Here's what's waiting for you:

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach($cart->items as $item)
| {{ $item->product->name ?? 'Product' }} | {{ $item->quantity }} | {{ format_price($item->total) }} |
@endforeach
@endcomponent

**Cart Total: {{ format_price($cart->items->sum('total')) }}**

These items may sell out soon - complete your purchase before they're gone!

@component('mail::button', ['url' => $recoveryUrl ?? url('/cart'), 'color' => 'success'])
Complete Your Purchase
@endcomponent

If you have any questions, feel free to reach out to our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
