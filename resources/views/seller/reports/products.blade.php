<x-layouts.seller>
    <x-slot name="title">Product Report</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Product Report</h1>
            <p class="text-neutral-600">Product performance by sales</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('seller.reports.sales') }}" class="btn-outline">Sales</a>
            <a href="{{ route('seller.reports.traffic') }}" class="btn-outline">Traffic</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Units Sold</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Views</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Rating</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->sku }}</td>
                            <td class="px-4 py-3 text-sm">@price($product->price)</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ $product->sales_count }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $product->view_count }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($product->rating > 0)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        {{ number_format($product->rating, 1) }}
                                    </span>
                                @else
                                    <span class="text-neutral-600">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-neutral-600">No products found.</td>
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
