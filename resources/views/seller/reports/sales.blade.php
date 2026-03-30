<x-layouts.seller>
    <x-slot name="title">Sales Report</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Sales Report</h1>
            <p class="text-neutral-600">Overview of your sales performance</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('seller.reports.products') }}" class="btn-outline">Products</a>
            <a href="{{ route('seller.reports.traffic') }}" class="btn-outline">Traffic</a>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card p-4 mb-6">
        <form action="{{ route('seller.reports.sales') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }}"
                       class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ \Carbon\Carbon::parse($endDate)->format('Y-m-d') }}"
                       class="form-input">
            </div>
            <button type="submit" class="btn-primary">Apply</button>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card p-6">
            <p class="text-sm text-neutral-600 mb-1">Total Sales</p>
            <p class="text-2xl font-bold text-neutral-900">@price($stats['total_sales'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600 mb-1">Total Orders</p>
            <p class="text-2xl font-bold text-neutral-900">{{ $stats['total_orders'] }}</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600 mb-1">Average Order Value</p>
            <p class="text-2xl font-bold text-neutral-900">@price($stats['average_order'])</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-neutral-200">
            <h2 class="font-semibold text-neutral-900">Orders</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($orders as $order)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">
                                <a href="{{ route('seller.orders.show', $order) }}" class="text-primary-600 hover:text-primary-700">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $order->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $order->status === 'completed' ? 'badge-success' : ($order->status === 'cancelled' ? 'badge-error' : 'badge-warning') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">@price($order->total)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-neutral-600">
                                No orders found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.seller>
