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
    //
    // It counts `values['subcategory']` - what the request carried - and not
    // `active_subcategories`, which also holds the slug a category sub-page
    // ticks on the shopper's behalf. Counting that put a standing "1" on the
    // Filters button of every sub-category page, offering to clear a filter
    // nobody had set. product-listing and the chip row both already read the
    // request-side list; this is the odd one out brought into line.
    //
    // The category value only counts where the panel actually owns that facet:
    // a category page passes owns_category => false, so a stray ?category= in
    // the URL is inert there and must not be advertised as an active filter.
    $kkOwnsCategory = $filterPanel['categories']->isNotEmpty();
    $kkActiveCount = count($kkV['brand']) + count($kkV['size']) + count($kkV['colour']) + count($kkV['texture'])
        + count($kkV['subcategory'])
        + count(array_filter([
            $kkOwnsCategory ? $kkV['category'] : null, $kkV['min_price'], $kkV['max_price'],
            $kkV['rating'], $kkV['in_stock'], $kkV['on_sale'],
        ], fn ($v) => $v !== null && $v !== false && $v !== ''));
@endphp

{{-- From lg up this page shows the filter column in full, so the header's
     Filters button would open a slide-over on top of a panel already on screen.
     The rule lives here rather than in the layout because it applies to exactly
     the pages that include this partial, and the layout - an anonymous
     component - cannot see whether the page has a $filterPanel. Below lg the
     column is hidden and the header button is the useful one, because it stays
     put while the listing's own Filters button scrolls away. --}}
@once
    <style>@media (min-width:1024px){.kk-filters-btn{display:none}}</style>
@endonce

{{-- data-kk-filter-sidebar is how the header's global drawer knows to stand
     down on this page: a listing filters the grid actually on screen, so the
     Filters button in the header opens THIS panel rather than the shop-wide one.
     partials/global-filter-drawer.blade.php is the other half. --}}
<aside class="lg:w-60 shrink-0" data-kk-filter-sidebar
       x-data="{ mobileOpen: false }"
       @open-global-filters.window="mobileOpen = true">
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
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-[60]"
         role="dialog" aria-modal="true" aria-label="Filters"
         @keydown.escape.window="mobileOpen = false">
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="mobileOpen = false" class="absolute inset-0 bg-black/40"></div>
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             x-trap.noscroll="mobileOpen"
             class="absolute inset-y-0 left-0 w-80 max-w-[85vw] bg-white shadow-xl flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
                <h3 class="font-semibold text-neutral-900">Filters</h3>
                <button @click="mobileOpen = false" type="button" class="p-2.5 -m-1.5 text-neutral-600 hover:text-neutral-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                @include('partials.product-filters', ['kkStickyActions' => true])
            </div>
        </div>
    </div>

    {{-- Desktop filters.

         The column sits on the page background with nothing of its own behind
         it, so the section rules were the only thing giving it an edge - and a
         hairline is not a panel. A card gives the filters a surface to sit on
         and reads the same whether the page behind it is white or cream. --}}
    <div class="hidden lg:block bg-white border border-neutral-200 rounded-xl p-4">
        @include('partials.product-filters')
    </div>
</aside>
