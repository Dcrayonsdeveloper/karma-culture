<x-layouts.app>
    <x-slot name="title">Deals - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Deals', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">Deals & Discounts</h1>
            <p class="text-neutral-600">{{ $products->total() }} products on sale</p>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">No deals available</h3>
                <p class="text-neutral-600 mb-4">Check back soon for new deals and discounts.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse Products</a>
            </div>
        @endif
    </div>
</x-layouts.app>
