<x-layouts.seller>
    <x-slot name="title">Orders</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Orders</h1>
            <p class="text-neutral-600">Manage orders for your products</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-4">
            <p class="text-sm text-neutral-600">Confirmed</p>
            <p class="text-2xl font-bold text-warning-600">{{ number_format($stats['confirmed']) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-sm text-neutral-600">Processing</p>
            <p class="text-2xl font-bold text-info-600">{{ number_format($stats['processing']) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-sm text-neutral-600">Shipped</p>
            <p class="text-2xl font-bold text-success-600">{{ number_format($stats['shipped']) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-6">
        <form action="{{ route('seller.orders.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by order number or customer..."
                       class="form-input w-full">
            </div>
            <select name="status" class="form-input w-auto">
                <option value="">All Status</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="packed" {{ request('status') === 'packed' ? 'selected' : '' }}>Packed</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="out_for_delivery" {{ request('status') === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="btn-outline">Filter</button>
            <a href="{{ route('seller.orders.index') }}" class="text-neutral-600 hover:text-neutral-900">Reset</a>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Your Earnings</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($orders as $order)
                        @php
                            $sellerItems = $order->items;
                            $sellerTotal = $sellerItems->sum('total');
                        @endphp
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('seller.orders.show', $order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $order->user->full_name ?? 'Guest' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $sellerItems->count() }} item(s)
                            </td>
                            <td class="px-4 py-3 font-medium">
                                @price($sellerTotal)
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ in_array($order->status, ['completed','delivered']) ? 'badge-success' : ($order->status === 'confirmed' ? 'badge-warning' : ($order->status === 'cancelled' ? 'badge-error' : 'badge-info')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $order->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('seller.orders.show', $order) }}" class="btn-outline btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-2">No orders yet</h3>
                                <p class="text-neutral-600">Orders for your products will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.seller>
