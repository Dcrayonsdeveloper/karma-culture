<x-layouts.delivery>
    <x-slot name="title">Dashboard</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Dashboard</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage your delivery orders</p>
            </div>
        </div>
    </x-slot>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-primary-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Active Deliveries</p>
                <p class="text-xl sm:text-2xl font-bold text-primary-600">{{ $stats['active'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Delivered Today</p>
                <p class="text-xl sm:text-2xl font-bold text-success-600">{{ $stats['delivered_today'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[#6F9CA2]/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Total Delivered</p>
                <p class="text-xl sm:text-2xl font-bold text-[#6F9CA2]">{{ $stats['total_delivered'] }}</p>
            </div>
        </div>
        <a href="{{ route('delivery.returns.index') }}" class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4 hover:shadow-sm transition-shadow">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-warning-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Return Pickups</p>
                <p class="text-xl sm:text-2xl font-bold text-warning-600">{{ $stats['active_returns'] }}</p>
            </div>
        </a>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 mb-4 bg-white rounded-lg border border-neutral-100 p-1 w-fit">
        <a href="{{ route('delivery.dashboard', ['tab' => 'active']) }}"
           class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $tab === 'active' ? 'bg-primary-600 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
            Active ({{ $stats['active'] }})
        </a>
        <a href="{{ route('delivery.dashboard', ['tab' => 'delivered']) }}"
           class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $tab === 'delivered' ? 'bg-primary-600 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
            Delivered ({{ $stats['total_delivered'] }})
        </a>
        <a href="{{ route('delivery.dashboard', ['tab' => 'all']) }}"
           class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $tab === 'all' ? 'bg-primary-600 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
            All
        </a>
    </div>

    {{-- Orders Table --}}
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-neutral-900">{{ ucfirst($tab) }} Orders</h2>
        </div>

        @if($orders->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-100 text-left">
                            <th class="px-4 py-3 font-medium text-neutral-600">Order</th>
                            <th class="px-4 py-3 font-medium text-neutral-600">Customer</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 hidden md:table-cell">Delivery Address</th>
                            <th class="px-4 py-3 font-medium text-neutral-600">Status</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 hidden sm:table-cell">Items</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 text-right">Total</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 text-center hidden sm:table-cell">Payment</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-50">
                        @foreach($orders as $order)
                            @php
                                $statusColors = [
                                    'shipped' => 'bg-info-50 text-info-700',
                                    'out_for_delivery' => 'bg-warning-50 text-warning-700',
                                    'delivered' => 'bg-success-50 text-success-700',
                                    'cancelled' => 'bg-error-50 text-error-700',
                                ];
                                $address = $order->shipping_address_snapshot;
                            @endphp
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3">
                                    <a href="{{ route('delivery.orders.show', $order) }}" class="font-semibold text-primary-600 hover:text-primary-700">
                                        #{{ $order->order_number }}
                                    </a>
                                    <p class="text-xs text-neutral-600 mt-0.5">{{ $order->created_at->format('M d, h:i A') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-neutral-900">{{ $order->user->full_name }}</p>
                                    @if($address && !empty($address['phone']))
                                        <a href="tel:{{ $address['phone'] }}" class="text-xs text-primary-600 hover:text-primary-700">{{ $address['phone'] }}</a>
                                    @endif
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    @if($address)
                                        <p class="text-neutral-600 truncate max-w-50">{{ $address['address_line_1'] ?? '' }}{{ isset($address['city']) ? ', ' . $address['city'] : '' }}</p>
                                        <p class="text-xs text-neutral-600">{{ $address['state'] ?? '' }} {{ $address['postal_code'] ?? '' }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$order->status] ?? 'bg-neutral-50 text-neutral-700' }}">
                                        {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell text-neutral-600">
                                    {{ $order->items->count() }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-neutral-900">
                                    @price($order->total)
                                </td>
                                <td class="px-4 py-3 text-center hidden sm:table-cell">
                                    @if($order->payment_collected)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-success-50 text-success-700">Collected</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-warning-50 text-warning-700">Pending</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('delivery.orders.show', $order) }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-md transition-colors" title="View Details">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        @if($order->status === 'shipped')
                                            <form action="{{ route('delivery.orders.update-status', $order) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="out_for_delivery">
                                                <button type="submit" class="btn btn-primary px-2.5 py-1.5 text-xs">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                                    Pickup
                                                </button>
                                            </form>
                                        @elseif($order->status === 'out_for_delivery')
                                            <form action="{{ route('delivery.orders.update-status', $order) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="delivered">
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-success-600 hover:bg-success-700 rounded-md transition-colors">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Deliver
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-neutral-600 text-sm">No orders found in this tab.</p>
            </div>
        @endif
    </div>
</x-layouts.delivery>
