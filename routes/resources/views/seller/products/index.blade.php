<x-layouts.seller>
    <x-slot name="title">My Products</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">My Products</h1>
            <p class="text-neutral-600">Manage your product catalog</p>
        </div>
        <a href="{{ route('seller.products.create') }}" class="btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </a>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-6">
        <form action="{{ route('seller.products.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search products..."
                       class="form-input w-full">
            </div>
            <select name="status" class="form-input w-auto">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <select name="category" class="form-input w-auto">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn-outline">Filter</button>
            <a href="{{ route('seller.products.index') }}" class="text-neutral-600 hover:text-neutral-900">Reset</a>
        </form>
    </div>

    <!-- Products Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                         class="w-12 h-12 rounded-lg object-cover">
                                    <div>
                                        <a href="{{ route('seller.products.edit', $product) }}"
                                           class="font-medium text-neutral-900 hover:text-primary-600">
                                            {{ $product->name }}
                                        </a>
                                        <p class="text-xs text-neutral-600">{{ $product->sales_count }} sold</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->sku }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->category->name }}</td>
                            <td class="px-4 py-3">
                                @if($product->mrp > $product->price)
                                    <span class="font-medium text-primary-600">@price($product->price)</span>
                                    <span class="text-xs text-neutral-600 line-through block">@price($product->mrp)</span>
                                @else
                                    <span class="font-medium">@price($product->price)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($product->stock_quantity <= 0)
                                    <span class="text-error-600 font-medium">Out of stock</span>
                                @elseif($product->stock_quantity <= 10)
                                    <span class="text-warning-600 font-medium">{{ $product->stock_quantity }} left</span>
                                @else
                                    <span class="text-neutral-600">{{ $product->stock_quantity }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($product->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-neutral">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('product.show', $product) }}" target="_blank"
                                       class="p-2 text-neutral-600 hover:text-neutral-600" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('seller.products.edit', $product) }}"
                                       class="p-2 text-neutral-600 hover:text-primary-600" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('seller.products.destroy', $product) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-neutral-600 hover:text-error-600" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-2">No products yet</h3>
                                <p class="text-neutral-600 mb-4">Start by adding your first product to the catalog.</p>
                                <a href="{{ route('seller.products.create') }}" class="btn-primary">Add Product</a>
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
