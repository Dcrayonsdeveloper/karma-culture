<x-layouts.app>
    <x-slot name="title">All Brands - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Brands', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-neutral-900 mb-8">Shop by Brand</h1>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            @foreach($brands as $brand)
                <a href="{{ route('brands.show', $brand) }}" class="group card overflow-hidden">
                    <div class="aspect-square bg-neutral-100 overflow-hidden flex items-center justify-center p-4">
                        @if($brand->logo_url)
                            <img src="{{ $brand->logo_url }}"
                                 alt="{{ $brand->name }}"
                                 class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="text-2xl font-bold text-neutral-300">{{ substr($brand->name, 0, 2) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-semibold text-neutral-900 group-hover:text-primary-500 mb-1">
                            {{ $brand->name }}
                        </h3>
                        <p class="text-sm text-neutral-600">{{ $brand->products_count }} products</p>
                    </div>
                </a>
            @endforeach
        </div>

        @if($brands->isEmpty())
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">No brands found</h3>
                <p class="text-neutral-600">Check back later for more brands.</p>
            </div>
        @endif
    </div>
</x-layouts.app>
