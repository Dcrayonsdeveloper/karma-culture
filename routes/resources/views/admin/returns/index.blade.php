<x-layouts.admin>
    <x-slot name="title">Returns</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Returns</h1>
        </div>
    </x-slot>

    {{-- Stats row --}}
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Requested</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['requested']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Approved</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['approved']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Received</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #7c3aed;">{{ number_format($stats['received']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Completed</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['completed']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Rejected</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['rejected']) }}</p>
        </div>
    </div>

    {{-- Returns card --}}
    <div class="card">
        {{-- Tab filters --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center;">
            <a href="{{ route('admin.returns.index', request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
            @foreach(['requested', 'approved', 'received', 'completed', 'rejected'] as $st)
                <a href="{{ route('admin.returns.index', ['status' => $st] + request()->except('status', 'page')) }}"
                   style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === $st ? '#303030' : 'transparent' }}; color: {{ request('status') === $st ? '#303030' : '#616161' }}; margin-bottom: -1px;">{{ ucfirst($st) }}</a>
            @endforeach
        </div>

        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.returns.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999;" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search returns"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.returns.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 1rem;">Return</th>
                        <th style="text-align: left;">Customer</th>
                        <th style="text-align: center;">Type</th>
                        <th style="text-align: left;">Reason</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Refund</th>
                        <th style="text-align: right; padding-right: 1rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $return)
                        <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.returns.show', $return) }}'">
                            <td style="padding-left: 1rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #005bd3;">{{ $return->return_number }}</span>
                                <p style="font-size: 12px; color: #616161; margin-top: 2px;">
                                    Order: <a href="{{ route('admin.orders.show', $return->order_id) }}" style="color: #616161; text-decoration: none;" onmouseover="this.style.color='#005bd3'" onmouseout="this.style.color='#616161'" onclick="event.stopPropagation()">{{ $return->order->order_number ?? 'N/A' }}</a>
                                </p>
                                <p style="font-size: 12px; color: #616161; margin-top: 1px;">{{ $return->created_at->format('M d, Y h:i A') }}</p>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.625rem;">
                                    <div style="width: 2rem; height: 2rem; background: #f1f1f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($return->order->user->first_name ?? 'G', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p style="font-size: 13px; font-weight: 500; color: #303030;">{{ $return->order->user->full_name ?? 'N/A' }}</p>
                                        <p style="font-size: 12px; color: #616161;">{{ $return->order->user->email ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $typeBadge = match($return->type ?? 'return') {
                                        'return' => 'badge-info',
                                        'refund' => 'badge-warning',
                                        'exchange' => 'badge-neutral',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $typeBadge }}">{{ ucfirst($return->type ?? 'Return') }}</span>
                            </td>
                            <td>
                                <p style="font-size: 13px; color: #616161; max-width: 12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $return->reason ?? '-' }}</p>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $statusBadge = match($return->status) {
                                        'requested' => 'badge-warning',
                                        'approved', 'pickup_scheduled', 'picked_up' => 'badge-info',
                                        'received', 'processed' => 'badge-info',
                                        'rejected' => 'badge-error',
                                        'completed' => 'badge-success',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">
                                    {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                @if($return->refund_amount)
                                    <span style="font-size: 13px; font-weight: 600; color: #1a7a2e;">@price($return->refund_amount)</span>
                                @else
                                    <span style="font-size: 13px; color: #616161;">-</span>
                                @endif
                            </td>
                            <td style="text-align: right; padding-right: 1rem;">
                                <a href="{{ route('admin.returns.show', $return) }}" style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;" onclick="event.stopPropagation()">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg width="20" height="20" style="color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No returns found</h3>
                                    <p style="font-size: 13px; color: #616161;">
                                        @if(request()->hasAny(['search', 'status']))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Return requests will appear here when customers submit them.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
