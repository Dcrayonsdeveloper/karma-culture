{{--
    The sidebar shell around the shared filter form: a slide-over on a phone, a
    plain column from lg up. Every listing page includes this one file, so the
    Filters button, the drawer and the desktop column behave identically
    wherever a shopper opens them.
--}}
@php
    $kkV = $filterPanel['values'];
    // Multi-value filters are counted per tick: two brands read as "1" when the
    // badge counted parameters instead of values, and a chosen size or colour
    // did not register at all.
    $kkActiveCount = count($kkV['brand']) + count($kkV['size']) + count($kkV['colour'])
        + count($filterPanel['active_subcategories'] ?? [])
        + count(array_filter([
            $kkV['category'], $kkV['min_price'], $kkV['max_price'],
            $kkV['rating'], $kkV['in_stock'], $kkV['on_sale'],
        ], fn ($v) => $v !== null && $v !== false && $v !== ''));
@endphp

<aside class="lg:w-60 shrink-0" x-data="{ mobileOpen: false }">
    <!-- Mobile filter toggle -->
    <button @click="mobileOpen = true" type="button"
            class="lg:hidden w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-neutral-200 rounded-lg text-sm font-medium text-neutral-700 hover:border-neutral-300 transition-colors mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        Filters
        @if($kkActiveCount)
            <span class="w-5 h-5 bg-[#F8931D] text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $kkActiveCount }}</span>
        @endif
    </button>

    <!-- Mobile filter overlay -->
    <div x-show="mobileOpen" x-cloak class="lg:hidden fixed inset-0 z-50">
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="mobileOpen = false" class="absolute inset-0 bg-black/40"></div>
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 w-80 max-w-[85vw] bg-white shadow-xl flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
                <h3 class="font-semibold text-neutral-900">Filters</h3>
                <button @click="mobileOpen = false" type="button" class="p-1 text-neutral-600 hover:text-neutral-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                @include('partials.product-filters')
            </div>
        </div>
    </div>

    <!-- Desktop filters -->
    <div class="hidden lg:block">
        @include('partials.product-filters')
    </div>
</aside>
