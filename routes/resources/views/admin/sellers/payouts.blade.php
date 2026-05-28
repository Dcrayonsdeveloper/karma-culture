<x-layouts.admin>
    <x-slot name="title">{{ $seller->store_name }} - Payouts</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.sellers.show', $seller) }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ $seller->store_name }}
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Payouts</h1>
    <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 1rem 0;">{{ $seller->store_name }}</p>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Available Balance</p>
            <p style="font-size: 1.25rem; font-weight: 700; color: #1a7a2e; margin: 0;">@price($seller->available_balance ?? 0)</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Pending</p>
            <p style="font-size: 1.25rem; font-weight: 700; color: #b98900; margin: 0;">@price($stats['pending'])</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Processing</p>
            <p style="font-size: 1.25rem; font-weight: 700; color: #005bd3; margin: 0;">@price($stats['processing'])</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Completed</p>
            <p style="font-size: 1.25rem; font-weight: 700; color: #303030; margin: 0;">@price($stats['completed'])</p>
        </div>
    </div>

    <div class="card" style="overflow: hidden;">
        @if($payouts->total() > 0)
            <div style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $payouts->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">ID</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Amount</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Method</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Requested</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161;">Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; font-family: monospace; color: #616161;">#{{ $payout->id }}</td>
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">@price($payout->amount)</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ ucfirst(str_replace('_', ' ', $payout->payout_method)) }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                @switch($payout->status)
                                    @case('pending')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Pending</span>
                                        @break
                                    @case('processing')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">Processing</span>
                                        @break
                                    @case('completed')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Completed</span>
                                        @break
                                    @case('failed')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Failed</span>
                                        @break
                                @endswitch
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $payout->created_at->format('M d, Y') }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $payout->completed_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center; color: #616161;">
                                No payouts found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
