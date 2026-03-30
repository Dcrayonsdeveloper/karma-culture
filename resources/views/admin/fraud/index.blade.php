<x-layouts.admin>
    <x-slot name="title">Fraud Review</x-slot>

    <!-- Page Header -->
    <div style="margin-bottom: 1rem;">
        <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Fraud Review</h1>
        <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">Monitor and review flagged orders for potential fraud</p>
    </div>

    <!-- Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Flagged</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['flagged'] ?? 0) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Blocked</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['blocked'] ?? 0) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Unreviewed</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['unreviewed'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Main Card -->
    <div style="background: white; border-radius: 0.75rem; border: 1px solid #e3e3e3; overflow: hidden;">

        <!-- Tab Row -->
        <div style="display: flex; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            <a href="{{ route('admin.fraud.index', request()->except('action')) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('action') ? '#303030' : 'transparent' }}; color: {{ !request('action') ? '#303030' : '#616161' }};">
                All
            </a>
            <a href="{{ route('admin.fraud.index', array_merge(request()->except('action'), ['action' => 'flagged'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('action') === 'flagged' ? '#303030' : 'transparent' }}; color: {{ request('action') === 'flagged' ? '#303030' : '#616161' }};">
                Flagged
            </a>
            <a href="{{ route('admin.fraud.index', array_merge(request()->except('action'), ['action' => 'blocked'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('action') === 'blocked' ? '#303030' : 'transparent' }}; color: {{ request('action') === 'blocked' ? '#303030' : '#616161' }};">
                Blocked
            </a>
            <a href="{{ route('admin.fraud.index', array_merge(request()->except('action'), ['action' => 'allowed'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('action') === 'allowed' ? '#303030' : 'transparent' }}; color: {{ request('action') === 'allowed' ? '#303030' : '#616161' }};">
                Allowed
            </a>
        </div>

        <!-- Search Row -->
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.fraud.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('action'))
                    <input type="hidden" name="action" value="{{ request('action') }}">
                @endif
                @if(request('reviewed'))
                    <input type="hidden" name="reviewed" value="{{ request('reviewed') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order #, customer name or email..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; outline: none;">
                </div>
            </form>
            @if(request('search'))
                <a href="{{ route('admin.fraud.index', request()->except('search')) }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">Clear all</a>
            @endif
        </div>

        <!-- Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Date</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Order #</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Customer</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: center;">Risk Score</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: left;">Fraud Type</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: center;">Action</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: center;">Reviewed</th>
                        <th style="padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px; text-align: right;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fraudLogs as $log)
                        <tr style="border-bottom: 1px solid #e3e3e3; cursor: pointer;" onclick="window.location='{{ route('admin.fraud.show', $log) }}'" onmouseover="this.style.background='#f6f6f7'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 0.625rem 1rem; color: #616161; white-space: nowrap;">
                                {{ $log->created_at->format('M d, Y') }}
                                <div style="font-size: 11px; color: #616161;">{{ $log->created_at->format('h:i A') }}</div>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($log->order)
                                    <a href="{{ route('admin.orders.show', $log->order_id) }}" style="color: #005bd3; text-decoration: none; font-weight: 500;" onclick="event.stopPropagation();">
                                        {{ $log->order->order_number }}
                                    </a>
                                @else
                                    <span style="color: #616161;">N/A</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($log->user)
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 28px; height: 28px; background: #e3e3e3; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($log->user->first_name ?? 'U', 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500; color: #303030;">{{ $log->user->full_name ?? $log->user->first_name . ' ' . $log->user->last_name }}</div>
                                            <div style="font-size: 11px; color: #616161;">{{ $log->user->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span style="color: #616161;">Guest</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                @php
                                    $score = $log->risk_score ?? 0;
                                    if ($score >= 75) {
                                        $scoreBg = '#ffe0db'; $scoreColor = '#b71c00';
                                    } elseif ($score >= 50) {
                                        $scoreBg = '#fff3cd'; $scoreColor = '#8a6d00';
                                    } elseif ($score >= 25) {
                                        $scoreBg = '#e0f0ff'; $scoreColor = '#005bd3';
                                    } else {
                                        $scoreBg = '#cdfee1'; $scoreColor = '#1a7a2e';
                                    }
                                @endphp
                                <span style="display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; background: {{ $scoreBg }}; color: {{ $scoreColor }};">
                                    {{ $score }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #303030;">
                                {{ ucfirst(str_replace('_', ' ', $log->type ?? '-')) }}
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                @php
                                    $actionStyles = match($log->action ?? '') {
                                        'flagged' => 'background: #fff3cd; color: #8a6d00;',
                                        'blocked' => 'background: #ffe0db; color: #b71c00;',
                                        'allowed' => 'background: #cdfee1; color: #1a7a2e;',
                                        default   => 'background: #f1f1f1; color: #616161;',
                                    };
                                @endphp
                                <span style="display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; {{ $actionStyles }}">
                                    {{ ucfirst($log->action ?? 'Pending') }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                @if($log->reviewed_at)
                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 12px; font-weight: 500; color: #1a7a2e;">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Yes
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 12px; font-weight: 500; color: #616161;">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        No
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <a href="{{ route('admin.fraud.show', $log) }}" style="color: #005bd3; text-decoration: none; font-size: 12px; font-weight: 500;" onclick="event.stopPropagation();">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #f6f6f7; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 24px; height: 24px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>
                                    <div style="font-size: 14px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No fraud logs found</div>
                                    <div style="font-size: 13px; color: #616161;">
                                        @if(request()->hasAny(['search', 'action', 'reviewed']))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Fraud detection logs will appear here when orders are screened.
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
        @if($fraudLogs->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $fraudLogs->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
