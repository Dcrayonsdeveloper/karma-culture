<x-layouts.app>
    <x-slot name="title">{{ request('category') ? ($categories->firstWhere('slug', request('category'))?->name ?? 'Products') : 'Kids Clothing & Accessories' }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        @php
            $metaCat = request('category') ? ($categories->firstWhere('slug', request('category'))?->name ?? null) : null;
            $metaBrand = request('brand') ? ($brands->firstWhere('slug', request('brand'))?->name ?? null) : null;
            $metaDesc = $metaCat
                ? "Shop {$metaCat} for kids at " . config('app.name') . ". Browse {$products->total()} products with great prices and free shipping."
                : ($metaBrand
                    ? "Shop {$metaBrand} kids' clothing at " . config('app.name') . ". Discover {$products->total()} products with great deals."
                    : "Shop kids' clothing, dresses, and accessories at " . config('app.name') . ". Browse {$products->total()} products for boys and girls.");
        @endphp
        <meta name="description" content="{{ $metaDesc }}">
        <link rel="canonical" href="{{ url('/products') }}">
        <meta property="og:title" content="{{ $metaCat ?? ($metaBrand ?? 'Kids Clothing & Accessories') }} - {{ config('app.name') }}">
        <meta property="og:description" content="{{ $metaDesc }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/products') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $metaCat ?? ($metaBrand ?? 'Kids Clothing & Accessories') }} - {{ config('app.name') }}">
        <meta name="twitter:description" content="{{ $metaDesc }}">
        @if(request()->anyFilled(['category', 'brand', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale', 'sort']))
        <meta name="robots" content="noindex, follow">
        @endif
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4 py-2.5">
            <x-breadcrumb :items="[['label' => 'Products', 'url' => null]]" />
        </div>
    </div>

    <!-- Header -->
    <div class="bg-[#F8931D]">
        <div class="container mx-auto px-4 py-6 md:py-8">
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">All Products</h1>
            <p class="text-white text-sm">Browse our wide range of kids' clothing & accessories</p>
            <p class="text-white/80 text-xs mt-2">{{ $products->total() }} products</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- Active Filters -->
        @if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale']))
            <div class="flex flex-wrap items-center gap-2 mb-5">
                <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Active Filters:</span>
                @if(request('category'))
                    @php $catName = $categories->firstWhere('slug', request('category'))?->name ?? request('category'); @endphp
                    <a href="{{ request()->fullUrlWithoutQuery('category') }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#6F9CA2]/5 text-[#5B878D] text-xs font-medium rounded-full border border-[#6F9CA2]/30 hover:bg-[#6F9CA2]/10 transition-colors">
                        {{ $catName }}
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                @if(request('brand'))
                    @foreach((array) request('brand') as $brandSlug)
                        @php $brandName = $brands->firstWhere('slug', $brandSlug)?->name ?? $brandSlug; @endphp
                        <a href="{{ request()->fullUrlWithoutQuery('brand') }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#6F9CA2]/5 text-[#5B878D] text-xs font-medium rounded-full border border-[#6F9CA2]/30 hover:bg-[#6F9CA2]/10 transition-colors">
                            {{ $brandName }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                @endif
                @if(request('min_price') || request('max_price'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#6F9CA2]/5 text-[#5B878D] text-xs font-medium rounded-full border border-[#6F9CA2]/30">
                        @price(request('min_price', 0)) - @price(request('max_price', '...'))
                    </span>
                @endif
                @if(request('rating'))
                    <span class="inline-flex items-center px-2.5 py-1 bg-[#6F9CA2]/5 text-[#5B878D] text-xs font-medium rounded-full border border-[#6F9CA2]/30">
                        {{ request('rating') }}+ Stars
                    </span>
                @endif
                @if(request('in_stock'))
                    <span class="inline-flex items-center px-2.5 py-1 bg-[#6F9CA2]/5 text-[#5B878D] text-xs font-medium rounded-full border border-[#6F9CA2]/30">In Stock</span>
                @endif
                @if(request('on_sale'))
                    <span class="inline-flex items-center px-2.5 py-1 bg-[#6F9CA2]/5 text-[#5B878D] text-xs font-medium rounded-full border border-[#6F9CA2]/30">On Sale</span>
                @endif
                <a href="{{ route('products.index') }}" class="text-xs text-neutral-600 hover:text-[#6F9CA2] underline ml-1">Clear all</a>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Filters Sidebar -->
            <aside class="lg:w-60 shrink-0" x-data="{ mobileOpen: false }">
                <!-- Mobile filter toggle -->
                <button @click="mobileOpen = true"
                        class="lg:hidden w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-neutral-200 rounded-lg text-sm font-medium text-neutral-700 hover:border-neutral-300 transition-colors mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filters
                    @if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale']))
                        <span class="w-5 h-5 bg-[#F8931D] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            {{ count(array_filter([request('category'), request('brand'), request('min_price'), request('max_price'), request('rating'), request('in_stock'), request('on_sale')])) }}
                        </span>
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
                            <button @click="mobileOpen = false" class="p-1 text-neutral-600 hover:text-neutral-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-4">
                            @include('products.partials.filters')
                        </div>
                    </div>
                </div>

                <!-- Desktop filters -->
                <div class="hidden lg:block">
                    @include('products.partials.filters')
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1 min-w-0">
                <!-- Sort Bar -->
                <div class="flex items-center justify-between mb-5 pb-4 border-b border-neutral-100">
                    <p class="text-sm text-neutral-600">
                        <span class="font-semibold text-neutral-900">{{ $products->total() }}</span> products found
                    </p>

                    <div class="flex items-center gap-2">
                        <label class="text-xs text-neutral-600 hidden sm:inline">Sort by:</label>
                        <select onchange="window.location.href = '{{ route('products.index') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), sort: this.value})"
                                class="text-sm py-1.5 pl-3 pr-8 border border-neutral-200 rounded-lg bg-white text-neutral-700 focus:outline-none focus:border-[#6F9CA2] cursor-pointer">
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Best Rating</option>
                            <option value="bestselling" {{ request('sort') === 'bestselling' ? 'selected' : '' }}>Bestselling</option>
                        </select>
                    </div>
                </div>

                @if($products->count())
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4">
                        @foreach($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="text-center py-20">
                        <div class="w-20 h-20 mx-auto mb-4 bg-neutral-100 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-900 mb-1">No products found</h3>
                        <p class="text-sm text-neutral-600 mb-5">Try adjusting your filters or browse all products.</p>
                        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear All Filters
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
