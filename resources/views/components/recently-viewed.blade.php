@props(['limit' => 10])

@php
    // The cards used to fall back to /images/placeholder.jpg, which is not in
    // public/images - so a product without a picture 404'd into a blank tile.
    // This is the placeholder the rest of the catalogue already lands on.
    $placeholder = asset_v('images/no-product-image.svg');
@endphp

<div x-data="recentlyViewed()" x-init="load()" x-show="products.length > 0" x-cloak class="mt-8">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Recently Viewed</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <template x-for="product in products" :key="product.id">
            <a :href="'/products/' + product.slug" class="group block bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-3">
                {{-- Media well: the square keeps every card in the row the same
                     height, but the shot inside is contained, so a portrait or a
                     wide product photo is shown whole instead of being cropped to
                     the frame. The blurred copy behind it fills what contain
                     leaves over, and a URL that 404s falls back once and then gets
                     the frame's broken state rather than an empty rectangle. --}}
                <div class="kk-media kk-media--zoom aspect-square overflow-hidden rounded-md mb-2">
                    <img class="kk-media__fill" :src="product.image || '{{ $placeholder }}'" alt="" aria-hidden="true" loading="lazy" decoding="async">
                    <img :src="product.image || '{{ $placeholder }}'" :alt="product.name" data-fallback="{{ $placeholder }}" loading="lazy" decoding="async">
                    <span class="kk-media__fallback" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <circle cx="8.5" cy="9.5" r="1.5"/>
                            <path d="M21 15l-5-5L5 20"/>
                        </svg>
                    </span>
                </div>
                <h3 x-text="product.name" class="text-sm font-medium text-gray-900 truncate"></h3>
                <div class="flex items-center gap-2 mt-1">
                    <span x-text="'₹' + product.price" class="text-sm font-bold text-primary-600"></span>
                    <span x-show="product.mrp > product.price" x-text="'₹' + product.mrp" class="text-xs text-gray-400 line-through"></span>
                </div>
            </a>
        </template>
    </div>
</div>

<script>
function recentlyViewed() {
    return {
        products: [],
        async load() {
            try {
                const res = await fetch('/recommendations/recently-viewed?limit={{ $limit }}');
                const data = await res.json();
                this.products = data.data || [];
            } catch (e) {}
        }
    }
}
</script>
