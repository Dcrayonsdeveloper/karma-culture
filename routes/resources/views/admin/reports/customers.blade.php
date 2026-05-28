<x-layouts.admin>
    <x-slot name="title">Customer Report</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Customer Report</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <form action="{{ route('admin.reports.customers') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
                    <select name="period" onchange="this.form.submit()" class="form-select" style="font-size: 13px;">
                        <option value="7" @selected($period == 7)>Last 7 days</option>
                        <option value="30" @selected($period == 30)>Last 30 days</option>
                        <option value="90" @selected($period == 90)>Last 90 days</option>
                        <option value="365" @selected($period == 365)>Last year</option>
                    </select>
                </form>
                <a href="{{ route('admin.reports.export', ['type' => 'customers', 'period' => $period]) }}" class="btn btn-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
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
            <div style="font-size: 12px; color: #616161;">Total Customers</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total_customers']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">New Customers</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['new_customers']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Returning</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['returning_customers']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Avg. Lifetime Value</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">@price($stats['average_lifetime_value'])</div>
        </div>
    </div>

    <!-- Growth + Segments -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <!-- Customer Growth -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">New Customer Growth</h2>
            </div>
            <div style="padding: 1rem;">
                @foreach($growth->take(14) as $data)
                    @php
                        $maxGrowth = $growth->max('count') ?: 1;
                        $percentage = ($data->count / $maxGrowth) * 100;
                    @endphp
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                        <div style="width: 4rem; font-size: 13px; color: #616161;">{{ \Carbon\Carbon::parse($data->date)->format('M d') }}</div>
                        <div style="flex: 1; background: #f0f0f0; border-radius: 9999px; height: 1rem; overflow: hidden;">
                            <div style="background: #1a7a2e; height: 100%; border-radius: 9999px; width: {{ $percentage }}%;"></div>
                        </div>
                        <div style="width: 2.5rem; text-align: right; font-size: 13px; font-weight: 500; color: #303030;">{{ $data->count }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Customer Segments -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Customer Segments</h2>
            </div>
            <div style="padding: 1rem;">
                @php
                    $totalCustomers = $stats['new_customers'] + $stats['returning_customers'];
                    $newPercentage = $totalCustomers > 0 ? ($stats['new_customers'] / $totalCustomers) * 100 : 0;
                    $returningPercentage = $totalCustomers > 0 ? ($stats['returning_customers'] / $totalCustomers) * 100 : 0;
                @endphp
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; margin-bottom: 0.25rem;">
                        <span style="color: #303030;">New Customers</span>
                        <span style="font-weight: 500; color: #303030;">{{ number_format($newPercentage, 1) }}%</span>
                    </div>
                    <div style="background: #f0f0f0; border-radius: 9999px; height: 0.75rem; overflow: hidden;">
                        <div style="background: #1a7a2e; height: 100%; border-radius: 9999px; width: {{ $newPercentage }}%;"></div>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; margin-bottom: 0.25rem;">
                        <span style="color: #303030;">Returning Customers</span>
                        <span style="font-weight: 500; color: #303030;">{{ number_format($returningPercentage, 1) }}%</span>
                    </div>
                    <div style="background: #f0f0f0; border-radius: 9999px; height: 0.75rem; overflow: hidden;">
                        <div style="background: #005bd3; height: 100%; border-radius: 9999px; width: {{ $returningPercentage }}%;"></div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                    <div style="font-size: 12px; color: #616161; margin-bottom: 0.25rem;">Customer Retention</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: #005bd3;">
                        {{ $stats['total_customers'] > 0 ? number_format(($stats['returning_customers'] / $stats['total_customers']) * 100, 1) : 0 }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Top Customers</h2>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Rank</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Customer</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Email</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Orders</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCustomers as $index => $customer)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 50%; font-size: 12px; font-weight: 600; {{ $index < 3 ? 'background: #d4edfc; color: #0064a4;' : 'background: #f0f0f0; color: #616161;' }}">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 13px; font-weight: 500; color: #616161;">
                                            {{ strtoupper(substr($customer->name ?? 'C', 0, 1)) }}
                                        </span>
                                    </div>
                                    <span style="font-weight: 500; color: #303030;">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $customer->email }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; color: #303030;">{{ number_format($customer->order_count ?? 0) }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 600; color: #1a7a2e;">@price($customer->total_spent ?? 0)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 2rem 1rem; text-align: center; color: #616161; font-size: 13px;">No customer data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
