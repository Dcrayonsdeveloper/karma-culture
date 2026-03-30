<x-layouts.seller>
    <x-slot name="title">Payouts</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Payouts</h1>
            <p class="text-neutral-600">Manage your payout requests</p>
        </div>
        @if($availableBalance >= 10)
            <a href="{{ route('seller.payouts.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Request Payout
            </a>
        @endif
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Available Balance</p>
            <p class="text-2xl font-bold text-primary-600">@price($availableBalance)</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Pending Payouts</p>
            <p class="text-2xl font-bold text-warning-600">@price($stats['pending'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Processing</p>
            <p class="text-2xl font-bold text-info-600">@price($stats['processing'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Total Paid</p>
            <p class="text-2xl font-bold text-success-600">@price($stats['completed'])</p>
        </div>
    </div>

    <!-- Payout Settings -->
    <div class="card p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-neutral-900">Payout Settings</h2>
                <p class="text-sm text-neutral-600">
                    @if($seller->payout_method)
                        {{ ucfirst(str_replace('_', ' ', $seller->payout_method)) }}
                        @if($seller->payout_email)
                            - {{ $seller->payout_email }}
                        @endif
                    @else
                        No payout method configured
                    @endif
                </p>
            </div>
            <a href="{{ route('seller.settings.index') }}" class="btn-outline">Update Settings</a>
        </div>
    </div>

    <!-- Payouts Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Payout ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Method</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($payouts as $payout)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">
                                #{{ $payout->id }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $payout->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ ucfirst(str_replace('_', ' ', $payout->payout_method)) }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                @price($payout->amount)
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $payout->status === 'completed' ? 'badge-success' : ($payout->status === 'pending' ? 'badge-warning' : ($payout->status === 'failed' ? 'badge-error' : 'badge-info')) }}">
                                    {{ ucfirst($payout->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('seller.payouts.show', $payout) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-2">No payouts yet</h3>
                                <p class="text-neutral-600 mb-4">Request your first payout when your balance reaches {{ currency_symbol() }}10.</p>
                                @if($availableBalance >= 10)
                                    <a href="{{ route('seller.payouts.create') }}" class="btn-primary">Request Payout</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>
</x-layouts.seller>
