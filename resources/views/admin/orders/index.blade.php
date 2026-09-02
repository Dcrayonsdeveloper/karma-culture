<x-layouts.admin>
    <x-slot name="title">Orders</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Orders</h1>
        </div>
    </x-slot>

    {{-- Stats row --}}
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Confirmed</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['confirmed']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Processing</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['processing']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Shipped</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #7c3aed;">{{ number_format($stats['shipped']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Completed</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['completed']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Cancelled</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['cancelled']) }}</p>
        </div>
    </div>

    {{-- Orders card --}}
    <div class="card">
        {{-- Tab filters --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center;">
            <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
            @foreach(['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $st)
                <a href="{{ route('admin.orders.index', ['status' => $st] + request()->except('status', 'page')) }}"
                   style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === $st ? '#303030' : 'transparent' }}; color: {{ request('status') === $st ? '#303030' : '#616161' }}; margin-bottom: -1px;">{{ ucfirst($st) }}</a>
            @endforeach
        </div>

        {{-- Search + Filter bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.orders.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search orders"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            @if(request()->hasAny(['search', 'status', 'payment_status', 'date_from', 'date_to']))
                <a href="{{ route('admin.orders.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 0.5rem 0.75rem 0.5rem 1rem;">Order</th>
                        <th style="text-align: left;">Date</th>
                        <th style="text-align: left;">Customer</th>
                        <th style="text-align: left;">Payment</th>
                        <th style="text-align: left;">Fulfillment</th>
                        <th style="text-align: right;">Items</th>
                        <th style="text-align: right; padding: 0.5rem 1rem 0.5rem 0.75rem;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td style="padding: 0.625rem 0.75rem 0.625rem 1rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #005bd3;">{{ $order->order_number }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $order->created_at->format('M d, Y') }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #303030;">{{ $order->user->full_name ?? 'Guest' }}</span>
                            </td>
                            <td>
                                @php
                                    $paymentBadge = match($order->payment_status) {
                                        'paid' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'failed' => 'badge-error',
                                        'refunded' => 'badge-neutral',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $paymentBadge }}">{{ ucfirst($order->payment_status) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusBadge = match($order->status) {
                                        'delivered', 'completed' => 'badge-success',
                                        'confirmed' => 'badge-warning',
                                        'processing', 'packed' => 'badge-info',
                                        'shipped', 'out_for_delivery' => 'badge-info',
                                        'cancelled', 'returned' => 'badge-error',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 13px; color: #616161;">{{ $order->items->count() }} items</span>
                            </td>
                            <td style="text-align: right; padding: 0.625rem 1rem 0.625rem 0.75rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">@price($order->total)</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No orders found</h3>
                                    <p style="font-size: 13px; color: #616161;">
                                        @if(request()->hasAny(['search', 'status', 'payment_status', 'date_from', 'date_to']))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Orders will appear here when customers place them.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
