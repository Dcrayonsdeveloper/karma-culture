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

    <!-- Category Header -->
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
    </style>
    {{-- The shop's orange, flat, and the same on every category.

         It is deliberately the exact value the All Products header uses
         (bg-[#F8931D]) rather than a near-match, so opening a category does not
         look like landing on a different site. Every category gets this one
         colour: a per-category tint would make the banner a decoration that
         changes meaninglessly from page to page.

         Two things had made it look otherwise. It used to stack an orange base
         under a left-to-right black scrim, which was there to keep white text
         readable over banner art - and that art is gone (it was ForeverKids
         branding, inherited from the codebase this shop was forked from: a
         cartoon of children on an adult fashion banner, removed rather than
         restored because restoring it would put another brand's mark on this
         one). With nothing left to darken, the scrim only shaded the left half
         and gave the banner the brown-to-orange look. --}}
    <div class="relative overflow-hidden cat-banner" style="background: #F8931D;">
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
