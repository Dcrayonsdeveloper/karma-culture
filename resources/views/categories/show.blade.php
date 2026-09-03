<x-layouts.app>
    <x-slot name="title">{{ $category->name }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        @php
            $catDesc = $category->meta_description ?? $category->description ?? "Shop {$category->name} for kids at " . config('app.name') . ". Browse {$products->total()} products with great prices.";
        @endphp
        <meta name="description" content="{{ Str::limit(strip_tags($catDesc), 160) }}">
        <link rel="canonical" href="{{ route('category.show', $category->slug) }}">
        <meta property="og:title" content="{{ $category->name }} - {{ config('app.name') }}">
        <meta property="og:description" content="{{ Str::limit(strip_tags($catDesc), 160) }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('category.show', $category->slug) }}">
        @if($category->image_url)
        <meta property="og:image" content="{{ asset_v('storage/' . $category->image_url) }}">
        @endif
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $category->name }} - {{ config('app.name') }}">
        <meta name="twitter:description" content="{{ Str::limit(strip_tags($catDesc), 160) }}">
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4 py-2.5">
            <x-breadcrumb :items="$breadcrumbs" />
        </div>
    </div>

    <!-- Category Header: mobile orange gradient / desktop banner image -->
    <style>
        /* min-height, not height: a category name that wraps on a narrow
           phone used to push the product count below the frame, where
           overflow-hidden clipped it. The inner box carries the same minimum
           so justify-center still centres the copy whenever there is room. */
        .cat-banner { min-height: 150px; }
        .cat-banner-inner { padding: 25px; min-height: 150px; }
        @media(min-width:640px) {
            .cat-banner { min-height: 224px; }
            .cat-banner-inner { padding: 0 1rem; min-height: 224px; }
        }
        /* The banner art is 1440px wide, so cover sliced the children off its
           right edge on every screen narrower than that. It is contained now.
           If the file ever goes missing, the orange gradient underneath is a
           finished design on its own - it is exactly what mobile shows - so the
           frame degrades to that rather than to the site-wide broken plate. */
        .cat-banner .kk-media.is-broken .kk-media__fallback { display: none; }
    </style>
    <div class="relative overflow-hidden cat-banner" style="background: linear-gradient(135deg, #F8931D 0%, #E07E0A 100%);">
        {{-- The frame is positioned inline because .kk-media sets position and
             background itself, and it wins over the Tailwind utilities. --}}
        <div class="kk-media hidden sm:block" style="position: absolute; inset: 0; background: transparent;">
            <img class="kk-media__fill" src="{{ asset_v('images/Forever.png') }}" alt="" aria-hidden="true">
            {{-- Same art on every category page, so it carries no meaning of its
                 own: the heading below names the category. --}}
            <img src="{{ asset_v('images/Forever.png') }}" alt="">
        </div>
        <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
        <div class="relative container mx-auto h-full flex flex-col justify-center cat-banner-inner">
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-sm max-w-lg line-clamp-2" style="color: rgba(255,255,255,0.9);">{{ $category->description }}</p>
            @endif
            <p class="text-xs mt-2" style="color: rgba(255,255,255,0.8);">{{ $products->total() }} products</p>
        </div>
    </div>

    @include('partials.product-listing')

</x-layouts.app>
