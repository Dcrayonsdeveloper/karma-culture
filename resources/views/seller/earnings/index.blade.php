<x-layouts.seller>
    <x-slot name="title">Earnings</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Earnings</h1>
            <p class="text-neutral-600">Track your sales and earnings</p>
        </div>
        <a href="{{ route('seller.payouts.index') }}" class="btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Request Payout
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Total Earnings</p>
                    <p class="text-2xl font-bold text-neutral-900">@price($totalEarnings)</p>
                </div>
                <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Available Balance</p>
                    <p class="text-2xl font-bold text-primary-600">@price($availableBalance)</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Pending</p>
                    <p class="text-2xl font-bold text-warning-600">@price($pendingEarnings)</p>
                </div>
                <div class="w-12 h-12 bg-warning-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Total Paid Out</p>
                    <p class="text-2xl font-bold text-neutral-900">@price($totalPaidOut)</p>
                </div>
                <div class="w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Monthly Earnings Chart -->
        <div class="lg:col-span-2 card p-6">
            <h2 class="font-semibold text-neutral-900 mb-6">Monthly Earnings</h2>
            <div class="space-y-4">
                @foreach($monthlyEarnings as $data)
                    @php
                        $maxEarnings = $monthlyEarnings->max('earnings') ?: 1;
                        $percentage = ($data['earnings'] / $maxEarnings) * 100;
                    @endphp
                    <div class="flex items-center gap-4">
                        <div class="w-20 text-sm text-neutral-600">{{ $data['month'] }}</div>
                        <div class="flex-1 bg-neutral-100 rounded-full h-4 overflow-hidden">
                            <div class="bg-primary-500 h-full rounded-full transition-all duration-500"
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="w-24 text-right font-medium">@price($data['earnings'])</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Commission Info -->
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 mb-4">Commission Structure</h2>
            <div class="bg-neutral-50 rounded-lg p-4 mb-4">
                <p class="text-3xl font-bold text-primary-600">{{ 100 - ($seller->commission_rate ?? 15) }}%</p>
                <p class="text-sm text-neutral-600">Your Earnings Rate</p>
            </div>
            <p class="text-sm text-neutral-600">
                You earn {{ 100 - ($seller->commission_rate ?? 15) }}% of each sale. The remaining {{ $seller->commission_rate ?? 15 }}%
                goes to platform fees including payment processing, hosting, and support.
            </p>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card mt-6">
        <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
            <h2 class="font-semibold text-neutral-900">Recent Sales</h2>
            <a href="{{ route('seller.orders.index') }}" class="text-sm text-primary-600 hover:text-primary-700">View All Orders</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Sale Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Your Earnings</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($recentOrders as $order)
                        @php
                            $saleTotal = $order->items->sum('total');
                            $earnings = $saleTotal * (1 - (($seller->commission_rate ?? 15) / 100));
                        @endphp
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('seller.orders.show', $order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $order->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $order->items->count() }} item(s)</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">@price($saleTotal)</td>
                            <td class="px-4 py-3 text-sm font-medium text-success-600 text-right">+@price($earnings)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-neutral-600">
                                No sales yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.seller>
