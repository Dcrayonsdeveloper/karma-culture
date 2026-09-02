{{--
    The "Active Filters:" row. Shared by every listing page so a shopper can
    always see - and undo - what is narrowing the grid, whether they got there
    from the shop, a category, a search or a sale.
--}}
@php
    $kkV = $filterPanel['values'];

    // Drops a single value out of a multi-value filter, so removing "Blue" does
    // not also throw away "Red".
    $kkWithout = function (string $param, string $value) {
        $q = request()->except('page');
        $q[$param] = array_values(array_diff((array) request($param, []), [$value]));
        if (! $q[$param]) {
            unset($q[$param]);
        }

        return request()->url().($q ? '?'.http_build_query($q) : '');
    };

    // Drops a whole one-value filter. This used to be fullUrlWithoutQuery(),
    // which keeps ?page - so removing a chip WIDENED the result set and then
    // left the shopper stranded on page 3 of it, while the multi-value chips
    // beside it restarted at page one. Same closure shape, minus `page`.
    $kkDrop = function (array|string $params) {
        $q = request()->except(array_merge(['page'], (array) $params));

        return request()->url().($q ? '?'.http_build_query($q) : '');
    };

    $kkName = function ($collection, string $slug) {
        return $collection->firstWhere('slug', $slug)->name ?? $slug;
    };

    $kkChip = 'inline-flex items-center gap-1 px-2.5 py-1 bg-[#6F9CA2]/5 text-[#5B878D] text-xs font-medium rounded-full border border-[#6F9CA2]/30';
    $kkHasAny = $kkV['category'] !== null || $kkV['subcategory'] || $kkV['brand'] || $kkV['size'] || $kkV['colour']
        || $kkV['min_price'] !== null || $kkV['max_price'] !== null || $kkV['rating'] !== null
        || $kkV['in_stock'] || $kkV['on_sale'];
@endphp

@if($kkHasAny)
    <div class="flex flex-wrap items-center gap-2 mb-5">
        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Active Filters:</span>

        @if($kkV['category'])
            <a href="{{ $kkDrop('category') }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                {{ $kkName($filterPanel['categories'], $kkV['category']) }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endif

        @foreach($kkV['subcategory'] as $kkSlug)
            <a href="{{ $kkWithout('subcategory', $kkSlug) }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                {{ $kkName($filterPanel['subcategories'], $kkSlug) }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endforeach

        @foreach($kkV['brand'] as $kkSlug)
            <a href="{{ $kkWithout('brand', $kkSlug) }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                {{ $kkName($filterPanel['brands'], $kkSlug) }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endforeach

        @foreach($kkV['size'] as $kkSize)
            <a href="{{ $kkWithout('size', $kkSize) }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                Size: {{ $kkSize }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endforeach

        @foreach($kkV['colour'] as $kkColour)
            <a href="{{ $kkWithout('colour', $kkColour) }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                {{ $kkColour }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endforeach

        @if($kkV['min_price'] !== null || $kkV['max_price'] !== null)
            @php
                // An open-ended bound reads as "Any" rather than as a stray dash: a
                // shopper who set only a maximum was shown "₹0 - ₹999".
                $kkMoney = fn (?float $v) => $v === null ? 'Any' : '₹'.number_format($v);
            @endphp
            <a href="{{ $kkDrop(['min_price', 'max_price']) }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                {{ $kkMoney($kkV['min_price']) }} - {{ $kkMoney($kkV['max_price']) }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endif

        @if($kkV['rating'] !== null)
            <a href="{{ $kkDrop('rating') }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                {{ $kkV['rating'] }}+ Stars
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endif

        @if($kkV['in_stock'])
            <a href="{{ $kkDrop('in_stock') }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                In Stock
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endif

        @if($kkV['on_sale'])
            <a href="{{ $kkDrop('on_sale') }}" class="{{ $kkChip }} hover:bg-[#6F9CA2]/10 transition-colors">
                On Sale
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endif

        <a href="{{ $filterPanel['reset'] }}" class="text-xs text-neutral-600 hover:text-[#6F9CA2] underline ml-1">Clear all</a>
    </div>
@endif
