<x-layouts.app>
    <x-slot name="title">Return {{ $return->return_number }}</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                @include('account.partials.sidebar')

                <div class="flex-1">
                    {{-- Breadcrumb --}}
                    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-5">
                        <a href="{{ route('account.returns.index') }}" class="hover:text-[#6F9CA2] transition-colors">My Returns</a>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="text-neutral-900 font-medium">{{ $return->return_number }}</span>
                    </div>

                    @if(session('success'))
                        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Header Card --}}
                    <div class="bg-white rounded-xl border border-neutral-200 mb-4 overflow-hidden">
                        <div class="px-5 py-4 border-b border-neutral-100 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h1 class="text-lg font-bold text-neutral-900">{{ $return->return_number }}</h1>
                                <p class="text-sm text-neutral-600 mt-0.5">Submitted on {{ $return->created_at->format('F d, Y \a\t g:i A') }}</p>
                            </div>
                            @php
                                $statusColors = [
                                    'requested' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                    'pickup_scheduled' => 'bg-[#6F9CA2]/5 text-[#5B878D] border-[#6F9CA2]/30',
                                    'picked_up' => 'bg-[#6F9CA2]/15 text-[#4A7A80] border-[#6F9CA2]/40',
                                    'received' => 'bg-[#6F9CA2]/5 text-[#5B878D] border-[#6F9CA2]/30',
                                    'processed' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                ];
                                $color = $statusColors[$return->status] ?? 'bg-neutral-50 text-neutral-600 border-neutral-200';
                            @endphp
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full border {{ $color }}">
                                {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                            </span>
                        </div>

                        {{-- Status Timeline --}}
                        @php
                            $steps = ['requested', 'approved', 'pickup_scheduled', 'picked_up', 'received', 'processed', 'completed'];
                            if ($return->status === 'rejected') {
                                $steps = ['requested', 'rejected'];
                            }
                            $currentIndex = array_search($return->status, $steps);
                        @endphp
                        <div class="px-5 py-4 bg-neutral-50 border-b border-neutral-100">
                            <div class="flex items-center justify-between gap-1">
                                @foreach($steps as $i => $step)
                                    <div class="flex items-center gap-1 {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                                        <div class="flex flex-col items-center">
                                            @if($i <= $currentIndex)
                                                <div class="w-6 h-6 rounded-full {{ $step === 'rejected' ? 'bg-red-500' : 'bg-[#F8931D]' }} flex items-center justify-center">
                                                    @if($step === 'rejected')
                                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    @else
                                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="w-6 h-6 rounded-full border-2 border-neutral-300 bg-white"></div>
                                            @endif
                                            <span class="text-[10px] mt-1 text-neutral-600 capitalize whitespace-nowrap hidden sm:block">{{ $step === 'processed' ? 'Refund Processed' : str_replace('_', ' ', $step) }}</span>
                                        </div>
                                        @if($i < count($steps) - 1)
                                            <div class="flex-1 h-0.5 {{ $i < $currentIndex ? ($step === 'rejected' ? 'bg-red-300' : 'bg-[#6F9CA2]') : 'bg-neutral-200' }} rounded-full mx-1 mb-4 sm:mb-0"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Details Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-neutral-100">
                            <div class="px-5 py-3.5">
                                <p class="text-[10px] uppercase tracking-wider text-neutral-600 font-semibold mb-1">Order</p>
                                <a href="{{ route('account.orders.show', $return->order) }}" class="text-sm font-semibold text-[#6F9CA2] hover:text-[#5B878D]">
                                    {{ $return->order->order_number }}
                                </a>
                            </div>
                            <div class="px-5 py-3.5">
                                <p class="text-[10px] uppercase tracking-wider text-neutral-600 font-semibold mb-1">Type</p>
                                <p class="text-sm font-semibold text-neutral-900 capitalize flex items-center gap-1.5">
                                    @if($return->type === 'return')
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                    @else
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    @endif
                                    {{ $return->type }}
                                </p>
                            </div>
                            <div class="px-5 py-3.5">
                                <p class="text-[10px] uppercase tracking-wider text-neutral-600 font-semibold mb-1">Items</p>
                                <p class="text-sm font-semibold text-neutral-900">{{ $return->items->count() }} {{ Str::plural('item', $return->items->count()) }}</p>
                            </div>
                            <div class="px-5 py-3.5">
                                <p class="text-[10px] uppercase tracking-wider text-neutral-600 font-semibold mb-1">Refund</p>
                                @if($return->refund_amount)
                                    <p class="text-sm font-bold text-emerald-600">{{ format_price($return->refund_amount) }}</p>
                                @else
                                    <p class="text-sm text-neutral-600">Pending</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Reason --}}
                    @if($return->reason || $return->description)
                        <div class="bg-white rounded-xl border border-neutral-200 mb-4 overflow-hidden">
                            <div class="px-5 py-3 border-b border-neutral-100">
                                <h2 class="text-sm font-bold text-neutral-900">Reason</h2>
                            </div>
                            <div class="px-5 py-3.5">
                                <p class="text-sm font-medium text-neutral-800">{{ $return->reason }}</p>
                                @if($return->description)
                                    <p class="text-sm text-neutral-600 mt-1">{{ $return->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Return Items --}}
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-neutral-100">
                            <h2 class="text-sm font-bold text-neutral-900">Return Items</h2>
                        </div>
                        <div class="divide-y divide-neutral-100">
                            @foreach($return->items as $item)
                                @php
                                    $productName = $item->orderItem->product_name ?? $item->orderItem->product->name ?? 'Product';
                                    $variantName = $item->orderItem->variant_name ?? null;
                                    $price = $item->orderItem->price ?? 0;
                                    $itemStatusColors = [
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        'received' => 'bg-[#6F9CA2]/5 text-[#5B878D] border-[#6F9CA2]/30',
                                    ];
                                    $itemColor = $itemStatusColors[$item->status ?? 'pending'] ?? 'bg-neutral-50 text-neutral-600 border-neutral-200';
                                    $conditionLabels = [
                                        'unopened' => ['Unopened', 'bg-emerald-50 text-emerald-700'],
                                        'opened' => ['Opened', 'bg-amber-50 text-amber-700'],
                                        'damaged' => ['Damaged', 'bg-red-50 text-red-700'],
                                    ];
                                @endphp
                                <div class="px-5 py-4 flex items-start gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-neutral-900 truncate">{{ $productName }}</p>
                                                @if($variantName)
                                                    <p class="text-xs text-neutral-600 mt-0.5">{{ $variantName }}</p>
                                                @endif
                                            </div>
                                            @if($item->status)
                                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border shrink-0 {{ $itemColor }}">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-neutral-600">
                                            <span>Qty: <span class="font-medium text-neutral-700">{{ $item->quantity }}</span></span>
                                            <span>Price: <span class="font-medium text-neutral-700">{{ format_price($price) }}</span></span>
                                            @if($item->condition && isset($conditionLabels[$item->condition]))
                                                <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded {{ $conditionLabels[$item->condition][1] }}">
                                                    {{ $conditionLabels[$item->condition][0] }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($item->reason)
                                            <p class="text-xs text-neutral-600 mt-2 italic">"{{ $item->reason }}"</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pickup Partner Info --}}
                    @if($return->pickupPartner && in_array($return->status, ['pickup_scheduled', 'picked_up', 'received', 'processed', 'completed']))
                        <div class="bg-white rounded-xl border border-neutral-200 mt-4 overflow-hidden">
                            <div class="px-5 py-3 border-b border-neutral-100">
                                <h2 class="text-sm font-bold text-neutral-900">Pickup Partner</h2>
                            </div>
                            <div class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-[#6F9CA2]/10 rounded-full flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-neutral-900">{{ $return->pickupPartner->user->full_name }}</p>
                                        @if($return->pickupPartner->phone)
                                            <a href="tel:{{ $return->pickupPartner->phone }}" class="text-xs text-[#6F9CA2] hover:text-[#5B878D]">{{ $return->pickupPartner->phone }}</a>
                                        @endif
                                    </div>
                                </div>
                                @if($return->pickup_scheduled_at || $return->picked_up_at)
                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-600">
                                        @if($return->pickup_scheduled_at)
                                            <span>Scheduled: <span class="font-medium text-neutral-700">{{ $return->pickup_scheduled_at->format('M d, Y') }}</span></span>
                                        @endif
                                        @if($return->picked_up_at)
                                            <span>Picked up: <span class="font-medium text-neutral-700">{{ $return->picked_up_at->format('M d, Y') }}</span></span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Refund Completed --}}
                    @if($return->status === 'completed' && $return->refund_amount)
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl mt-4 p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-800">Refund Processed</p>
                                    <p class="text-sm text-emerald-700 mt-0.5">
                                        <span class="font-bold">{{ format_price($return->refund_amount) }}</span>
                                        has been refunded
                                        @if($return->refund_method)
                                            via {{ ucfirst($return->refund_method) }} payment method
                                        @endif
                                        @if($return->completed_at)
                                            on {{ $return->completed_at->format('M d, Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
