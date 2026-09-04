@props(['productId'])

@php
    // The cards used to fall back to /images/placeholder.jpg, which is not in
    // public/images - so a product without a picture 404'd into a blank tile.
    // This is the placeholder the rest of the catalogue already lands on.
    $placeholder = asset_v('images/no-product-image.svg');
@endphp

<div x-data="similarProducts({{ $productId }})" x-init="load()" x-show="products.length > 0" x-cloak class="mt-10">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Similar Products</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <template x-for="product in products" :key="product.id">
            <a :href="product.url" class="group block bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-3">
                {{-- Single image with object-fit cover --}}
                <div class="aspect-[4/5] overflow-hidden rounded-md mb-2 bg-neutral-50">
                    <img :src="product.image || '{{ $placeholder }}'" 
                         :alt="product.name" 
                         loading="lazy" 
                         decoding="async"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
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
function similarProducts(productId) {
    return {
        products: [],
        async load() {
            try {
                const res = await fetch('/recommendations/similar/' + productId);
                const data = await res.json();
                this.products = data.data || [];
            } catch (e) {}
        }
    }
}
</script>
