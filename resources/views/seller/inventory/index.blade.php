<x-layouts.seller>
    <x-slot name="title">Inventory</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Inventory</h1>
            <p class="text-neutral-600">Manage stock levels for your products</p>
        </div>
        <a href="{{ route('seller.inventory.low-stock') }}" class="btn-outline">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            Low Stock
        </a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Low Stock Threshold</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->sku }}</td>
                            <td class="px-4 py-3">
                                @if($product->stock_quantity <= 0)
                                    <span class="text-error-600 font-bold">{{ $product->stock_quantity }}</span>
                                @elseif($product->stock_quantity <= $product->low_stock_threshold)
                                    <span class="text-warning-600 font-bold">{{ $product->stock_quantity }}</span>
                                @else
                                    <span class="text-neutral-900">{{ $product->stock_quantity }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->low_stock_threshold }}</td>
                            <td class="px-4 py-3">
                                @if($product->stock_quantity <= 0)
                                    <span class="badge badge-error">Out of Stock</span>
                                @elseif($product->stock_quantity <= $product->low_stock_threshold)
                                    <span class="badge badge-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-success">In Stock</span>
                                @endif
                            </td>
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
                            <td colspan="6" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-1">No products yet</h3>
                                <p class="text-neutral-600">Add products to manage their inventory here.</p>
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
