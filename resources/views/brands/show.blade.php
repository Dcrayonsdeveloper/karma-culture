<x-layouts.app>
    <x-slot name="title">{{ $brand->name }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $brand->meta_description ?? $brand->description }}">
    @endpush

    <!-- Brand Header -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[
                ['label' => 'Brands', 'url' => route('brands.index')],
                ['label' => $brand->name, 'url' => null]
            ]" />
        </div>

        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center gap-6">
                @if($brand->logo_url)
                    <div class="w-24 h-24 bg-neutral-100 rounded-lg p-4 flex items-center justify-center">
                        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="max-w-full max-h-full object-contain">
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-neutral-900">{{ $brand->name }}</h1>
                    @if($brand->description)
                        <p class="text-neutral-600 mt-2">{{ $brand->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <p class="text-neutral-600">{{ $products->total() }} products</p>

            <select onchange="window.location.href = '{{ route('brands.show', $brand) }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), sort: this.value})"
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
                <p class="text-neutral-600 mb-4">This brand doesn't have any products yet.</p>
                <a href="{{ route('brands.index') }}" class="btn-primary">View All Brands</a>
            </div>
        @endif
    </div>
</x-layouts.app>
