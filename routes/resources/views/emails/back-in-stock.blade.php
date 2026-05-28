@component('mail::message')
# Great News! {{ $product->name }} is Back in Stock

The item you've been waiting for is now available:

**{{ $product->name }}**
@if($product->price)
**Price:** ₹{{ number_format($product->price, 2) }}
@endif

Don't wait too long — popular items sell out fast!

@component('mail::button', ['url' => route('product.show', $product)])
Shop Now
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
