<x-layouts.app>
    <x-slot name="title">Bestsellers - {{ config('app.name') }}</x-slot>
    <x-slot name="meta">
        <meta name="description" content="Our most-bought pieces at Karmaa Kulture - the shirts, kurtas and trousers customers order again and again.">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:title" content="Bestsellers - {{ config('app.name') }}">
        <meta property="og:description" content="Our most-bought pieces at Karmaa Kulture - the shirts, kurtas and trousers customers order again and again.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary_large_image">
    </x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Bestsellers', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 pt-6">
        <h1 class="text-2xl font-bold text-neutral-900">Bestsellers</h1>
        <p class="text-sm text-neutral-600">{{ number_format($products->total()) }} products</p>
    </div>

    @include('partials.product-listing')

</x-layouts.app>
