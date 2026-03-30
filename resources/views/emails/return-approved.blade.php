@component('mail::message')
# Return Request Approved

Hi {{ $orderReturn->user->first_name }},

Your return request **#{{ $orderReturn->return_number }}** for order **#{{ $orderReturn->order->order_number }}** has been approved.

---

## Return Details

**Return Number:** #{{ $orderReturn->return_number }}
**Order Number:** #{{ $orderReturn->order->order_number }}
**Return Type:** {{ ucfirst($orderReturn->type) }}
**Approved On:** {{ $orderReturn->approved_at ? $orderReturn->approved_at->format('M d, Y \a\t h:i A') : now()->format('M d, Y \a\t h:i A') }}

@if($orderReturn->items->count() > 0)
## Items Being Returned

@component('mail::table')
| Item | Qty | Reason |
|:-----|:---:|:-------|
@foreach ($orderReturn->items as $returnItem)
| {{ $returnItem->orderItem->product_name ?? 'Item' }} | {{ $returnItem->quantity }} | {{ ucfirst($returnItem->reason ?? 'N/A') }} |
@endforeach
@endcomponent
@endif

@if($orderReturn->refund_amount > 0)
**Estimated Refund Amount:** {{ format_price($orderReturn->refund_amount) }}
**Refund Method:** {{ ucfirst(str_replace('_', ' ', $orderReturn->refund_method ?? 'Original payment method')) }}
@endif

---

## What Happens Next?

1. **Pack the items** securely in their original packaging if possible.
2. **A pickup will be scheduled** -- we will notify you with the pickup date and time.
3. **Once we receive and inspect the items**, your refund will be processed.

@if($orderReturn->pickup_scheduled_at)
**Pickup Scheduled For:** {{ $orderReturn->pickup_scheduled_at->format('M d, Y \a\t h:i A') }}
@endif

@component('mail::button', ['url' => url('/orders/' . $orderReturn->order_id)])
View Return Details
@endcomponent

If you have any questions about the return process, please do not hesitate to contact our support team.

Warm regards,
**ForeverKids**
@endcomponent
