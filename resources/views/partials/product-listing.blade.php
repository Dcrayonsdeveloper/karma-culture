{{--
    A product listing: active-filter chips, the filter sidebar, the sort bar,
    the grid and the empty state.

    Every storefront listing renders through this, so the shop, a category,
    search results, a brand, deals, a flash sale, new arrivals and bestsellers
    all get the same controls in the same places. A page only supplies its own
    hero and its own "nothing here yet" wording.

    Expects: $products (paginator) and $filterPanel (App\Support\ProductFilters::facets()).
    Optional: $filterPanel['empty'] => ['title' => ..., 'text' => ..., 'url' => ..., 'label' => ...]
--}}
@php
    $kkV = $filterPanel['values'];
    // Only what the shopper actually chose. On a sub-category page the
    // sub-category box is ticked for them, so counting it would offer to clear
    // a filter nobody set.
    // The category value only counts where this panel owns the facet: a category
    // page passes owns_category => false, so ?category= never reaches its query
    // and must not be read as the shopper having narrowed anything.
    $kkNarrowed = ($kkV['category'] !== null && $filterPanel['categories']->isNotEmpty())
        || $kkV['brand'] || $kkV['size'] || $kkV['colour'] || $kkV['texture']
        || $kkV['min_price'] !== null || $kkV['max_price'] !== null || $kkV['rating'] !== null
        || $kkV['in_stock'] || $kkV['on_sale'] || $kkV['subcategory'];
    $kkEmpty = $filterPanel['empty'] ?? [];
    // A page whose cards carry extra terms - a flash sale's countdown price and
    // "N left at this price" - supplies its own card partial rather than
    // forking the whole listing.
    $kkCard = $filterPanel['card'] ?? null;
@endphp

<div class="container mx-auto px-4 py-6">
    @include('partials.product-filter-chips')

    <div class="flex flex-col lg:flex-row gap-6">
        @include('partials.product-filter-sidebar')

        <div class="flex-1 min-w-0">
            @include('partials.product-sort-bar')

            @if($products->count())
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4">
                    @foreach($products as $product)
                        @if($kkCard)
                            @include($kkCard)
                        @else
                            <x-product-card :product="$product" />
                        @endif
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-20">
                    <div class="w-20 h-20 mx-auto mb-4 bg-neutral-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    @if($kkNarrowed)
                        <h3 class="text-lg font-semibold text-neutral-900 mb-1">No products found</h3>
                        <p class="text-sm text-neutral-600 mb-5">Try adjusting your filters or browse all products.</p>
                        <a href="{{ $filterPanel['reset'] }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear All Filters
                        </a>
                    @else
                        {{-- Nothing was filtered: the list is simply empty, so offering
                             to clear filters would be nonsense. --}}
                        <h3 class="text-lg font-semibold text-neutral-900 mb-1">{{ $kkEmpty['title'] ?? 'No products found' }}</h3>
                        <p class="text-sm text-neutral-600 mb-5">{{ $kkEmpty['text'] ?? "There's nothing here yet. Check back soon." }}</p>
                        <a href="{{ $kkEmpty['url'] ?? route('shop') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-lg transition-colors">
                            {{ $kkEmpty['label'] ?? 'Browse all products' }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
