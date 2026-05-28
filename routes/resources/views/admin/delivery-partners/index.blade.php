<x-layouts.admin>
    <x-slot name="title">Delivery Partners</x-slot>

    <!-- Page Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Delivery Partners</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">Manage delivery partners and assignments</p>
        </div>
        <a href="{{ route('admin.delivery-partners.create') }}" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.4375rem 0.75rem; background: #303030; color: white; border-radius: 0.5rem; font-size: 13px; font-weight: 500; text-decoration: none;">
            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add partner
        </a>
    </div>

    <!-- Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ $stats['total'] }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Active</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ $stats['active'] }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Inactive</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ $stats['inactive'] }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">On Delivery</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ $stats['on_delivery'] }}</div>
        </div>
    </div>

    <!-- Main Card -->
    <div style="background: white; border-radius: 0.75rem; border: 1px solid #e3e3e3; overflow: hidden;">

        <!-- Tab Row -->
        <div style="display: flex; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            <a href="{{ route('admin.delivery-partners.index', request()->except('status')) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }};">
                All
            </a>
            <a href="{{ route('admin.delivery-partners.index', array_merge(request()->except('status'), ['status' => 'active'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'active' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'active' ? '#303030' : '#616161' }};">
                Active
            </a>
            <a href="{{ route('admin.delivery-partners.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'inactive' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'inactive' ? '#303030' : '#616161' }};">
                Inactive
            </a>
        </div>

        <!-- Search Row -->
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.delivery-partners.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('vehicle_type'))
                    <input type="hidden" name="vehicle_type" value="{{ request('vehicle_type') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; outline: none;">
                </div>
            </form>
            @if(request('search'))
                <a href="{{ route('admin.delivery-partners.index', request()->except('search')) }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">Clear all</a>
            @endif
        </div>

        <!-- Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Partner</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">ID</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Phone</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Vehicle</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Status</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Verification</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Joined</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $partner)
                        <tr style="border-bottom: 1px solid #e3e3e3; cursor: pointer;" onclick="window.location='{{ route('admin.delivery-partners.show', $partner) }}'" onmouseover="this.style.background='#f6f6f7'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 0.625rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 28px; height: 28px; background: #e3e3e3; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($partner->user->first_name ?? '', 0, 1) . substr($partner->user->last_name ?? '', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: #303030;">{{ $partner->user->full_name ?? 'N/A' }}</div>
                                        <div style="font-size: 11px; color: #616161;">{{ $partner->user->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="font-family: monospace; font-size: 13px; font-weight: 500; color: #303030;">{{ $partner->partner_id }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $partner->phone ?? '-' }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #e0f0ff; color: #005bd3;">{{ ucfirst($partner->vehicle_type) }}</span>
                                @if($partner->vehicle_number)
                                    <span style="font-size: 12px; color: #616161; margin-left: 0.25rem;">{{ $partner->vehicle_number }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($partner->is_active)
                                    <span style="display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #cdfee1; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #ffe0db; color: #b71c00;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @php
                                    $vStyles = match($partner->verification_status) {
                                        'verified' => 'background: #cdfee1; color: #1a7a2e;',
                                        'rejected' => 'background: #ffe0db; color: #b71c00;',
                                        default    => 'background: #fff3cd; color: #8a6d00;',
                                    };
                                @endphp
                                <span style="display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; {{ $vStyles }}">{{ ucfirst($partner->verification_status) }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $partner->created_at->format('M d, Y') }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right;" onclick="event.stopPropagation();">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                    <a href="{{ route('admin.delivery-partners.show', $partner) }}" style="color: #616161; text-decoration: none; font-size: 12px; font-weight: 500;">View</a>
                                    <a href="{{ route('admin.delivery-partners.edit', $partner) }}" style="color: #005bd3; text-decoration: none; font-size: 12px; font-weight: 500;">Edit</a>
                                    <form action="{{ route('admin.delivery-partners.destroy', $partner) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this delivery partner?')" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: #d72c0d; font-size: 12px; font-weight: 500; cursor: pointer; padding: 0;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #f6f6f7; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 24px; height: 24px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div style="font-size: 14px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No delivery partners found</div>
                                    <div style="font-size: 13px; color: #616161;">
                                        @if(request()->hasAny(['search', 'status', 'vehicle_type']))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            <a href="{{ route('admin.delivery-partners.create') }}" style="color: #005bd3; text-decoration: none; font-weight: 500;">Add a partner</a> to get started.
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($partners->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $partners->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
