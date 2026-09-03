<x-layouts.app>
    @php
        $kkCatSlug = $filterPanel['values']['category'];
        $kkCatName = $kkCatSlug ? ($filterPanel['categories']->firstWhere('slug', $kkCatSlug)?->name ?? null) : null;
    @endphp

    <x-slot name="title">{{ $listingTitle ?? $kkCatName ?? ($kkCatSlug ? 'Products' : 'Kids Clothing & Accessories') }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        @php
            $metaCat = $kkCatName;
            $metaBrand = $filterPanel['values']['brand']
                ? ($filterPanel['brands']->firstWhere('slug', $filterPanel['values']['brand'][0])?->name ?? null)
                : null;
            $metaDesc = $metaCat
                ? "Shop {$metaCat} for kids at " . config('app.name') . ". Browse {$products->total()} products with great prices and free shipping."
                : ($metaBrand
                    ? "Shop {$metaBrand} kids' clothing at " . config('app.name') . ". Discover {$products->total()} products with great deals."
                    : "Shop kids' clothing, dresses, and accessories at " . config('app.name') . ". Browse {$products->total()} products for boys and girls.");
        @endphp
        <meta name="description" content="{{ $metaDesc }}">
        <link rel="canonical" href="{{ route('shop') }}">
        <meta property="og:title" content="{{ $metaCat ?? ($metaBrand ?? 'Kids Clothing & Accessories') }} - {{ config('app.name') }}">
        <meta property="og:description" content="{{ $metaDesc }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('shop') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $metaCat ?? ($metaBrand ?? 'Kids Clothing & Accessories') }} - {{ config('app.name') }}">
        <meta name="twitter:description" content="{{ $metaDesc }}">
        @if(request()->anyFilled(['category', 'brand', 'size', 'colour', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale', 'sort']))
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
            {{-- A collection page reuses this listing whole, so the heading has
                 to come from the caller rather than being hard-wired to the shop. --}}
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">{{ $listingTitle ?? 'All Products' }}</h1>
            <p class="text-white text-sm">{{ $listingDescription ?? 'Browse our full range of men\'s and women\'s clothing' }}</p>
            <p class="text-white/80 text-xs mt-2">{{ $products->total() }} products</p>
        </div>
    </div>

    @include('partials.product-listing')

</x-layouts.app>
