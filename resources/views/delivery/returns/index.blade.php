<x-layouts.delivery>
    <x-slot name="title">Return Pickups</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Return Pickups</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage return pickup assignments</p>
            </div>
        </div>
    </x-slot>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-warning-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Active Pickups</p>
                <p class="text-xl sm:text-2xl font-bold text-warning-600">{{ $stats['active'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-info-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Picked Up</p>
                <p class="text-xl sm:text-2xl font-bold text-info-600">{{ $stats['picked_up'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Completed</p>
                <p class="text-xl sm:text-2xl font-bold text-success-600">{{ $stats['completed'] }}</p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 mb-4 bg-white rounded-lg border border-neutral-100 p-1 w-fit">
        <a href="{{ route('delivery.returns.index', ['tab' => 'active']) }}"
           class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $tab === 'active' ? 'bg-primary-600 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
            Active ({{ $stats['active'] }})
        </a>
        <a href="{{ route('delivery.returns.index', ['tab' => 'completed']) }}"
           class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $tab === 'completed' ? 'bg-primary-600 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
            Completed ({{ $stats['completed'] }})
        </a>
        <a href="{{ route('delivery.returns.index', ['tab' => 'all']) }}"
           class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $tab === 'all' ? 'bg-primary-600 text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
            All
        </a>
    </div>

    {{-- Returns Table --}}
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-neutral-900">{{ ucfirst($tab) }} Returns</h2>
        </div>

        @if($returns->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-100 text-left">
                            <th class="px-4 py-3 font-medium text-neutral-600">Return</th>
                            <th class="px-4 py-3 font-medium text-neutral-600">Customer</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 hidden md:table-cell">Pickup Address</th>
                            <th class="px-4 py-3 font-medium text-neutral-600">Status</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 hidden sm:table-cell">Items</th>
                            <th class="px-4 py-3 font-medium text-neutral-600 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-50">
                        @foreach($returns as $return)
                            @php
                                $statusColors = [
                                    'approved' => 'bg-info-50 text-info-700',
                                    'pickup_scheduled' => 'bg-warning-50 text-warning-700',
                                    'picked_up' => 'bg-[#6F9CA2]/10 text-[#5B878D]',
                                    'received' => 'bg-success-50 text-success-700',
                                    'processed' => 'bg-success-50 text-success-700',
                                    'completed' => 'bg-success-50 text-success-700',
                                ];
                                $address = $return->order->shipping_address_snapshot;
                            @endphp
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3">
                                    <a href="{{ route('delivery.returns.show', $return) }}" class="font-semibold text-primary-600 hover:text-primary-700">
                                        {{ $return->return_number }}
                                    </a>
                                    <p class="text-xs text-neutral-600 mt-0.5">Order: {{ $return->order->order_number }}</p>
                                    <p class="text-xs text-neutral-600">{{ $return->created_at->format('M d, h:i A') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-neutral-900">{{ $return->order->user->full_name ?? 'N/A' }}</p>
                                    @if($address && !empty($address['phone']))
                                        <a href="tel:{{ $address['phone'] }}" class="text-xs text-primary-600 hover:text-primary-700">{{ $address['phone'] }}</a>
                                    @endif
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    @if($address)
                                        <p class="text-neutral-600 truncate max-w-50">{{ $address['address_line_1'] ?? '' }}{{ isset($address['city']) ? ', ' . $address['city'] : '' }}</p>
                                        <p class="text-xs text-neutral-600">{{ $address['state'] ?? '' }} {{ $address['postal_code'] ?? '' }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$return->status] ?? 'bg-neutral-50 text-neutral-700' }}">
                                        {{ $return->status === 'processed' ? 'Refund Processed' : ucwords(str_replace('_', ' ', $return->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell text-neutral-600">
                                    {{ $return->items->count() }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('delivery.returns.show', $return) }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-md transition-colors" title="View Details">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        @if($return->status === 'approved')
                                            <form action="{{ route('delivery.returns.update-status', $return) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="pickup_scheduled">
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    Schedule
                                                </button>
                                            </form>
                                        @elseif($return->status === 'pickup_scheduled')
                                            <form action="{{ route('delivery.returns.update-status', $return) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="picked_up">
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-[#6F9CA2] hover:bg-[#5B878D] rounded-md transition-colors">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                                    Picked Up
                                                </button>
                                            </form>
                                        @elseif($return->status === 'picked_up')
                                            <form action="{{ route('delivery.returns.update-status', $return) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="received">
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-success-600 hover:bg-success-700 rounded-md transition-colors">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Delivered
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                <p class="text-neutral-600 text-sm">No return pickups found in this tab.</p>
            </div>
        @endif
    </div>
</x-layouts.delivery>
