@props(['productId'])

<div x-data="similarProducts({{ $productId }})" x-init="load()" x-show="products.length > 0" x-cloak class="mt-10">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Similar Products</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <template x-for="product in products" :key="product.id">
            <a :href="'/products/' + product.slug" class="group block bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-3">
                <div class="aspect-square overflow-hidden rounded-md bg-gray-100 mb-2">
                    <img :src="product.image || '/images/placeholder.jpg'" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy">
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
