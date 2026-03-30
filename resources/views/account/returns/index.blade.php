<x-layouts.app>
    <x-slot name="title">My Returns</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <x-breadcrumb :items="[['label' => 'Account', 'url' => route('account.dashboard')], ['label' => 'Returns']]" />
            <div class="flex flex-col lg:flex-row gap-8 mt-4">
                @include('account.partials.sidebar')

                <div class="flex-1">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-xl font-bold text-neutral-900">My Returns</h1>
                            <p class="text-sm text-neutral-600 mt-0.5">{{ $returns->total() }} {{ Str::plural('request', $returns->total()) }}</p>
                        </div>
                    </div>

                    @forelse($returns as $return)
                        <div class="bg-white rounded-xl border border-neutral-200 mb-3 overflow-hidden hover:shadow-sm transition-shadow">
                            {{-- Header --}}
                            <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('account.returns.show', $return) }}" class="text-sm font-bold text-neutral-900 hover:text-[#6F9CA2] transition-colors">
                                        {{ $return->return_number }}
                                    </a>
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
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border {{ $color }}">
                                        {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-neutral-600">
                                    <span class="inline-flex items-center gap-1 capitalize">
                                        @if($return->type === 'return')
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        @endif
                                        {{ $return->type }}
                                    </span>
                                    <span>&middot;</span>
                                    <span>{{ $return->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>

                            {{-- Items --}}
                            <div class="px-4 py-3">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs text-neutral-600">Order</span>
                                    <a href="{{ route('account.orders.show', $return->order) }}" class="text-xs font-medium text-[#6F9CA2] hover:text-[#5B878D]">
                                        {{ $return->order->order_number }}
                                    </a>
                                </div>
                                <div class="space-y-2">
                                    @foreach($return->items as $item)
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-neutral-300 shrink-0"></span>
                                                <span class="text-neutral-700">{{ Str::limit($item->orderItem->product_name ?? $item->orderItem->product->name ?? 'Product', 45) }}</span>
                                            </div>
                                            <span class="text-neutral-600 text-xs shrink-0 ml-2">Qty: {{ $item->quantity }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="px-4 py-2.5 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between">
                                @if($return->refund_amount)
                                    <span class="text-xs text-neutral-600">Refund: <span class="font-semibold text-emerald-600">{{ format_price($return->refund_amount) }}</span></span>
                                @else
                                    <span></span>
                                @endif
                                <a href="{{ route('account.returns.show', $return) }}" class="text-xs font-semibold text-[#6F9CA2] hover:text-[#5B878D] inline-flex items-center gap-1">
                                    View Details
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl border border-neutral-200 p-12 text-center">
                            <div class="w-16 h-16 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-neutral-900 mb-1">No returns yet</h3>
                            <p class="text-sm text-neutral-600 mb-5">You haven't submitted any return requests.</p>
                            <a href="{{ route('account.orders.index') }}" class="inline-flex items-center gap-2 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                                View Orders
                            </a>
                        </div>
                    @endforelse

                    @if($returns->hasPages())
                        <div class="mt-6">
                            {{ $returns->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
