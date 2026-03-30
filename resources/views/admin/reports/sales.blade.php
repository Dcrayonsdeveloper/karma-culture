<x-layouts.admin>
    <x-slot name="title">Sales Report</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Sales Report</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <form action="{{ route('admin.reports.sales') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
                    <select name="period" onchange="this.form.submit()" class="form-select" style="font-size: 13px;">
                        <option value="7" @selected($period == 7)>Last 7 days</option>
                        <option value="30" @selected($period == 30)>Last 30 days</option>
                        <option value="90" @selected($period == 90)>Last 90 days</option>
                        <option value="365" @selected($period == 365)>Last year</option>
                    </select>
                </form>
                <a href="{{ route('admin.reports.export', ['type' => 'sales', 'period' => $period]) }}" class="btn btn-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total Revenue</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">@price($stats['total_revenue'])</div>
            @if($stats['revenue_change'] != 0)
                <div style="font-size: 12px; color: {{ $stats['revenue_change'] > 0 ? '#1a7a2e' : '#d72c0d' }};">
                    {{ $stats['revenue_change'] > 0 ? '+' : '' }}{{ number_format($stats['revenue_change'], 1) }}% vs prev
                </div>
            @endif
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total Orders</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total_orders']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Average Order</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">@price($stats['average_order'])</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Items Sold</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['items_sold']) }}</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <!-- Sales Chart -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Daily Sales</h2>
                <span style="font-size: 12px; color: #616161;">Last {{ $period }} days</span>
            </div>
            <div style="padding: 1rem;">
                @if($salesData->count() > 0)
                    <canvas id="salesChart" height="260"></canvas>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; color: #616161;">
                        <svg style="width: 3rem; height: 3rem; margin-bottom: 0.75rem; color: #babfc3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p style="font-size: 13px;">No sales data for this period</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sales by Category -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Sales by Category</h2>
            </div>
            <div style="padding: 1rem;">
                @if($salesByCategory->count() > 0)
                    <canvas id="categoryChart" height="260"></canvas>
                @else
                    <div style="display: flex; align-items: center; justify-content: center; padding: 3rem 1rem; color: #616161; font-size: 13px;">
                        No category data available
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Top Selling Products</h2>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Rank</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Product</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Price</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Units Sold</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $index => $product)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 50%; font-size: 12px; font-weight: 600; {{ $index < 3 ? 'background: #d4edfc; color: #0064a4;' : 'background: #f0f0f0; color: #616161;' }}">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2.5rem; height: 2.5rem; background: #f6f6f7; border-radius: 0.5rem; overflow: hidden; flex-shrink: 0;">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 500; color: #303030;">{{ $product->name }}</div>
                                        <div style="font-size: 12px; color: #616161;">{{ $product->sku ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">@price($product->price)</td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <span style="font-weight: 600; color: #303030;">{{ number_format($product->sold ?? 0) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 2rem 1rem; text-align: center; color: #616161; font-size: 13px;">No sales data for this period</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($salesData->count() > 0 || $salesByCategory->count() > 0)
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fontFamily = "'Poppins', 'Inter', sans-serif";

                @if($salesData->count() > 0)
                // Sales Chart - Bar + Line combo
                new Chart(document.getElementById('salesChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($salesData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))),
                        datasets: [{
                            label: 'Revenue',
                            data: @json($salesData->pluck('revenue')->map(fn($r) => round($r, 2))),
                            backgroundColor: 'rgba(156, 0, 173, 0.15)',
                            hoverBackgroundColor: 'rgba(156, 0, 173, 0.3)',
                            borderColor: '#9c00ad',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                            yAxisID: 'y'
                        }, {
                            label: 'Orders',
                            data: @json($salesData->pluck('orders')),
                            type: 'line',
                            borderColor: '#06b6d4',
                            backgroundColor: 'rgba(6, 182, 212, 0.05)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#06b6d4',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, family: fontFamily } }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 10, family: fontFamily }, color: '#9e9e9e', maxRotation: 45 } },
                            y: {
                                position: 'left',
                                grid: { color: '#f5f5f5' },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#9e9e9e', callback: v => '₹' + v.toLocaleString() }
                            },
                            y1: {
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#9e9e9e', stepSize: 1 }
                            }
                        }
                    }
                });
                @endif

                @if($salesByCategory->count() > 0)
                // Category Chart - Doughnut
                const categoryColors = ['#9c00ad', '#d946ef', '#f472b6', '#fb923c', '#facc15', '#4ade80', '#22d3ee', '#818cf8', '#a78bfa', '#94a3b8'];
                new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: @json($salesByCategory->pluck('name')),
                        datasets: [{
                            data: @json($salesByCategory->pluck('revenue')->map(fn($r) => round($r, 2))),
                            backgroundColor: categoryColors.slice(0, {{ $salesByCategory->count() }}),
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, pointStyle: 'circle', padding: 10, font: { size: 11, family: fontFamily } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.label + ': ₹' + ctx.parsed.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                @endif
            });
        </script>
        @endpush
    @endif
</x-layouts.admin>
