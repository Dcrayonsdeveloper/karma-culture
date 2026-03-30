<x-layouts.app>
    <x-slot name="title">{{ $seller->business_name }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $seller->description }}">
    @endpush

    <!-- Seller Header -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[
                ['label' => 'Sellers', 'url' => null],
                ['label' => $seller->business_name, 'url' => null]
            ]" />
        </div>

        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center gap-6">
                <div class="w-24 h-24 bg-primary-100 rounded-lg flex items-center justify-center overflow-hidden">
                    @if($seller->logo_url)
                        <img src="{{ $seller->logo_url }}" alt="{{ $seller->business_name }}" class="max-w-full max-h-full object-contain">
                    @else
                        <span class="text-3xl font-bold text-primary-600">{{ substr($seller->business_name, 0, 1) }}</span>
                    @endif
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-neutral-900">{{ $seller->business_name }}</h1>
                    @if($seller->description)
                        <p class="text-neutral-600 mt-2">{{ $seller->description }}</p>
                    @endif
                    <div class="flex items-center gap-4 mt-2 text-sm text-neutral-600">
                        @if($seller->rating)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                {{ number_format($seller->rating, 1) }} ({{ $seller->total_reviews }} reviews)
                            </span>
                        @endif
                        <span>{{ $seller->total_products ?? $products->total() }} products</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <p class="text-neutral-600">{{ $products->total() }} products</p>

            <select onchange="window.location.href = '{{ route('sellers.show', $seller) }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), sort: this.value})"
                    class="form-input text-sm py-2 w-auto">
                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Best Rating</option>
                <option value="bestselling" {{ request('sort') === 'bestselling' ? 'selected' : '' }}>Bestselling</option>
            </select>
        </div>

        @if($products->count())
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                @foreach($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">No products found</h3>
                <p class="text-neutral-600 mb-4">This seller doesn't have any products yet.</p>
                <a href="{{ route('home') }}" class="btn-primary">Back to Home</a>
            </div>
        @endif
    </div>
</x-layouts.app>
