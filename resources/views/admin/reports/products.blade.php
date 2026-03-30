<x-layouts.admin>
    <x-slot name="title">Product Report</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Product Report</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <form action="{{ route('admin.reports.products') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
                    <select name="period" onchange="this.form.submit()" class="form-select" style="font-size: 13px;">
                        <option value="7" @selected($period == 7)>Last 7 days</option>
                        <option value="30" @selected($period == 30)>Last 30 days</option>
                        <option value="90" @selected($period == 90)>Last 90 days</option>
                        <option value="365" @selected($period == 365)>Last year</option>
                    </select>
                </form>
                <a href="{{ route('admin.reports.export', ['type' => 'products', 'period' => $period]) }}" class="btn btn-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
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
            <div style="font-size: 12px; color: #616161;">Total Products</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total_products']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Active Products</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['active_products']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Low Stock</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['low_stock']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Out of Stock</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['out_of_stock']) }}</div>
        </div>
    </div>

    <!-- Category + Inventory Alerts -->
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1rem;">
        <!-- Category Breakdown -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Products by Category</h2>
            </div>
            <div style="padding: 1rem;">
                @if($categoryBreakdown->count() > 0)
                    <canvas id="categoryChart" height="280"></canvas>
                @else
                    <div style="display: flex; align-items: center; justify-content: center; padding: 3rem 1rem; color: #616161; font-size: 13px;">
                        No category data available
                    </div>
                @endif
            </div>
        </div>

        <!-- Inventory Alerts -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Inventory Alerts</h2>
                <a href="{{ route('admin.inventory.low-stock') }}" style="font-size: 12px; color: #005bd3; font-weight: 500; text-decoration: none;">View All</a>
            </div>
            <div style="padding: 1rem;">
                @if($stats['low_stock'] > 0 || $stats['out_of_stock'] > 0)
                    <div>
                        @if($stats['out_of_stock'] > 0)
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #fef0ee; border-radius: 0.5rem; margin-bottom: 0.75rem;">
                                <div style="width: 2.5rem; height: 2.5rem; background: #fcd6cf; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 500; color: #d72c0d;">{{ $stats['out_of_stock'] }} products out of stock</div>
                                    <div style="font-size: 12px; color: #d72c0d;">These products cannot be purchased</div>
                                </div>
                            </div>
                        @endif
                        @if($stats['low_stock'] > 0)
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #fdf8e8; border-radius: 0.5rem; margin-bottom: 0.75rem;">
                                <div style="width: 2.5rem; height: 2.5rem; background: #f5e6b8; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #b98900;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 500; color: #b98900;">{{ $stats['low_stock'] }} products low on stock</div>
                                    <div style="font-size: 12px; color: #b98900;">Stock level at 10 units or below</div>
                                </div>
                            </div>
                        @endif

                        <!-- Stock overview bar -->
                        <div style="padding-top: 0.75rem; border-top: 1px solid #f0f0f0;">
                            @php
                                $inStock = $stats['total_products'] - $stats['out_of_stock'] - $stats['low_stock'];
                                $inStockPct = $stats['total_products'] > 0 ? ($inStock / $stats['total_products']) * 100 : 0;
                                $lowPct = $stats['total_products'] > 0 ? ($stats['low_stock'] / $stats['total_products']) * 100 : 0;
                                $outPct = $stats['total_products'] > 0 ? ($stats['out_of_stock'] / $stats['total_products']) * 100 : 0;
                            @endphp
                            <div style="font-size: 12px; color: #616161; margin-bottom: 0.5rem;">Inventory Distribution</div>
                            <div style="display: flex; height: 0.75rem; border-radius: 9999px; overflow: hidden;">
                                <div style="background: #1a7a2e; width: {{ $inStockPct }}%;"></div>
                                <div style="background: #b98900; width: {{ $lowPct }}%;"></div>
                                <div style="background: #d72c0d; width: {{ $outPct }}%;"></div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    <div style="width: 0.625rem; height: 0.625rem; background: #1a7a2e; border-radius: 50%;"></div>
                                    <span style="font-size: 12px; color: #616161;">In Stock ({{ $inStock }})</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    <div style="width: 0.625rem; height: 0.625rem; background: #b98900; border-radius: 50%;"></div>
                                    <span style="font-size: 12px; color: #616161;">Low ({{ $stats['low_stock'] }})</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    <div style="width: 0.625rem; height: 0.625rem; background: #d72c0d; border-radius: 50%;"></div>
                                    <span style="font-size: 12px; color: #616161;">Out ({{ $stats['out_of_stock'] }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="text-align: center; padding: 2rem 0;">
                        <div style="width: 3rem; height: 3rem; background: #eefbe9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem auto;">
                            <svg style="width: 1.5rem; height: 1.5rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p style="font-size: 13px; color: #616161;">All products are well stocked!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Product Performance Table -->
    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Product Performance</h2>
        </div>
        @if($products->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $products->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Product</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">SKU</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Stock</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Price</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Units Sold</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
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
                                        <div style="font-size: 13px; font-weight: 500; color: #303030;">{{ Str::limit($product->name, 30) }}</div>
                                        <div style="font-size: 12px; color: #616161;">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $product->sku ?? '-' }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($product->stock_quantity <= 0)
                                    <span style="display: inline-flex; padding: 0.125rem 0.5rem; font-size: 12px; font-weight: 500; background: #fef0ee; color: #d72c0d; border-radius: 9999px;">Out of stock</span>
                                @elseif($product->stock_quantity <= 10)
                                    <span style="display: inline-flex; padding: 0.125rem 0.5rem; font-size: 12px; font-weight: 500; background: #fdf8e8; color: #b98900; border-radius: 9999px;">{{ $product->stock_quantity }} left</span>
                                @else
                                    <span style="color: #616161;">{{ $product->stock_quantity }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">@price($product->price)</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 600; color: #303030;">{{ number_format($product->sold ?? 0) }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 600; color: #1a7a2e;">@price($product->revenue ?? 0)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 2rem 1rem; text-align: center; color: #616161; font-size: 13px;">No products found</td>
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

    @if($categoryBreakdown->count() > 0)
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fontFamily = "'Poppins', 'Inter', sans-serif";
                const categoryColors = ['#9c00ad', '#d946ef', '#f472b6', '#fb923c', '#facc15', '#4ade80', '#22d3ee', '#818cf8', '#a78bfa', '#94a3b8'];

                new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($categoryBreakdown->pluck('name')),
                        datasets: [{
                            data: @json($categoryBreakdown->pluck('count')),
                            backgroundColor: categoryColors.slice(0, {{ $categoryBreakdown->count() }}),
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                grid: { color: '#f5f5f5' },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#9e9e9e', stepSize: 1 }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#525252' }
                            }
                        }
                    }
                });
            });
        </script>
        @endpush
    @endif
</x-layouts.admin>
