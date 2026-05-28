<x-layouts.admin>
    <x-slot name="title">Customers</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Customers</h1>
        </div>
    </x-slot>

    {{-- Stats --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total customers</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Active</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['active']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">New this month</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['new_this_month']) }}</p>
        </div>
    </div>

    {{-- Customers card --}}
    <div class="card">
        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.customers.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search customers"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
                @if(request()->hasAny(['search', 'status', 'from', 'to']))
                    <a href="{{ route('admin.customers.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 1rem;">Customer name</th>
                        <th style="text-align: left;">Email</th>
                        <th style="text-align: left;">Location</th>
                        <th style="text-align: right;">Orders</th>
                        <th style="text-align: right; padding-right: 1rem;">Amount spent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.customers.show', $customer) }}'">
                            <td style="padding-left: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e3e3e3; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($customer->first_name, 0, 1)) }}</span>
                                    </div>
                                    <span style="font-size: 13px; font-weight: 500; color: #005bd3;">{{ $customer->full_name }}</span>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $customer->email }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $customer->phone ?? '—' }}</span>
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 13px; color: #303030;">{{ $customer->orders_count }}</span>
                            </td>
                            <td style="text-align: right; padding-right: 1rem;">
                                <span style="font-size: 13px; color: #303030;">@price($customer->orders_sum_total ?? 0)</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No customers yet</h3>
                                    <p style="font-size: 13px; color: #616161;">Customers will appear here once they register.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
