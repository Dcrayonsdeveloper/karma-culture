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
                    {{-- The logo stays contained on the flat box - it is a mark,
                         not a photo. A missing file used to leave the box empty,
                         so it falls back to the same initials the brand grid
                         shows; x-init catches a logo that already failed before
                         Alpine booted. --}}
                    <div class="w-24 h-24 bg-neutral-100 rounded-lg p-4 flex items-center justify-center"
                         x-data="{ logoBroken: false }">
                        <img src="{{ $brand->logo_src }}" alt="{{ $brand->name }}"
                             class="max-w-full max-h-full object-contain"
                             x-init="logoBroken = $el.complete && $el.naturalWidth === 0"
                             x-on:error="logoBroken = true"
                             x-show="!logoBroken">
                        <span class="text-2xl font-bold text-neutral-300"
                              x-show="logoBroken" x-cloak>{{ substr($brand->name, 0, 2) }}</span>
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

    @include('partials.product-listing')

</x-layouts.app>
