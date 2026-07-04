<x-layouts.app>
    <x-slot name="title">{{ $query ? 'Search: ' . $query : 'Search' }}</x-slot>

    <div class="container mx-auto px-4 py-8">
        {{-- Search input with autocomplete dropdown --}}
        <div class="relative max-w-xl mb-6 mx-auto"
             x-data="searchBar()"
             x-init="query = '{{ addslashes($query ?? '') }}'; if(query) stopTypewriter()"
             @click.outside="showResults = false">
            <form action="{{ route('search') }}" method="GET" class="relative flex items-center">
                <svg class="absolute left-3 w-5 h-5 text-neutral-600 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       name="q"
                       x-ref="searchInput"
                       x-model="query"
                       @input.debounce.300ms="fetchSuggestions()"
                       @focus="showResults = true; stopTypewriter()"
                       @blur="if(!query) startTypewriter()"
                       @keydown.escape="showResults = false; $refs.searchInput.blur()"
                       :placeholder="currentPlaceholder"
                       class="w-full pl-10 pr-20 py-3 text-sm bg-white border border-neutral-200 rounded-lg focus:outline-none focus:border-[#6F9CA2] focus:ring-1 focus:ring-[#6F9CA2]"
                       autocomplete="off"
                       autofocus>

                {{-- Mic button (only shown when browser supports Speech Recognition) --}}
                <button x-show="recognition" x-cloak
                        type="button"
                        @click.prevent="toggleMic()"
                        class="absolute right-12 p-1.5 transition-colors z-10"
                        :class="listening ? 'text-red-500 animate-pulse' : 'text-neutral-600 hover:text-[#6F9CA2]'"
                        :title="listening ? 'Stop listening' : 'Voice search'"
                        aria-label="Voice search">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                </button>

                {{-- Submit button --}}
                <button type="submit" class="absolute right-3 p-1.5 text-neutral-600 hover:text-[#6F9CA2] transition-colors" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            {{-- Autocomplete dropdown --}}
            <div x-show="showResults && (results.length > 0 || (query.length >= 2 && !loading))" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute left-0 right-0 top-full mt-1 bg-white border border-neutral-200 rounded-lg shadow-lg z-50 overflow-hidden">
                <div x-show="results.length > 0" class="max-h-72 overflow-y-auto">
                    <ul class="py-1">
                        <template x-for="result in results" :key="result.id">
                            <li>
                                <a :href="result.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-neutral-50 transition-colors">
                                    <img :src="result.image" :alt="result.name" class="w-10 h-10 object-cover rounded">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm text-neutral-900 truncate" x-text="result.name"></div>
                                        <div class="text-xs text-neutral-600" x-text="result.category"></div>
                                    </div>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
                <div x-show="query.length >= 2 && results.length === 0 && !loading" class="px-4 py-4 text-center">
                    <p class="text-sm text-neutral-600">No results found</p>
                </div>
            </div>
        </div>

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
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Filters Sidebar -->
                <div class="lg:w-64 flex-shrink-0">
                    <div class="card p-4 sticky top-4">
                        <h3 class="font-semibold text-neutral-900 mb-4">Filters</h3>

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

                            <div class="flex gap-2">
                                <button type="submit" class="btn-primary flex-1 text-sm">Apply Price</button>
                                <a href="{{ route('search', ['q' => $query]) }}" class="btn-outline text-sm">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results -->
                <div class="flex-1">
                    @if($products->count())
                        <!-- Sort Bar -->
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-neutral-600">
                                Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }}
                            </p>
                            <form class="flex items-center gap-2">
                                <input type="hidden" name="q" value="{{ $query }}">
                                @if(request('category'))
                                    <input type="hidden" name="category" value="{{ request('category') }}">
                                @endif
                                @if(request('brand'))
                                    <input type="hidden" name="brand" value="{{ request('brand') }}">
                                @endif
                                <label class="text-sm text-neutral-600">Sort by:</label>
                                <select name="sort" class="form-input w-auto text-sm" onchange="this.form.submit()">
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
                                @include('partials.kk-product-card', ['product' => $product])
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
                                <a href="{{ route('category.show', $category) }}" class="btn-outline text-sm">
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
