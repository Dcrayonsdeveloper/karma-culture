@props([
    // The order that identifies the customer. On the Returns list that is the
    // order the return was raised against - a return's customer is simply the
    // customer of its order, so both screens read identity from the same place.
    'order' => null,
    // Their lifetime tally, already counted for the whole page in one query.
    'count' => 0,
    // Where the badge leads, and what it is counting.
    'listRoute' => 'admin.orders.index',
    'noun' => 'order',
    // Optional extra clause for the tooltip, e.g. "across 2 orders".
    'detail' => null,
])

@php
    $kkCustomerKey = \App\Support\CustomerIdentity::ofOrder($order);
    $kkTally = (int) $count;
    $kkName = \App\Support\CustomerIdentity::nameOfOrder($order) ?? 'This customer';
    $kkNoun = \Illuminate\Support\Str::plural($noun, $kkTally);

    // Say WHICH history is being counted. An account and a mobile number are
    // separate customers here and are never merged, so a bare "3 orders" would
    // be a claim the badge cannot support for someone who bought as a guest
    // before they registered. Naming the basis makes the number checkable.
    $kkScope = $kkCustomerKey && \App\Support\CustomerIdentity::isGuestKey($kkCustomerKey)
        ? ' placed from ' . \App\Support\CustomerIdentity::phoneOf($kkCustomerKey)
        : ' on this account';

    $kkTitle = trim("{$kkName} has {$kkTally} {$kkNoun}{$kkScope}"
        . ($detail ? ", {$detail}" : '')) . ' - show them all';
@endphp

{{-- Nothing at all for a first-timer.

     A badge reading "1" on almost every row is noise that hides the rows worth
     noticing, and it would link to a filtered list of the single order already
     on screen. The badge exists to answer "have I seen this person before?", so
     it only appears once the answer is yes.

     Also nothing when the customer cannot be identified - a guest order placed
     with no usable mobile number. Those rows are not a customer with one order;
     they are an order whose customer is unknown, and grouping them would invent
     a person who had ordered as many times as the shop has anonymous orders.

     stopPropagation: the whole row is already a link to the order, via an
     onclick on the <tr>. Without it the browser would follow both, and the
     badge would open the order it sits on instead of the customer's list. --}}
@if($kkCustomerKey && $kkTally > 1)
    <a href="{{ route($listRoute, ['customer' => $kkCustomerKey]) }}"
       class="badge"
       onclick="event.stopPropagation()"
       title="{{ $kkTitle }}"
       aria-label="{{ $kkTitle }}"
       style="background: #efe8fd; color: #5c35c4; text-decoration: none; white-space: nowrap; flex-shrink: 0; cursor: pointer;">
        {{-- Two arrows chasing each other: this one came round again. --}}
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="flex-shrink: 0;">
            <path d="M17 1l4 4-4 4"/>
            <path d="M3 11V9a4 4 0 014-4h14"/>
            <path d="M7 23l-4-4 4-4"/>
            <path d="M21 13v2a4 4 0 01-4 4H3"/>
        </svg>
        {{ $kkTally }}
    </a>
@endif
