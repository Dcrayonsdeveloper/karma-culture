<x-layouts.admin>
    <x-slot name="title">Sellers</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Sellers</h1>
            <a href="{{ route('admin.sellers.pending') }}" class="btn btn-primary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
                Pending Approvals
                @if($stats['pending'] > 0)
                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 20px; padding: 0 0.375rem; font-size: 11px; font-weight: 600; background: white; color: #303030; border-radius: 9999px;">{{ $stats['pending'] }}</span>
                @endif
            </a>
        </div>
    </x-slot>

    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total Sellers</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Active</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['approved']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Pending</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['pending']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Suspended</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['suspended']) }}</div>
        </div>
    </div>

    {{-- Single Card --}}
    <div class="card" style="overflow: hidden;">
        {{-- Tab Filters --}}
        <div style="display: flex; align-items: center; gap: 0; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            <a href="{{ route('admin.sellers.index', request()->only('search')) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }};">
                All <span style="color: #616161; font-size: 12px;">({{ $stats['total'] }})</span>
            </a>
            <a href="{{ route('admin.sellers.index', array_merge(request()->only('search'), ['status' => 'approved'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'approved' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'approved' ? '#303030' : '#616161' }};">
                Approved <span style="color: #616161; font-size: 12px;">({{ $stats['approved'] }})</span>
            </a>
            <a href="{{ route('admin.sellers.index', array_merge(request()->only('search'), ['status' => 'pending'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'pending' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'pending' ? '#303030' : '#616161' }};">
                Pending <span style="color: #616161; font-size: 12px;">({{ $stats['pending'] }})</span>
            </a>
            <a href="{{ route('admin.sellers.index', array_merge(request()->only('search'), ['status' => 'suspended'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'suspended' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'suspended' ? '#303030' : '#616161' }};">
                Suspended <span style="color: #616161; font-size: 12px;">({{ $stats['suspended'] }})</span>
            </a>
        </div>

        {{-- Search Bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.sellers.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search store name, business name or email..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.sellers.index', request()->only('status')) }}" style="font-size: 13px; color: #616161; text-decoration: none; padding: 0.375rem 0.5rem;">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Store</th>
                        <th>Products</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sellers as $seller)
                        <tr onclick="window.location='{{ route('admin.sellers.show', $seller) }}'"
                            style="cursor: pointer;">
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 32px; height: 32px; background: #e0f0ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 13px; font-weight: 600; color: #005bd3;">
                                            {{ strtoupper(substr($seller->user->name ?? 'S', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: #303030;">{{ $seller->user->name ?? 'N/A' }}</div>
                                        <div style="font-size: 12px; color: #616161;">{{ $seller->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500; color: #303030;">{{ $seller->store_name }}</div>
                                @if($seller->business_name)
                                    <div style="font-size: 12px; color: #616161;">{{ $seller->business_name }}</div>
                                @endif
                            </td>
                            <td>{{ $seller->products_count ?? $seller->products->count() }}</td>
                            <td>{{ $seller->commission_rate ?? 15 }}%</td>
                            <td>
                                @switch($seller->status)
                                    @case('approved')
                                        <span class="badge badge-success">Approved</span>
                                        @break
                                    @case('pending')
                                        <span class="badge badge-warning">Pending</span>
                                        @break
                                    @case('suspended')
                                        <span class="badge badge-error">Suspended</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge badge-neutral">Rejected</span>
                                        @break
                                @endswitch
                            </td>
                            <td style="color: #616161;">{{ $seller->created_at->format('M d, Y') }}</td>
                            <td style="text-align: right;" onclick="event.stopPropagation();">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                    <a href="{{ route('admin.sellers.show', $seller) }}" class="btn-icon" title="View"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; color: #616161; text-decoration: none;">
                                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.sellers.products', $seller) }}" class="btn-icon" title="Products"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; color: #616161; text-decoration: none;">
                                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center; color: #616161;">
                                <p style="font-size: 13px;">No sellers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sellers->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $sellers->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
