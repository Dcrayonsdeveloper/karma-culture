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
    {{-- The same block as the All Products header, markup for markup: the
         brand's brown, flat, and sized by its padding.

         It used to be its own thing - a 224px min-height box centring its copy,
         with a per-page <style> block to hold that height up. Against the
         All Products header, which is only as tall as its own padding, the two
         read as different pages of different sites. Every category gets this
         one colour: a per-category tint would make the banner a decoration that
         changes meaninglessly from page to page.

         line-clamp-2 stays because a category description is admin-entered and
         can run long, where the All Products strap is a fixed short sentence.
         It is invisible on anything shorter. --}}
    <div class="bg-kk-brown-dark">
        <div class="container mx-auto px-4 py-6 md:py-8">
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-white text-sm max-w-lg line-clamp-2">{{ $category->description }}</p>
            @endif
            <p class="text-white/80 text-xs mt-2">{{ $products->total() }} products</p>
        </div>
    </div>

    @include('partials.product-listing')

</x-layouts.app>
