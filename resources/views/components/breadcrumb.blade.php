@props(['items' => []])

<nav {{ $attributes->merge(['class' => 'text-[13px]']) }} aria-label="Breadcrumb">
    <ol class="flex items-center flex-wrap gap-1.5" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="{{ url('/') }}" itemprop="item" class="inline-flex items-center gap-1 text-neutral-600 hover:text-primary-600 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span itemprop="name">Home</span>
            </a>
            <meta itemprop="position" content="1">
        </li>

        @foreach($items as $index => $item)
            <li class="flex items-center gap-1.5" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <svg class="w-3 h-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                @if($loop->last)
                    <span itemprop="name" class="text-neutral-800 font-medium">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" itemprop="item" class="text-neutral-600 hover:text-primary-600 transition-colors">
                        <span itemprop="name">{{ $item['label'] }}</span>
                    </a>
                @endif
                <meta itemprop="position" content="{{ $index + 2 }}">
            </li>
        @endforeach
    </ol>
</nav>

{{-- BreadcrumbList JSON-LD --}}
@php
$breadcrumbJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
    ],
];
foreach ($items as $i => $item) {
    $entry = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $item['label']];
    if (!empty($item['url'])) {
        $entry['item'] = $item['url'];
    }
    $breadcrumbJsonLd['itemListElement'][] = $entry;
}
@endphp
<script type="application/ld+json">
{!! json_encode($breadcrumbJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
