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
        <label for="kk-sort" class="text-xs text-neutral-600 hidden sm:inline">Sort by:</label>
        <select id="kk-sort" name="sort" onchange="this.form.submit()"
                class="min-h-10 sm:min-h-0 text-sm py-1.5 pl-3 pr-8 border border-neutral-200 rounded-lg bg-white text-neutral-700 focus:outline-none focus:border-[#6F9CA2] cursor-pointer">
            @foreach($kkSortOptions as $kkValueKey => $kkLabel)
                <option value="{{ $kkValueKey }}" @selected($kkSortValues['sort'] === $kkValueKey)>{{ $kkLabel }}</option>
            @endforeach
        </select>
    </form>
</div>
