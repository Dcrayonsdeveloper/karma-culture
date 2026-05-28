<x-layouts.seller>
    <x-slot name="title">Low Stock</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.inventory.index') }}" class="hover:text-primary-600">Inventory</a>
        <span>/</span>
        <span>Low Stock</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Low Stock Products</h1>
            <p class="text-neutral-600">Products that need restocking</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Current Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Threshold</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->sku }}</td>
                            <td class="px-4 py-3">
                                <span class="text-warning-600 font-bold">{{ $product->stock_quantity }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->low_stock_threshold }}</td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('seller.inventory.update-stock', $product) }}" method="POST" class="flex items-center justify-end gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="stock_quantity" value="{{ $product->stock_quantity }}" min="0"
                                           class="form-input w-24 text-sm text-center">
                                    <button type="submit" class="btn-primary text-sm px-3 py-1.5">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-1">All stocked up!</h3>
                                <p class="text-neutral-600">No products are below their low stock threshold.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-layouts.seller>
