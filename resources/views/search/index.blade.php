<x-layouts.app>
    <x-slot name="title">{{ $query ? 'Search: ' . $query : 'Search' }}</x-slot>

    {{-- The search field lives in the header, which already carries the
         suggestions dropdown. A second one here duplicated it and, because
         the box is autofocused with the query prefilled, its dropdown opened
         on load while `results` was still empty - printing "No results
         found" directly above the results the page had just found. --}}

    @if($query)
        <div class="container mx-auto px-4 pt-8">
            <p class="text-sm text-neutral-600">
                @if($products->total() > 0)
                    {{ number_format($products->total()) }} results for <span class="font-semibold text-neutral-800">"{{ $query }}"</span>
                @else
                    No results found for <span class="font-semibold text-neutral-800">"{{ $query }}"</span>
                @endif
            </p>
        </div>

        {{-- The same sidebar the shop, the categories and every other listing
             use. This page used to carry its own cut-down panel that offered
             category, brand and price and nothing else. --}}
        @include('partials.product-listing')
    @else
        <div class="container mx-auto px-4 py-8">
            <!-- Empty Search State -->
            <div class="text-center py-12">
                <svg class="w-20 h-20 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h2 class="text-xl font-semibold text-neutral-900 mb-2">Start searching</h2>
                <p class="text-neutral-600 mb-6">Enter a keyword to find products.</p>

                <!-- Popular Categories -->
                @if($categories->count())
                    <div class="max-w-2xl mx-auto">
                        <h3 class="text-sm font-medium text-neutral-700 mb-4">Popular Categories</h3>
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach($categories as $category)
                                <a href="{{ route('category.show', $category) }}" class="btn btn-outline text-sm">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-layouts.app>
