@props([
    // Where the filter submits. Defaults to the current URL so a screen can drop
    // the component in without repeating its own route name.
    'action' => null,
    // The App\Support\ReportRange the page is showing, so the two inputs open on
    // the window on screen rather than on empty boxes.
    'range' => null,
    // Query parameter names. ReportRange reads from/to, but the dashboard has
    // its own validated start_date/end_date pair and predates it.
    'fromName' => 'from',
    'toName' => 'to',
])

@php
    $kkAction = $action ?? url()->current();

    // old() first: when the dashboard rejects a window it redirects back, and
    // without this the two boxes came back empty next to an error about dates
    // that were no longer on screen.
    $kkFrom = old($fromName, request($fromName, $range?->fromDate()));
    $kkTo = old($toName, request($toName, $range?->toDate()));
    $kkToday = \Carbon\CarbonImmutable::today()->format('Y-m-d');

    // Filters other than the dates have to survive the submit, or applying a
    // range would silently drop the type or tab the admin had set. `period` goes
    // deliberately: it is the old preset parameter, and carrying it would let it
    // win over the dates on the next request.
    $kkCarry = request()->except([$fromName, $toName, 'period', 'page']);

    // Only a window that came from the URL is worth offering to reset.
    $kkFiltered = request()->filled($fromName) || request()->filled($toName) || request()->filled('period');

    $kkError = $errors->first($fromName) ?: $errors->first($toName);
@endphp

<form method="GET" action="{{ $kkAction }}" data-date-range-filter
      style="display: flex; align-items: center; gap: 0.375rem; flex-wrap: wrap;">
    @foreach($kkCarry as $kkName => $kkValue)
        @if(is_array($kkValue))
            @foreach($kkValue as $kkItem)
                <input type="hidden" name="{{ $kkName }}[]" value="{{ $kkItem }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $kkName }}" value="{{ $kkValue }}">
        @endif
    @endforeach

    <input type="date" name="{{ $fromName }}" value="{{ $kkFrom }}" max="{{ $kkToday }}"
           data-range-from
           class="form-input" aria-label="From date"
           style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">

    <span style="font-size: 12px; color: #616161;">to</span>

    <input type="date" name="{{ $toName }}" value="{{ $kkTo }}" max="{{ $kkToday }}"
           data-range-to
           class="form-input" aria-label="To date"
           style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">

    <button type="submit" class="btn btn-secondary" style="font-size: 13px; padding: 0.375rem 0.75rem;">Apply</button>

    @if($kkFiltered)
        <a href="{{ $kkAction }}" style="font-size: 12px; color: #6F9CA2; text-decoration: none;">Reset</a>
    @endif

    {{-- Full width so the message sits under the row rather than beside the
         Apply button. --}}
    @if($kkError)
        <p class="form-error" style="flex-basis: 100%; margin: 0.25rem 0 0 0;">{{ $kkError }}</p>
    @endif
</form>

@once
    @push('scripts')
        <script>
            // The end date is only ever bounded by the start date on screen, so
            // a picked window that reads backwards is caught before it costs a
            // round trip. This mirrors the controller's after_or_equal rule and
            // stops exactly there: the two dates may be the same day, because a
            // one-day report is a real report.
            document.querySelectorAll('form[data-date-range-filter]').forEach(function (form) {
                var from = form.querySelector('[data-range-from]');
                var to = form.querySelector('[data-range-to]');

                if (!from || !to) {
                    return;
                }

                function sync() {
                    to.min = from.value || '';

                    // The native "value must be X or later" bubble names the
                    // constraint but not the field it came from, and the two
                    // inputs look alike.
                    var reversed = from.value && to.value && to.value < from.value;
                    to.setCustomValidity(reversed ? 'The end date must be on or after the start date.' : '');
                }

                from.addEventListener('change', sync);
                to.addEventListener('change', sync);
                to.addEventListener('input', sync);
                sync();
            });
        </script>
    @endpush
@endonce
