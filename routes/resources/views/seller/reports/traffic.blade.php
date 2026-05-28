<x-layouts.seller>
    <x-slot name="title">Traffic Report</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Traffic Report</h1>
            <p class="text-neutral-600">Product views and engagement</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('seller.reports.sales') }}" class="btn-outline">Sales</a>
            <a href="{{ route('seller.reports.products') }}" class="btn-outline">Products</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Views</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Sales</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Conversion</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Wishlisted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ $product->view_count }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->sales_count }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $product->view_count > 0 ? number_format(($product->sales_count / $product->view_count) * 100, 1) . '%' : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->wishlist_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-neutral-600">No products found.</td>
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
