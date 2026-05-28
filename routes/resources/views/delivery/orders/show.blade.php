<x-layouts.delivery>
    <x-slot name="title">Order #{{ $order->order_number }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('delivery.dashboard') }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">Order #{{ $order->order_number }}</h1>
                    <p class="text-sm text-neutral-600 mt-1">Placed {{ $order->created_at->format('M d, Y \a\t h:i A') }}</p>
                </div>
            </div>
            @php
                $headerStatusColors = [
                    'shipped' => 'bg-info-50 text-info-700 border border-info-200',
                    'out_for_delivery' => 'bg-warning-50 text-warning-700 border border-warning-200',
                    'delivered' => 'bg-success-50 text-success-700 border border-success-200',
                ];
            @endphp
            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ $headerStatusColors[$order->status] ?? 'bg-neutral-50 text-neutral-700 border border-neutral-200' }}">
                {{ ucwords(str_replace('_', ' ', $order->status)) }}
            </span>
        </div>
    </x-slot>

    {{-- Flipkart-style Order Status Stepper --}}
    @php
        $steps = [
            'packed' => ['label' => 'Packed', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            'shipped' => ['label' => 'Shipped', 'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
            'out_for_delivery' => ['label' => 'Out for Delivery', 'icon' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'],
            'delivered' => ['label' => 'Delivered', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ];
        $statusOrder = array_keys($steps);
        $currentIndex = array_search($order->status, $statusOrder);
        if ($currentIndex === false) $currentIndex = -1;
    @endphp
    <div class="card p-6 mb-6">
        <div class="flex items-center justify-between">
            @foreach($steps as $stepKey => $step)
                @php
                    $stepIndex = array_search($stepKey, $statusOrder);
                    $isCompleted = $stepIndex <= $currentIndex;
                    $isCurrent = $stepIndex === $currentIndex;
                @endphp
                <div class="flex flex-col items-center relative {{ !$loop->last ? 'flex-1' : '' }}">
                    {{-- Connector line --}}
                    @unless($loop->first)
                        <div class="absolute top-5 right-1/2 w-full h-0.5 -translate-y-1/2 {{ $isCompleted ? 'bg-primary-500' : 'bg-neutral-200' }}"></div>
                    @endunless

                    {{-- Step circle --}}
                    <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center {{ $isCompleted ? 'bg-primary-500 text-white' : 'bg-neutral-100 text-neutral-600 border-2 border-neutral-200' }} {{ $isCurrent ? 'ring-4 ring-primary-100' : '' }}">
                        @if($isCompleted && !$isCurrent)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/></svg>
                        @endif
                    </div>

                    {{-- Label --}}
                    <p class="mt-2 text-xs font-medium text-center {{ $isCompleted ? 'text-primary-600' : 'text-neutral-600' }}">{{ $step['label'] }}</p>

                    {{-- Date if completed --}}
                    @if($isCompleted)
                        @php
                            $historyEntry = $order->statusHistory->firstWhere('status', $stepKey);
                        @endphp
                        @if($historyEntry)
                            <p class="text-[10px] text-neutral-600 text-center mt-0.5">{{ $historyEntry->created_at->format('M d, h:i A') }}</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Status Update Action --}}
    @if($nextStatus)
        <div class="card p-5 mb-6 border-l-4 {{ $nextStatus === 'out_for_delivery' ? 'border-l-primary-500' : 'border-l-success-500' }}">
            <form action="{{ route('delivery.orders.update-status', $order) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="{{ $nextStatus }}">

                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-neutral-900 mb-1">
                            @if($nextStatus === 'out_for_delivery')
                                Ready to pick up this order?
                            @else
                                Ready to mark as delivered?
                            @endif
                        </h3>
                        <p class="text-sm text-neutral-600 mb-3">
                            @if($nextStatus === 'out_for_delivery')
                                Confirm that you have picked up this order and are heading to the delivery address.
                            @else
                                Confirm that the order has been successfully delivered to the customer.
                            @endif
                        </p>
                        <div>
                            <label for="comment" class="block text-sm font-medium text-neutral-700 mb-1">Delivery Note (optional)</label>
                            <textarea name="comment" id="comment" rows="2" class="form-input w-full" placeholder="Add a note about this delivery..."></textarea>
                        </div>
                    </div>
                    <div class="shrink-0">
                        @if($nextStatus === 'out_for_delivery')
                            <button type="submit" class="btn btn-primary w-full sm:w-auto px-6 py-2.5">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                Mark Out for Delivery
                            </button>
                        @else
                            <button type="submit" class="inline-flex items-center justify-center w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-white bg-success-600 hover:bg-success-700 rounded-md transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Mark as Delivered
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Order Items --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Order Items ({{ $order->items->count() }})</h2>
                </div>
                <div class="divide-y divide-neutral-50">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-4 p-4">
                            @if($item->product && $item->product->primary_image_url)
                                <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product_name }}" class="w-14 h-14 rounded-lg object-cover border border-neutral-200">
                            @else
                                <div class="w-14 h-14 bg-neutral-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-neutral-900 truncate">{{ $item->product_name }}</p>
                                @if($item->variant_name)
                                    <p class="text-xs text-neutral-600">{{ $item->variant_name }}</p>
                                @endif
                                <p class="text-xs text-neutral-600">Qty: {{ $item->quantity }}</p>
                            </div>
                            <p class="text-sm font-medium text-neutral-900">@price($item->total)</p>
                        </div>
                    @endforeach
                </div>
                <div class="px-4 py-3 border-t border-neutral-100 bg-neutral-50">
                    <div class="flex justify-between text-sm font-semibold text-neutral-900">
                        <span>Total</span>
                        <span>@price($order->total)</span>
                    </div>
                </div>
            </div>

            {{-- Order Timeline --}}
            @if($order->statusHistory->count())
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Order Timeline</h2>
                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            @foreach($order->statusHistory as $history)
                                <div class="flex gap-3">
                                    <div class="flex flex-col items-center">
                                        <div class="w-2.5 h-2.5 rounded-full bg-primary-500 mt-1.5"></div>
                                        @unless($loop->last)
                                            <div class="w-px h-full bg-neutral-200 mt-1"></div>
                                        @endunless
                                    </div>
                                    <div class="pb-4">
                                        <p class="text-sm font-medium text-neutral-900">{{ ucwords(str_replace('_', ' ', $history->status)) }}</p>
                                        @if($history->comment)
                                            <p class="text-xs text-neutral-600 mt-0.5">{{ $history->comment }}</p>
                                        @endif
                                        <p class="text-xs text-neutral-600 mt-0.5">{{ $history->created_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Customer</h2>
                </div>
                <div class="p-4 space-y-2">
                    <p class="text-sm font-medium text-neutral-900">{{ $order->user->full_name }}</p>
                    <p class="text-sm text-neutral-600">{{ $order->user->email }}</p>
                </div>
            </div>

            {{-- Delivery Address --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Delivery Address</h2>
                </div>
                <div class="p-4">
                    @php
                        $address = $order->shipping_address_snapshot;
                    @endphp
                    @if($address)
                        <div class="text-sm text-neutral-700 space-y-1">
                            @if(!empty($address['name']))
                                <p class="font-medium">{{ $address['name'] }}</p>
                            @endif
                            <p>{{ $address['address_line_1'] ?? '' }}</p>
                            @if(!empty($address['address_line_2']))
                                <p>{{ $address['address_line_2'] }}</p>
                            @endif
                            <p>{{ $address['city'] ?? '' }}{{ isset($address['state']) ? ', ' . $address['state'] : '' }} {{ $address['postal_code'] ?? '' }}</p>
                            @if(!empty($address['phone']))
                                <a href="tel:{{ $address['phone'] }}" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 font-medium mt-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    {{ $address['phone'] }}
                                </a>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-neutral-600">No address available</p>
                    @endif
                </div>
            </div>

            {{-- Shipment Info --}}
            @if($order->shipments->count())
                @php $shipment = $order->shipments->last(); @endphp
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Shipment</h2>
                    </div>
                    <div class="p-4 space-y-2 text-sm">
                        @if($shipment->carrier)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Carrier</span>
                                <span class="font-medium text-neutral-900">{{ $shipment->carrier }}</span>
                            </div>
                        @endif
                        @if($shipment->tracking_number)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Tracking #</span>
                                <span class="font-mono text-neutral-900">{{ $shipment->tracking_number }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Status</span>
                            <span class="font-medium text-neutral-900">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Payment Info --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="font-semibold text-neutral-900">Payment</h2>
                    @if($order->payment_collected)
                        <span class="badge badge-success">Collected</span>
                    @else
                        <span class="badge badge-warning">Pending</span>
                    @endif
                </div>
                <div class="p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Payment Status</span>
                        <span class="font-medium text-neutral-900">{{ ucwords($order->payment_status ?? 'Pending') }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-neutral-100">
                        <span class="font-semibold text-neutral-900">Amount to Collect</span>
                        <span class="font-semibold text-neutral-900">@price($order->total)</span>
                    </div>
                    @if($order->payment_collected)
                        <div class="mt-3 p-3 bg-success-50 rounded-lg">
                            <div class="flex items-center gap-2 text-success-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium">Payment Collected</span>
                            </div>
                            @if($order->payment_collected_at)
                                <p class="text-xs text-success-600 mt-1">{{ $order->payment_collected_at->format('M d, Y h:i A') }}</p>
                            @endif
                        </div>
                    @elseif(in_array($order->status, ['out_for_delivery', 'delivered']))
                        <form action="{{ route('delivery.orders.collect-payment', $order) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full justify-center" onclick="return confirm('Confirm payment collection of @price($order->total)?')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Collect Payment - @price($order->total)
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.delivery>
