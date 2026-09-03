{{--
    Result count and ordering, shared by every listing page.

    The select is a real GET form posting back to the page it is on, carrying
    the filters through as hidden inputs. Each page used to hand-roll this with
    `window.location.href = '{{ route(...) }}?' + new URLSearchParams(...)`, and
    the shop's copy named route('home') - so choosing "Price: Low to High" on
    /shop threw the shopper out to the front page with their filters attached.
--}}
@php
    $kkSortValues = $filterPanel['values'];
    $kkSortOptions = ($filterPanel['sorts'] ?? null) ?: [
        'newest' => 'Newest',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'rating' => 'Best Rating',
        'bestselling' => 'Bestselling',
        'name' => 'Name: A to Z',
    ];
    // Everything the shopper has already narrowed by, carried through the sort
    // submit. `page` is dropped: a re-sorted list starts again at page one.
    $kkCarried = collect(request()->except(['sort', 'page']))
        ->merge($filterPanel['hidden'] ?? [])
        ->all();
@endphp

<div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-5 pb-4 border-b border-neutral-100">
    <p class="text-sm text-neutral-600">
        <span class="font-semibold text-neutral-900">{{ number_format($products->total()) }}</span>
        {{ Str::plural('product', $products->total()) }} found
    </p>

    <form method="GET" action="{{ $filterPanel['action'] }}" class="flex items-center gap-2 ml-auto min-w-0">
        @foreach(Arr::dot($kkCarried) as $kkKey => $kkValue)
            {{-- Arr::dot flattens size[] to size.0; the name has to go back to
                 size[0] or the array filters arrive as scalars named "size.0". --}}
            @php
                $kkParts = explode('.', $kkKey);
                $kkField = array_shift($kkParts);
                foreach ($kkParts as $kkPart) {
                    $kkField .= '['.$kkPart.']';
                }
            @endphp
            <input type="hidden" name="{{ $kkField }}" value="{{ $kkValue }}">
        @endforeach
        <label for="kk-sort" class="text-xs text-kk-text-muted hidden sm:inline">Sort by:</label>
        {{-- aria-label as well as the label, because the label is hidden below
             sm: and a display:none label gives the control no name at all on a
             phone - which is also where the dropdown opens as a titled sheet.
             min-h-10 keeps the touch target on a phone; the pill carries the
             rest of the look. --}}
        <select id="kk-sort" name="sort" aria-label="Sort by" onchange="this.form.submit()"
                class="kk-select-pill min-h-10 sm:min-h-0">
            @foreach($kkSortOptions as $kkValueKey => $kkLabel)
                <option value="{{ $kkValueKey }}" @selected($kkSortValues['sort'] === $kkValueKey)>{{ $kkLabel }}</option>
            @endforeach
        </select>
    </form>
</div>
