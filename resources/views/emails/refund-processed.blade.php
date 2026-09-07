@php
    // A credit refund and a cash refund are different messages. The old template
    // sent one mail for both: it promised a "5-10 business day" bank timeline to
    // a customer who had actually been given a coupon code, and never printed
    // the code itself - so the one thing they needed was the one thing missing.
    // It also listed "Store Credit: Immediately available in your account",
    // which was never true; this shop has no account balance.
    $kkCoupon = $orderReturn->refundCoupon;
@endphp
@component('mail::message')
@if($kkCoupon)
# Your Store Credit Is Ready

Hi {{ $orderReturn->user->first_name ?? 'there' }},

Your return **#{{ $orderReturn->return_number }}** has been processed, and {{ format_price($amount) }} has been issued to you as store credit.

## Your Credit Code

@component('mail::panel')
# {{ $kkCoupon->code }}

Worth **{{ format_price($amount) }}**@if($kkCoupon->expires_at) · Valid until **{{ $kkCoupon->expires_at->format('d M Y') }}**@endif
@endcomponent

Enter this code in the coupon box at checkout.

@if((float) $kkCoupon->min_order_amount > 0)
It applies to orders of **{{ format_price($kkCoupon->min_order_amount) }}** or more. That minimum is deliberate - the credit is spent in one go, so this makes sure none of it is left behind.
@endif

It can be used once, and only on this account.
@else
# Refund Processed Successfully

Hi {{ $orderReturn->user->first_name ?? 'there' }},

We are pleased to inform you that your refund for return **#{{ $orderReturn->return_number }}** has been processed.
@endif

---

## Refund Details

**Return Number:** #{{ $orderReturn->return_number }}
**Order Number:** #{{ $orderReturn->order->order_number }}
**Refund Amount:** {{ format_price($amount) }}
**Refund Method:** {{ $kkCoupon ? 'Store credit' : ucfirst(str_replace('_', ' ', $orderReturn->refund_method ?? 'Original payment method')) }}
**Processed On:** {{ now()->format('M d, Y') }}

@if($orderReturn->items->count() > 0)
## Returned Items

@component('mail::table')
| Item | Qty |
|:-----|:---:|
@foreach ($orderReturn->items as $returnItem)
| {{ $returnItem->orderItem->product_name ?? 'Item' }} | {{ $returnItem->quantity }} |
@endforeach
@endcomponent
@endif

---

@unless($kkCoupon)
## When Will I Receive My Refund?

Depending on your payment method, please allow the following timeframes for the refund to appear:

- **Credit/Debit Card:** 5-10 business days
- **UPI/Net Banking:** 3-5 business days

@endunless
@component('mail::button', ['url' => route('account.orders.show', $orderReturn->order_id)])
View Order Details
@endcomponent

@if($kkCoupon)
Your code is also saved on the return in your account, so you can look it up any time.
@else
If you do not see the refund within the expected timeframe, please contact our support team and we will be happy to assist you.
@endif

Thank you for your patience, and we hope to see you shopping with us again soon!

Warm regards,
**{{ config('app.name') }}**
@endcomponent
