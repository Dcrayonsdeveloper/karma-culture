@component('mail::message')
# Welcome to {{ config('app.name') }}!

Hi {{ $user->first_name }},

We are thrilled to have you join the {{ config('app.name') }} family! Thank you for creating your account with us.

At {{ config('app.name') }}, we are passionate about curated, high-quality pieces made to last. From everyday essentials to statement occasion wear, we have everything to help you dress the way you want to be seen.

---

## Here Is What You Can Look Forward To

- **Curated Collections** -- Handpicked styles for every occasion
- **Quality You Can Trust** -- Comfortable, durable fabrics built to wear well
- **Exclusive Deals** -- Member-only discounts and early access to sales
- **Easy Returns** -- Hassle-free returns within 7 days of delivery

@component('mail::button', ['url' => route('shop')])
Start Shopping
@endcomponent

---

## Your Account Details

**Name:** {{ $user->full_name }}
**Email:** {{ $user->email }}

You can manage your profile, track orders, and save your favorite items all from your account dashboard.

@component('mail::button', ['url' => url('/account'), 'color' => 'success'])
Visit Your Account
@endcomponent

If you have any questions or need assistance, our friendly support team is always ready to help. Just reply to this email or visit our help center.

We cannot wait to help you find pieces you will reach for again and again!

Warm regards,
**{{ config('app.name') }}**
@endcomponent
