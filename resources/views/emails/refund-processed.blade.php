@component('mail::message')
# Refund Processed Successfully

Hi {{ $orderReturn->user->first_name }},

We are pleased to inform you that your refund for return **#{{ $orderReturn->return_number }}** has been processed.

---

## Refund Details

**Return Number:** #{{ $orderReturn->return_number }}
**Order Number:** #{{ $orderReturn->order->order_number }}
**Refund Amount:** {{ format_price($amount) }}
**Refund Method:** {{ ucfirst(str_replace('_', ' ', $orderReturn->refund_method ?? 'Original payment method')) }}
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

## When Will I Receive My Refund?

Depending on your payment method, please allow the following timeframes for the refund to appear:

- **Credit/Debit Card:** 5-10 business days
- **UPI/Net Banking:** 3-5 business days
- **Store Credit:** Immediately available in your account

@component('mail::button', ['url' => url('/orders/' . $orderReturn->order_id)])
View Order Details
@endcomponent

If you do not see the refund within the expected timeframe, please contact our support team and we will be happy to assist you.

Thank you for your patience, and we hope to see you shopping with us again soon!

Warm regards,
**ForeverKids**
@endcomponent
