<x-layouts.app>
    <x-slot name="title">{{ $query ? 'Search: ' . $query : 'Search' }}</x-slot>

    <div class="container mx-auto px-4 py-8">
        {{-- The search field lives in the header, which already carries the
             suggestions dropdown. A second one here duplicated it and, because
             the box is autofocused with the query prefilled, its dropdown opened
             on load while `results` was still empty - printing "No results
             found" directly above the results the page had just found. --}}

        @if($query)
            <p class="text-sm text-neutral-600 mb-6">
                @if($products->total() > 0)
                    {{ number_format($products->total()) }} results for <span class="font-semibold text-neutral-800">"{{ $query }}"</span>
                @else
                    No results found for <span class="font-semibold text-neutral-800">"{{ $query }}"</span>
                @endif
            </p>
        @endif

        @if($query)
            <div class="flex flex-col lg:flex-row gap-5 lg:gap-8">
                <!-- Filters Sidebar -->
                {{-- On a phone this sits above the results, so left expanded it pushes
                     every product below the fold. It collapses behind a toggle under
                     lg and is always open from lg up, where it has its own column.
                     `sticky` is also lg-only: pinning a full-width block to the top of
                     a phone screen just steals the viewport. --}}
                <div class="lg:w-64 flex-shrink-0" x-data="{ filtersOpen: false }">
                    <button type="button" @click="filtersOpen = !filtersOpen"
                            :aria-expanded="filtersOpen ? 'true' : 'false'"
                            class="lg:hidden w-full flex items-center justify-between gap-2 px-4 py-2.5 mb-3 bg-white border border-neutral-200 rounded-lg text-sm font-medium text-neutral-700">
                        <span>Filters</span>
                        <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="filtersOpen && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="card p-4 lg:sticky lg:top-4" :class="filtersOpen ? 'block' : 'hidden lg:block'">
                        <h3 class="hidden lg:block font-semibold text-neutral-900 mb-4">Filters</h3>

                        <form action="{{ route('search') }}" method="GET" x-data x-ref="filterForm">
                            <input type="hidden" name="q" value="{{ $query }}">

                            <!-- Categories -->
                            @if($categories->count())
                                <div class="mb-6">
                                    <h4 class="text-sm font-medium text-neutral-700 mb-2">Category</h4>
                                    <div class="space-y-2">
                                        @foreach($categories as $category)
                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio" name="category" value="{{ $category->slug }}"
                                                       {{ request('category') === $category->slug ? 'checked' : '' }}
                                                       @change="$refs.filterForm.submit()"
                                                       class="text-primary-600 focus:ring-primary-500">
                                                <span class="ml-2 text-sm text-neutral-600">{{ $category->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Brands -->
                            @if($brands->count())
                                <div class="mb-6">
                                    <h4 class="text-sm font-medium text-neutral-700 mb-2">Brand</h4>
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        @foreach($brands as $brand)
                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio" name="brand" value="{{ $brand->slug }}"
                                                       {{ request('brand') === $brand->slug ? 'checked' : '' }}
                                                       @change="$refs.filterForm.submit()"
                                                       class="text-primary-600 focus:ring-primary-500">
                                                <span class="ml-2 text-sm text-neutral-600">{{ $brand->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Price Range -->
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-neutral-700 mb-2">Price Range</h4>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="min_price" value="{{ request('min_price') }}"
                                           class="form-input w-full text-sm" placeholder="Min" aria-label="Minimum price">
                                    <span class="text-neutral-600">-</span>
                                    <input type="number" name="max_price" value="{{ request('max_price') }}"
                                           class="form-input w-full text-sm" placeholder="Max" aria-label="Maximum price">
                                </div>
                            </div>

                            {{-- `btn` is the base class - .btn-primary and .btn-outline carry
                                 colour and nothing else. Without it these were raw browser
                                 boxes: an unpadded teal bar and a square-cornered link that
                                 ran to the edge of a 16rem panel instead of sitting inside it. --}}
                            <div class="flex items-center gap-2">
                                <button type="submit" class="btn btn-primary flex-1 text-sm">Apply Price</button>
                                <a href="{{ route('search', ['q' => $query]) }}" class="btn btn-outline text-sm shrink-0 whitespace-nowrap">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results -->
                <div class="flex-1">
                    @if($products->count())
                        <!-- Sort Bar -->
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-4">
                            <p class="text-sm text-neutral-600">
                                Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }}
                            </p>
                            <form class="flex items-center gap-2 ml-auto min-w-0">
                                <input type="hidden" name="q" value="{{ $query }}">
                                @if(request('category'))
                                    <input type="hidden" name="category" value="{{ request('category') }}">
                                @endif
                                @if(request('brand'))
                                    <input type="hidden" name="brand" value="{{ request('brand') }}">
                                @endif
                                <label for="search-sort" class="text-sm text-neutral-600 whitespace-nowrap">Sort by:</label>
                                <select id="search-sort" name="sort" class="form-input w-auto max-w-[11rem] text-sm" onchange="this.form.submit()">
                                    <option value="relevance" {{ request('sort') === 'relevance' ? 'selected' : '' }}>Relevance</option>
                                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                                    <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Rating</option>
                                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                                </select>
                            </form>
                        </div>

                        <!-- Products Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($products as $product)
                                <x-product-card :product="$product" :show-quick-view="false" />
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($products->hasPages())
                            <div class="mt-8">
                                {{ $products->links() }}
                            </div>
                        @endif
                    @else
                        <div class="card p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-neutral-900 mb-2">No products found</h3>
                            <p class="text-neutral-600 mb-4">Try adjusting your search or filters.</p>
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-sm text-neutral-600">Suggestions:</p>
                                <ul class="text-sm text-neutral-600">
                                    <li>Check your spelling</li>
                                    <li>Try more general keywords</li>
                                    <li>Remove some filters</li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
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
        @endif
    </div>
</x-layouts.app>
