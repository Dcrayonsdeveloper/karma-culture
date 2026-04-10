<x-layouts.admin>
    <x-slot name="title">Dashboard</x-slot>

    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = auth('admin')->user()->first_name ?? auth('admin')->user()->full_name ?? 'there';
    @endphp

    <!-- Shopify-style top bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $greeting }}, {{ $firstName }}</h1>
    </div>

    <!-- Date range pills (Shopify style - inline, no card wrapper) -->
    <div style="margin-bottom: 1.25rem;" x-data>
        <form method="GET" action="{{ route('admin.dashboard') }}" x-ref="filterForm" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <input type="hidden" name="start_date" x-ref="startDate" value="{{ request('start_date') }}">
            <input type="hidden" name="end_date" x-ref="endDate" value="{{ request('end_date') }}">

            <button type="button" @click="$refs.startDate.value='{{ today()->format('Y-m-d') }}'; $refs.endDate.value='{{ today()->format('Y-m-d') }}'; $refs.filterForm.submit()"
                    style="padding: 0.375rem 0.75rem; font-size: 12px; font-weight: 500; border-radius: 50rem; cursor: pointer; border: none; {{ request('start_date') == today()->format('Y-m-d') && request('end_date') == today()->format('Y-m-d') ? 'background:#303030;color:white;' : 'background:white;color:#616161;border:1px solid #d4d4d4;' }}">
                Today
            </button>
            <button type="button" @click="$refs.startDate.value='{{ now()->subDays(6)->format('Y-m-d') }}'; $refs.endDate.value='{{ today()->format('Y-m-d') }}'; $refs.filterForm.submit()"
                    style="padding: 0.375rem 0.75rem; font-size: 12px; font-weight: 500; border-radius: 50rem; cursor: pointer; border: none; {{ request('start_date') == now()->subDays(6)->format('Y-m-d') && request('end_date') == today()->format('Y-m-d') ? 'background:#303030;color:white;' : 'background:white;color:#616161;border:1px solid #d4d4d4;' }}">
                Last 7 days
            </button>
            <button type="button" @click="$refs.startDate.value='{{ now()->subDays(29)->format('Y-m-d') }}'; $refs.endDate.value='{{ today()->format('Y-m-d') }}'; $refs.filterForm.submit()"
                    style="padding: 0.375rem 0.75rem; font-size: 12px; font-weight: 500; border-radius: 50rem; cursor: pointer; border: none; {{ request('start_date') == now()->subDays(29)->format('Y-m-d') && request('end_date') == today()->format('Y-m-d') ? 'background:#303030;color:white;' : 'background:white;color:#616161;border:1px solid #d4d4d4;' }}">
                Last 30 days
            </button>
            <button type="button" @click="$refs.startDate.value='{{ now()->startOfMonth()->format('Y-m-d') }}'; $refs.endDate.value='{{ today()->format('Y-m-d') }}'; $refs.filterForm.submit()"
                    style="padding: 0.375rem 0.75rem; font-size: 12px; font-weight: 500; border-radius: 50rem; cursor: pointer; border: none; {{ request('start_date') == now()->startOfMonth()->format('Y-m-d') && request('end_date') == today()->format('Y-m-d') ? 'background:#303030;color:white;' : 'background:white;color:#616161;border:1px solid #d4d4d4;' }}">
                This month
            </button>
            <button type="button" @click="$refs.startDate.value='{{ now()->startOfYear()->format('Y-m-d') }}'; $refs.endDate.value='{{ today()->format('Y-m-d') }}'; $refs.filterForm.submit()"
                    style="padding: 0.375rem 0.75rem; font-size: 12px; font-weight: 500; border-radius: 50rem; cursor: pointer; border: none; {{ request('start_date') == now()->startOfYear()->format('Y-m-d') && request('end_date') == today()->format('Y-m-d') ? 'background:#303030;color:white;' : 'background:white;color:#616161;border:1px solid #d4d4d4;' }}">
                This year
            </button>
            @if($hasDateFilter)
                <a href="{{ route('admin.dashboard') }}" style="padding: 0.375rem 0.75rem; font-size: 12px; font-weight: 500; border-radius: 50rem; color: #6F9CA2; text-decoration: none;">Clear filter</a>
            @endif
        </form>
        @if($hasDateFilter)
            <p style="font-size: 12px; margin-top: 0.5rem; color: #616161;">{{ $startDate->format('M d, Y') }} &ndash; {{ $endDate->format('M d, Y') }}</p>
        @endif
    </div>

    <!-- Total Sales card with chart (Shopify's main analytics card) -->
    <div class="card" style="margin-bottom: 1.25rem;">
        <div style="padding: 1.25rem;">
            <p style="font-size: 12px; font-weight: 500; margin-bottom: 0.25rem; color: #616161;">Total sales</p>
            <p style="font-size: 1.875rem; font-weight: 600; color: #303030;">@price($topRevenue)</p>
        </div>
        <div style="padding: 0 1rem 1rem 1rem;">
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>

    <!-- Metrics grid (Shopify 2x2 on mobile, 4 on desktop) -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; margin-bottom: 1.25rem; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e3e3e3;">
        <div style="background: white; padding: 1rem;">
            <p style="font-size: 12px; font-weight: 500; margin-bottom: 2px; color: #616161;">Total orders</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($topOrders) }}</p>
            @if($pendingOrders > 0)
                <p style="font-size: 11px; margin-top: 0.25rem; font-weight: 500; color: #b98900;">{{ $pendingOrders }} to fulfill</p>
            @endif
        </div>
        <div style="background: white; padding: 1rem;">
            <p style="font-size: 12px; font-weight: 500; margin-bottom: 2px; color: #616161;">Conversion rate</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ $completionRate }}%</p>
            <p style="font-size: 11px; margin-top: 0.25rem; color: #616161;">{{ number_format($completedOrders) }} completed</p>
        </div>
        <div style="background: white; padding: 1rem;">
            <p style="font-size: 12px; font-weight: 500; margin-bottom: 2px; color: #616161;">Total customers</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($totalCustomers) }}</p>
        </div>
        <div style="background: white; padding: 1rem;">
            <p style="font-size: 12px; font-weight: 500; margin-bottom: 2px; color: #616161;">Returns</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($totalReturns) }}</p>
            @if($pendingReturns > 0)
                <p style="font-size: 11px; margin-top: 0.25rem; font-weight: 500; color: #b98900;">{{ $pendingReturns }} pending</p>
            @endif
        </div>
    </div>

    <!-- Two-column: Orders table + Order Status chart -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <!-- Recent Orders -->
        <div class="card lg:col-span-2">
            <div style="padding: 0.875rem 1.25rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Recent orders</h2>
                <a href="{{ route('admin.orders.index') }}" style="font-size: 12px; font-weight: 500; color: #005bd3; text-decoration: none;">View all orders</a>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e3e3e3;">
                            <th style="padding: 0.5rem 1rem; text-align: left; font-size: 11px; font-weight: 500; color: #616161;">Order</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-size: 11px; font-weight: 500; color: #616161;">Customer</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-size: 11px; font-weight: 500; color: #616161;">Status</th>
                            <th style="padding: 0.5rem 1rem; text-align: right; font-size: 11px; font-weight: 500; color: #616161;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr style="border-bottom: 1px solid #f1f1f1;">
                                <td style="padding: 0.625rem 1rem;">
                                    <a href="{{ route('admin.orders.show', $order) }}" style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;">
                                        {{ $order->order_number }}
                                    </a>
                                    <p style="font-size: 11px; color: #616161;">{{ $order->created_at->diffForHumans() }}</p>
                                </td>
                                <td style="padding: 0.625rem 1rem; font-size: 13px; color: #303030;">
                                    {{ $order->user->full_name ?? 'Guest' }}
                                </td>
                                <td style="padding: 0.625rem 1rem;">
                                    <span class="badge {{ $order->status === 'delivered' || $order->status === 'completed' ? 'badge-success' : ($order->status === 'pending' ? 'badge-warning' : ($order->status === 'cancelled' ? 'badge-error' : 'badge-info')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td style="padding: 0.625rem 1rem; font-size: 13px; text-align: right; font-weight: 500; color: #303030;">
                                    @price($order->total)
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding: 2rem 1rem; text-align: center; font-size: 13px; color: #616161;">
                                    No orders yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Order Status -->
        <div class="card">
            <div style="padding: 0.875rem 1.25rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Order status</h2>
            </div>
            <div style="padding: 1rem; display: flex; align-items: center; justify-content: center;">
                <canvas id="orderStatusChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue + Top Products -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Monthly Revenue -->
        <div class="card lg:col-span-2">
            <div style="padding: 0.875rem 1.25rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Monthly revenue</h2>
                <span style="font-size: 11px; color: #616161;">{{ $hasDateFilter ? $startDate->format('M d') . ' – ' . $endDate->format('M d, Y') : 'Last 6 months' }}</span>
            </div>
            <div style="padding: 1rem;">
                <canvas id="monthlyRevenueChart" height="200"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="card">
            <div style="padding: 0.875rem 1.25rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Top products</h2>
            </div>
            <div>
                @forelse($topProducts as $index => $product)
                    <div style="padding: 0.625rem 1rem; display: flex; align-items: center; gap: 0.75rem; {{ !$loop->last ? 'border-bottom: 1px solid #f1f1f1;' : '' }}">
                        <div style="width: 2rem; height: 2rem; border-radius: 0.25rem; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; background: #f1f1f1;">
                            @if($product->primary_image_url)
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <svg style="width: 0.875rem; height: 0.875rem; color: #b5b5b5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            @endif
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <p style="font-size: 13px; font-weight: 500; color: #303030; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $product->name }}</p>
                            <p style="font-size: 11px; color: #616161;">{{ $product->total_sold ?? 0 }} sold</p>
                        </div>
                        <span style="font-size: 13px; font-weight: 500; flex-shrink: 0; color: #303030;">@price($product->price)</span>
                    </div>
                @empty
                    <div style="padding: 1rem; text-align: center; font-size: 13px; color: #616161;">
                        No products yet
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fontFamily = "'Inter', 'Poppins', sans-serif";
            const gridColor = '#f1f1f1';

            // Revenue Overview - Line Chart (Shopify green style)
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Revenue',
                        data: @json($chartRevenue),
                        borderColor: '#2a9d3e',
                        backgroundColor: 'rgba(42, 157, 62, 0.06)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#2a9d3e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#303030',
                            titleFont: { size: 11, family: fontFamily },
                            bodyFont: { size: 12, family: fontFamily, weight: '600' },
                            padding: { x: 12, y: 8 },
                            cornerRadius: 8,
                            callbacks: {
                                label: ctx => '₹' + ctx.parsed.y.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11, family: fontFamily }, color: '#616161', maxTicksLimit: 7 } },
                        y: {
                            grid: { color: gridColor, drawBorder: false },
                            ticks: { font: { size: 11, family: fontFamily }, color: '#616161', callback: v => '₹' + v.toLocaleString() },
                            border: { display: false }
                        }
                    }
                }
            });

            // Order Status Donut Chart
            const statusData = @json($orderStatusCounts);
            const statusLabels = Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' '));
            const statusValues = Object.values(statusData);
            const statusColors = {
                pending: '#f5a623', confirmed: '#3b82f6', processing: '#8b5cf6',
                packed: '#6366f1', shipped: '#06b6d4', out_for_delivery: '#14b8a6',
                delivered: '#2a9d3e', completed: '#059669', cancelled: '#d72c0d', returned: '#f97316'
            };
            const bgColors = Object.keys(statusData).map(s => statusColors[s] || '#bdbdbd');

            new Chart(document.getElementById('orderStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: bgColors,
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, pointStyle: 'circle', padding: 8, font: { size: 11, family: fontFamily }, color: '#303030' }
                        }
                    }
                }
            });

            // Monthly Revenue Bar Chart
            new Chart(document.getElementById('monthlyRevenueChart'), {
                type: 'bar',
                data: {
                    labels: @json($monthLabels),
                    datasets: [{
                        label: 'Revenue',
                        data: @json($monthData),
                        backgroundColor: '#2a9d3e',
                        hoverBackgroundColor: '#1f7a2f',
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#303030',
                            titleFont: { size: 11, family: fontFamily },
                            bodyFont: { size: 12, family: fontFamily, weight: '600' },
                            padding: { x: 12, y: 8 },
                            cornerRadius: 8,
                            callbacks: {
                                label: ctx => '₹' + ctx.parsed.y.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11, family: fontFamily }, color: '#616161' } },
                        y: {
                            grid: { color: gridColor, drawBorder: false },
                            ticks: { font: { size: 11, family: fontFamily }, color: '#616161', callback: v => '₹' + v.toLocaleString() },
                            border: { display: false }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-layouts.admin>
