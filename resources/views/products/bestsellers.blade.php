<x-layouts.app>
    <x-slot name="title">Bestsellers - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Bestsellers', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">Bestsellers</h1>
            <p class="text-neutral-600">{{ $products->total() }} products</p>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">No bestsellers yet</h3>
                <p class="text-neutral-600 mb-4">Check back soon for popular products.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse All Products</a>
            </div>
        @endif
    </div>
</x-layouts.app>
