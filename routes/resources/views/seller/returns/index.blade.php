<x-layouts.seller>
    <x-slot name="title">Returns</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Returns</h1>
            <p class="text-neutral-600">Manage return requests for your products</p>
        </div>
    </div>

    <div class="card">
        @if($returns->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50">
                            <th class="text-left px-4 py-3 font-medium text-neutral-600">Return ID</th>
                            <th class="text-left px-4 py-3 font-medium text-neutral-600">Order</th>
                            <th class="text-left px-4 py-3 font-medium text-neutral-600">Reason</th>
                            <th class="text-left px-4 py-3 font-medium text-neutral-600">Status</th>
                            <th class="text-left px-4 py-3 font-medium text-neutral-600">Date</th>
                            <th class="text-left px-4 py-3 font-medium text-neutral-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @foreach($returns as $return)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-4 py-3 font-medium">{{ $return->return_number }}</td>
                                <td class="px-4 py-3">{{ $return->order->order_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $return->reason ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $return->status === 'approved' ? 'badge-success' : ($return->status === 'rejected' ? 'badge-error' : 'badge-warning') }}">
                                        {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-neutral-600">{{ $return->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('seller.returns.show', $return) }}" class="text-primary-600 hover:text-primary-700">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $returns->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-1">No return requests</h3>
                <p class="text-neutral-600">Return requests from customers will appear here.</p>
            </div>
        @endif
    </div>
</x-layouts.seller>
