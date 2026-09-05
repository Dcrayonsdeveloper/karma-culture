@props(['coupon'])

@php
    $transition = $coupon->statusTransition();
@endphp

{{--
    The status badge, plus the one boundary the coupon is waiting on.

    status() is true for the instant this rendered. Left at that, a tab held
    open across a coupon's start keeps reporting "Scheduled" long after it went
    live, which reads as the schedule never firing. With a boundary pending the
    badge carries the moment and what it becomes; without one it is a plain
    span and the script below never touches it.
--}}
<span {{ $attributes->merge(['class' => 'badge ' . $coupon->statusBadgeClass()]) }}
      @if ($transition)
          data-status-at="{{ $transition['at']->toIso8601String() }}"
          data-status-from="{{ $coupon->statusBadgeClass() }}"
          data-status-to="{{ \App\Models\Coupon::badgeClassFor($transition['status']) }}"
          data-status-label="{{ \App\Models\Coupon::STATUSES[$transition['status']] }}"
      @endif
>{{ $coupon->statusLabel() }}</span>

@once
@push('scripts')
<script>
    // Polled rather than a timer per badge: setTimeout is capped just under 25
    // days and fires immediately once a delay passes that, so a coupon expiring
    // next month would flip the moment the page loaded. Reading the clock has
    // neither that ceiling nor the problem of a laptop sleeping through the one
    // moment its timer was set for.
    (function () {
        const waiting = () => document.querySelectorAll('[data-status-at]');
        if (!waiting().length) return;

        function settle() {
            waiting().forEach(badge => {
                const at = Date.parse(badge.dataset.statusAt);

                // A date we cannot read is one the server is still right about.
                if (isNaN(at) || Date.now() < at) return;

                badge.classList.replace(badge.dataset.statusFrom, badge.dataset.statusTo);
                badge.textContent = badge.dataset.statusLabel;

                // One move each. What follows it - a redemption, an edit, the
                // Active switch - needs data this page does not have, so the
                // status after this one comes from a reload.
                delete badge.dataset.statusAt;
            });

            if (!waiting().length) clearInterval(timer);
        }

        const timer = setInterval(settle, 5000);

        // Background tabs have their timers throttled to once a minute or worse,
        // so catch up the moment one is looked at again.
        document.addEventListener('visibilitychange', () => document.hidden || settle());
    })();
</script>
@endpush
@endonce
