<x-layouts.admin>
    <x-slot name="title">{{ $customer->full_name }} - Orders</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.customers.show', $customer) }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ $customer->full_name }}
        </a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Orders by {{ $customer->full_name }}</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">{{ $orders->total() }} orders total</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card" style="overflow: hidden;">
        @if($orders->total() > 0)
            <div style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $orders->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Order</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Date</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Items</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Payment</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161;">Total</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem;">
                                <a href="{{ route('admin.orders.show', $order) }}" style="font-weight: 500; color: #005bd3; text-decoration: none;">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">
                                {{ $order->created_at->format('M d, Y') }}
                                <span style="display: block; font-size: 12px; color: #616161;">{{ $order->created_at->format('H:i') }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $order->items->count() }} items</td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($order->status === 'completed')
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                @elseif($order->status === 'pending')
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                @elseif($order->status === 'cancelled')
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($order->payment_status === 'paid')
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">{{ ucfirst($order->payment_status) }}</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">{{ ucfirst($order->payment_status) }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; font-weight: 500; text-align: right; color: #303030;">@price($order->total)</td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <a href="{{ route('admin.orders.show', $order) }}" style="color: #616161; text-decoration: none;" title="View Order">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <svg width="48" height="48" fill="none" stroke="#c9cccf" viewBox="0 0 24 24" style="margin: 0 auto 1rem auto; display: block;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <h3 style="font-size: 14px; font-weight: 600; color: #303030; margin: 0 0 0.25rem 0;">No orders yet</h3>
                                <p style="font-size: 13px; color: #616161; margin: 0;">This customer hasn't placed any orders.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
