<x-layouts.admin>
    <x-slot name="title">Seller Report</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Seller Report</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <form action="{{ route('admin.reports.sellers') }}" method="GET">
                    <select name="period" onchange="this.form.submit()" class="form-select" style="font-size: 13px;">
                        <option value="7" @selected($period == 7)>Last 7 days</option>
                        <option value="30" @selected($period == 30)>Last 30 days</option>
                        <option value="90" @selected($period == 90)>Last 90 days</option>
                        <option value="365" @selected($period == 365)>Last year</option>
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total Sellers</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total_sellers']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Active Sellers</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['active_sellers']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Pending Approval</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['pending_sellers']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">New This Period</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['new_sellers']) }}</div>
        </div>
    </div>

    <!-- Top Sellers -->
    <div class="card" style="margin-bottom: 1rem;">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Top Performing Sellers</h2>
        </div>
        <div>
            @forelse($topSellers as $index => $seller)
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 0.75rem;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 50%; font-size: 12px; font-weight: 600; {{ $index < 3 ? 'background: #d4edfc; color: #0064a4;' : 'background: #f0f0f0; color: #616161;' }}">
                        {{ $index + 1 }}
                    </span>
                    <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span style="font-size: 1rem; font-weight: 500; color: #616161;">
                            {{ strtoupper(substr($seller->store_name, 0, 1)) }}
                        </span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 13px; font-weight: 500; color: #303030;">{{ $seller->store_name }}</div>
                        <div style="font-size: 12px; color: #616161;">{{ $seller->products_count }} products</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 13px; font-weight: 600; color: #1a7a2e;">@price($seller->total_sales ?? 0)</div>
                        <div style="font-size: 12px; color: #616161;">Total Sales</div>
                    </div>
                    <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary" style="font-size: 13px;">View</a>
                </div>
            @empty
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; color: #616161;">
                    <p style="font-size: 13px;">No seller data available</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- All Active Sellers -->
    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Active Sellers</h2>
            <a href="{{ route('admin.sellers.index') }}" style="font-size: 12px; color: #005bd3; font-weight: 500; text-decoration: none;">View All Sellers</a>
        </div>
        @if($sellers->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $sellers->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Seller</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Store</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Products</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Commission</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sellers as $seller)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 13px; font-weight: 500; color: #616161;">
                                            {{ strtoupper(substr($seller->user->name ?? 'S', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 500; color: #303030;">{{ $seller->user->name ?? 'N/A' }}</div>
                                        <div style="font-size: 12px; color: #616161;">{{ $seller->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #303030;">{{ $seller->store_name }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $seller->products_count }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $seller->commission_rate ?? 15 }}%</td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="display: inline-flex; padding: 0.125rem 0.5rem; font-size: 12px; font-weight: 500; background: #eefbe9; color: #1a7a2e; border-radius: 9999px;">
                                    Active
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <a href="{{ route('admin.sellers.show', $seller) }}" style="color: #005bd3; text-decoration: none; font-size: 13px; font-weight: 500;">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 2rem 1rem; text-align: center; color: #616161; font-size: 13px;">No active sellers</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sellers->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $sellers->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
