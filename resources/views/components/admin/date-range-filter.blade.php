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
    $kkFrom = request($fromName, $range?->fromDate());
    $kkTo = request($toName, $range?->toDate());
    $kkToday = \Carbon\CarbonImmutable::today()->format('Y-m-d');

    // Filters other than the dates have to survive the submit, or applying a
    // range would silently drop the type or tab the admin had set. `period` goes
    // deliberately: it is the old preset parameter, and carrying it would let it
    // win over the dates on the next request.
    $kkCarry = request()->except([$fromName, $toName, 'period', 'page']);

    // Only a window that came from the URL is worth offering to reset.
    $kkFiltered = request()->filled($fromName) || request()->filled($toName) || request()->filled('period');
@endphp

<form method="GET" action="{{ $kkAction }}"
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
           class="form-input" aria-label="From date"
           style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">

    <span style="font-size: 12px; color: #616161;">to</span>

    <input type="date" name="{{ $toName }}" value="{{ $kkTo }}" max="{{ $kkToday }}"
           class="form-input" aria-label="To date"
           style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">

    <button type="submit" class="btn btn-secondary" style="font-size: 13px; padding: 0.375rem 0.75rem;">Apply</button>

    @if($kkFiltered)
        <a href="{{ $kkAction }}" style="font-size: 12px; color: #6F9CA2; text-decoration: none;">Reset</a>
    @endif
</form>
