@component('mail::message')
# Thank You For Your Review!

Hi {{ $review->user?->first_name ?? $review->guest_name ?? 'there' }},

Thank you for sharing your experience with **{{ $review->product->name }}**. Your review helps other parents make great choices for their little ones!

---

## Here Is Your Reward

As promised, here is your exclusive discount code:

@component('mail::panel')
**{{ $coupon->code }}**
{{ (int) $coupon->value }}% off your next order
@endcomponent

**Expires:** {{ $coupon->expires_at->format('M d, Y') }}

@component('mail::button', ['url' => url('/products'), 'color' => 'primary'])
Shop Now & Save
@endcomponent

Simply enter the code at checkout to enjoy your discount. Happy shopping!

Warm regards,
**{{ config('app.name', 'ForeverKids') }}**
@endcomponent
