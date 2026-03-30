<x-layouts.delivery>
    <x-slot name="title">Return {{ $return->return_number }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                    <a href="{{ route('delivery.returns.index') }}" class="hover:text-primary-600 transition-colors">Return Pickups</a>
                    <svg class="w-3.5 h-3.5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-neutral-900">{{ $return->return_number }}</span>
                </div>
                <h1 class="text-2xl font-bold text-neutral-900">Return {{ $return->return_number }}</h1>
            </div>
            @php
                $statusColors = [
                    'approved' => 'bg-info-50 text-info-700',
                    'pickup_scheduled' => 'bg-warning-50 text-warning-700',
                    'picked_up' => 'bg-[#6F9CA2]/10 text-[#5B878D]',
                    'received' => 'bg-success-50 text-success-700',
                    'processed' => 'bg-success-50 text-success-700',
                    'completed' => 'bg-success-50 text-success-700',
                ];
            @endphp
            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ $statusColors[$return->status] ?? 'bg-neutral-50 text-neutral-700' }}">
                {{ $return->status === 'processed' ? 'Refund Processed' : ucwords(str_replace('_', ' ', $return->status)) }}
            </span>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Status Update --}}
            @if(in_array($return->status, ['approved', 'pickup_scheduled', 'picked_up']))
                <div class="card overflow-hidden">
                    <div class="p-5">
                        @if($return->status === 'approved')
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-neutral-900">Schedule Pickup</p>
                                    <p class="text-sm text-neutral-600">Confirm you'll pick up this return from the customer</p>
                                </div>
                            </div>
                            <form action="{{ route('delivery.returns.update-status', $return) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="pickup_scheduled">
                                <button type="submit" class="btn btn-primary w-full">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Schedule Pickup
                                </button>
                            </form>
                        @elseif($return->status === 'pickup_scheduled')
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-[#6F9CA2]/10 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-neutral-900">Confirm Pickup</p>
                                    <p class="text-sm text-neutral-600">Mark that you've collected the return items from the customer</p>
                                </div>
                            </div>
                            <form action="{{ route('delivery.returns.update-status', $return) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="picked_up">
                                <button type="submit" class="btn btn-primary w-full" style="background-color: rgb(147, 51, 234);">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    Mark as Picked Up
                                </button>
                            </form>
                        @elseif($return->status === 'picked_up')
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-neutral-900">Deliver to Warehouse</p>
                                    <p class="text-sm text-neutral-600">Mark that you've delivered the return items to the warehouse</p>
                                </div>
                            </div>
                            <form action="{{ route('delivery.returns.update-status', $return) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="received">
                                <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-success-600 hover:bg-success-700 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Mark as Delivered to Warehouse
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Return Items --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Return Items</h2>
                </div>
                <div class="divide-y divide-neutral-100">
                    @foreach($return->items as $item)
                        <div class="px-5 py-4 flex gap-4">
                            <div class="w-14 h-14 rounded-lg bg-neutral-50 ring-1 ring-neutral-200 overflow-hidden shrink-0">
                                @if($item->orderItem->product->primary_image_url ?? null)
                                    <img src="{{ $item->orderItem->product->primary_image_url }}" alt="{{ $item->orderItem->product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-neutral-900">{{ $item->orderItem->product_name ?? $item->orderItem->product->name ?? 'Product' }}</p>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-neutral-600">
                                    <span>Qty: <span class="font-medium text-neutral-700">{{ $item->quantity }}</span></span>
                                    @if($item->condition)
                                        @php
                                            $conditionColors = [
                                                'unopened' => 'bg-emerald-50 text-emerald-700',
                                                'opened' => 'bg-amber-50 text-amber-700',
                                                'damaged' => 'bg-red-50 text-red-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $conditionColors[$item->condition] ?? 'bg-neutral-50 text-neutral-600' }}">
                                            {{ ucfirst($item->condition) }}
                                        </span>
                                    @endif
                                </div>
                                @if($item->reason)
                                    <p class="text-xs text-neutral-600 mt-1 italic">"{{ $item->reason }}"</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Return Reason --}}
            @if($return->reason || $return->description)
                <div class="card p-5">
                    <h2 class="font-semibold text-neutral-900 mb-3">Return Reason</h2>
                    <p class="text-sm font-medium text-neutral-700">{{ $return->reason }}</p>
                    @if($return->description)
                        <p class="text-sm text-neutral-600 mt-1">{{ $return->description }}</p>
                    @endif
                </div>
            @endif

            {{-- Return Images --}}
            @if($return->images && count($return->images))
                <div class="card p-5">
                    <h2 class="font-semibold text-neutral-900 mb-3">Photos</h2>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($return->images as $image)
                            <a href="{{ $image }}" target="_blank" class="block rounded-lg overflow-hidden ring-1 ring-neutral-200 hover:ring-primary-300 transition-all">
                                <img src="{{ $image }}" alt="Return photo" class="w-full h-24 object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Pickup Address --}}
            <div class="card p-5">
                <h2 class="font-semibold text-neutral-900 mb-3">Pickup Address</h2>
                @php $address = $return->order->shipping_address_snapshot; @endphp
                @if($address)
                    <div class="text-sm text-neutral-600 space-y-1">
                        <p class="font-medium text-neutral-900">{{ $address['name'] ?? '' }}</p>
                        @if(!empty($address['phone']))
                            <a href="tel:{{ $address['phone'] }}" class="flex items-center gap-1.5 text-primary-600 hover:text-primary-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $address['phone'] }}
                            </a>
                        @endif
                        <p>{{ $address['address_line_1'] ?? '' }}</p>
                        @if(!empty($address['address_line_2']))
                            <p>{{ $address['address_line_2'] }}</p>
                        @endif
                        <p>{{ $address['city'] ?? '' }}, {{ $address['state'] ?? '' }} {{ $address['postal_code'] ?? '' }}</p>
                    </div>
                @else
                    <p class="text-sm text-neutral-600">No address available</p>
                @endif
            </div>

            {{-- Return Info --}}
            <div class="card p-5">
                <h2 class="font-semibold text-neutral-900 mb-3">Return Info</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Return Number</dt>
                        <dd class="font-medium text-neutral-900">{{ $return->return_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Type</dt>
                        <dd class="font-medium text-neutral-900 capitalize">{{ $return->type }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Order</dt>
                        <dd>
                            <a href="{{ route('delivery.orders.show', $return->order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                {{ $return->order->order_number }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Submitted</dt>
                        <dd class="font-medium text-neutral-900">{{ $return->created_at->format('M d, Y') }}</dd>
                    </div>
                    @if($return->pickup_scheduled_at)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Pickup Scheduled</dt>
                            <dd class="font-medium text-neutral-900">{{ $return->pickup_scheduled_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                    @if($return->picked_up_at)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Picked Up</dt>
                            <dd class="font-medium text-neutral-900">{{ $return->picked_up_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Customer Info --}}
            @if($return->order->user)
                <div class="card p-5">
                    <h2 class="font-semibold text-neutral-900 mb-3">Customer</h2>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="font-medium text-primary-600">{{ substr($return->order->user->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">{{ $return->order->user->full_name }}</p>
                            <p class="text-sm text-neutral-600">{{ $return->order->user->email }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.delivery>
