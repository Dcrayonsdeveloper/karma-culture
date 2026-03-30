<x-layouts.admin>
    <x-slot name="title">{{ $seller->store_name }} - Products</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.sellers.show', $seller) }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ $seller->store_name }}
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Products</h1>
    <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 1rem 0;">{{ $seller->store_name }}</p>

    <div class="card" style="overflow: hidden;">
        @if($products->total() > 0)
            <div style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $products->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Product</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">SKU</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Price</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Stock</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: #f6f6f7; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                        @if($product->image)
                                            <img src="{{ $product->image }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <svg width="20" height="20" fill="none" stroke="#616161" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $product->name }}</p>
                                        <p style="font-size: 12px; color: #616161; margin: 0;">{{ $product->category->name ?? 'Uncategorized' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $product->sku ?? '-' }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">@price($product->price)</td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($product->stock <= 0)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Out of stock</span>
                                @elseif($product->stock <= 10)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">{{ $product->stock }} left</span>
                                @else
                                    <span style="color: #616161;">{{ $product->stock }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($product->is_active)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f6f6f7; color: #616161;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <a href="{{ route('admin.products.edit', $product) }}" style="color: #616161; text-decoration: none;" title="Edit">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center; color: #616161;">
                                No products found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
