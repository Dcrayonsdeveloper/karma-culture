@php
    $festivalDesc = $festivalSale->description
        ?: 'Save '.rtrim(rtrim(number_format((float) $festivalSale->discount_percent, 2), '0'), '.').'% on selected styles at Karmaa Kulture.';
    $festivalPct = rtrim(rtrim(number_format((float) $festivalSale->discount_percent, 2), '0'), '.');
@endphp

<x-layouts.app>
    <x-slot name="title">{{ $festivalSale->name }} - {{ config('app.name') }}</x-slot>
    <x-slot name="meta">
        <meta name="description" content="{{ Str::limit(strip_tags($festivalDesc), 155) }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:title" content="{{ $festivalSale->name }} - {{ config('app.name') }}">
        <meta property="og:description" content="{{ Str::limit(strip_tags($festivalDesc), 155) }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        @if($festivalSale->bannerUrl())
            <meta property="og:image" content="{{ $festivalSale->bannerUrl() }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
    </x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => $festivalSale->name, 'url' => null]]" />
        </div>
    </div>

    {{-- The banner the admin uploaded, at its own aspect ratio. No fixed
         height: festival artwork is designed as one picture and cropping it to
         a band cuts the lettering off, which is usually the whole design. --}}
    @if($festivalSale->bannerUrl())
        <div class="container mx-auto px-4 pt-4">
            <picture>
                @if($festivalSale->banner_mobile_path)
                    <source media="(max-width: 640px)" srcset="{{ $festivalSale->bannerMobileUrl() }}">
                @endif
                <img src="{{ $festivalSale->bannerUrl() }}"
                     alt="{{ $festivalSale->name }}"
                     class="w-full rounded-2xl block"
                     fetchpriority="high">
            </picture>
        </div>
    @endif

    <div class="container mx-auto px-4 pt-6">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h1 class="text-2xl font-bold text-neutral-900">{{ $festivalSale->name }}</h1>
            <span class="text-sm font-bold text-white rounded-full px-3 py-1" style="background:#F8931D;">
                {{ $festivalPct }}% OFF
            </span>
        </div>

        @if($festivalSale->description)
            <p class="text-sm text-neutral-600 mt-2 max-w-2xl">{{ $festivalSale->description }}</p>
        @endif

        <p class="text-sm text-neutral-600 mt-1">{{ number_format($products->total()) }} products in this sale</p>
    </div>

    @include('partials.product-listing')

</x-layouts.app>
