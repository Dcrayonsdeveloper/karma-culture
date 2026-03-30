@component('mail::message')
# Welcome to ForeverKids!

Hi {{ $user->first_name }},

We are thrilled to have you join the ForeverKids family! Thank you for creating your account with us.

At ForeverKids, we are passionate about providing adorable, high-quality clothing for your little ones. From everyday essentials to special occasion outfits, we have everything to keep your kids looking and feeling their best.

---

## Here Is What You Can Look Forward To

- **Curated Collections** -- Handpicked styles for every age and occasion
- **Quality You Can Trust** -- Comfortable, durable fabrics that kids love
- **Exclusive Deals** -- Member-only discounts and early access to sales
- **Easy Returns** -- Hassle-free returns within 7 days of delivery

@component('mail::button', ['url' => url('/shop')])
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

We cannot wait to help you find the perfect outfits for your little ones!

Warm regards,
**ForeverKids**
@endcomponent
