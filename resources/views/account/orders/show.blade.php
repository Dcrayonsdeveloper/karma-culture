<x-layouts.app>
    <x-slot name="title">Order {{ $order->order_number }}</x-slot>

    @php
        // Where every line-item well on this page lands when its picture is
        // missing, so a deleted product or a file that has gone from disk still
        // shows something rather than an empty box.
        $placeholder = asset_v('images/no-product-image.svg');
    @endphp

    {{-- These wells are only 44-80px across, and the shared frame is tuned for a
         full-size card: its 24px blur fades out before it reaches an edge this
         close, and its 30px glyph does not fit. Scale both down to the well. --}}
    <style>
        .kk-media--thumb { background: #f5f5f5; }
    </style>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <x-breadcrumb :items="[['label' => 'Account', 'url' => route('account.dashboard')], ['label' => 'Orders', 'url' => route('account.orders.index')], ['label' => $order->order_number]]" />
            <div class="flex flex-col lg:flex-row gap-8 mt-4">
                <!-- Sidebar -->
                @include('account.partials.sidebar')

                <!-- Main Content -->
                <div class="flex-1">

                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-neutral-900">{{ $order->order_number }}</h1>
                                <p class="text-[13px] text-neutral-600">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                        @php
                            $statusColors = [
                                'confirmed' => 'bg-[#6F9CA2]/5 text-[#5B878D] border-[#6F9CA2]/30',
                                'processing' => 'bg-[#6F9CA2]/5 text-[#5B878D] border-[#6F9CA2]/30',
                                'packed' => 'bg-[#6F9CA2]/10 text-[#5B878D] border-[#6F9CA2]/30',
                                'shipped' => 'bg-[#6F9CA2]/15 text-[#4A7A80] border-[#6F9CA2]/40',
                                'out_for_delivery' => 'bg-[#6F9CA2]/5 text-[#5B878D] border-[#6F9CA2]/30',
                                'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                                'returned' => 'bg-neutral-100 text-neutral-700 border-neutral-200',
                            ];
                            $statusIcons = [
                                'confirmed' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                'processing' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
                                'packed' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                                'shipped' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
                                'delivered' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                                'cancelled' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                            ];
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-[13px] font-semibold self-start sm:self-auto {{ $statusColors[$order->status] ?? 'bg-neutral-100 text-neutral-700 border-neutral-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $statusIcons[$order->status] ?? $statusIcons['confirmed'] !!}</svg>
                            {{ str_replace('_', ' ', ucfirst($order->status)) }}
                        </span>
                    </div>

                    {{-- Order Tracking Card. The same timeline the Track page draws,
                         shown here so the customer sees where the parcel is on the
                         page they land on. The estimated-delivery strip is left off:
                         this page already prints the expected date in the sidebar. --}}
                    @include('account.orders.partials.tracking-timeline', ['showEta' => false])

                    {{-- Delivered Confirmation Banner --}}
                    @if($order->status === 'delivered')
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4 flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-[14px] font-semibold text-emerald-800">Your order has been delivered!</h3>
                                <p class="text-[13px] text-emerald-600 mt-0.5">
                                    Delivered on {{ $order->delivered_at ? $order->delivered_at->format('d M Y, h:i A') : 'N/A' }}
                                    @if($order->payment_collected && ($order->metadata['payment_method'] ?? 'cod') === 'cod')
                                        - Payment collected successfully
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Order Items (Left Column) -->
                        <div class="lg:col-span-2 space-y-4">
                            <!-- Items Card -->
                            <div class="bg-white rounded-xl border border-neutral-100">
                                <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-neutral-100">
                                    <svg class="w-4.5 h-4.5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <h2 class="text-sm font-semibold text-neutral-900">Order Items</h2>
                                    <span class="text-[12px] text-neutral-600 ml-auto">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</span>
                                </div>

                                <div class="divide-y divide-neutral-50">
                                    @foreach($order->items as $item)
                                        <div class="flex gap-3.5 p-4">
                                            @if($item->product && $item->product->primary_image_url)
                                                {{-- Contained in the fixed 64px well so the customer can still
                                                     tell which of their items this row is. --}}
                                                <div class="kk-media kk-media--thumb w-16 h-16 rounded-lg border border-neutral-100 shrink-0">
                                                    <img class="kk-media__fill" src="{{ $item->product->primary_image_url }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                                    <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product_name }}"
                                                         data-fallback="{{ $placeholder }}" loading="lazy" decoding="async">
                                                    <span class="kk-media__fallback" aria-hidden="true">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                                            <circle cx="8.5" cy="9.5" r="1.5"/>
                                                            <path d="M21 15l-5-5L5 20"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                            @else
                                                <div class="w-16 h-16 rounded-lg bg-neutral-100 flex items-center justify-center shrink-0">
                                                    <svg class="w-6 h-6 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[13px] font-semibold text-neutral-900">{{ $item->product_name }}</p>
                                                @if($item->variant_name)
                                                    <p class="text-[12px] text-neutral-600 mt-0.5">{{ $item->variant_name }}</p>
                                                @endif
                                                @if($item->sku)
                                                    <p class="text-[11px] text-neutral-600 mt-0.5">SKU: {{ $item->sku }}</p>
                                                @endif
                                                <div class="flex items-center justify-between mt-2">
                                                    <span class="text-[12px] text-neutral-600">@price($item->price) &times; {{ $item->quantity }}</span>
                                                    <span class="text-[13px] font-semibold text-neutral-900">@price($item->total)</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Price Breakdown -->
                                <div class="px-5 py-4 border-t border-neutral-100 space-y-2">
                                    <div class="flex justify-between text-[13px]">
                                        <span class="text-neutral-600">Subtotal</span>
                                        <span class="text-neutral-700">@price($order->subtotal)</span>
                                    </div>
                                    @if($order->discount > 0)
                                        <div class="flex justify-between text-[13px]">
                                            <span class="text-emerald-600 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                </svg>
                                                Discount{{ $order->coupon ? ' ('.$order->coupon->code.')' : '' }}
                                            </span>
                                            <span class="text-emerald-600 font-medium">-@price($order->discount)</span>
                                        </div>
                                    @endif
                                    @if($order->tax > 0)
                                        <div class="flex justify-between text-[13px]">
                                            <span class="text-neutral-600">Tax</span>
                                            <span class="text-neutral-700">@price($order->tax)</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between text-[13px]">
                                        <span class="text-neutral-600">Shipping</span>
                                        <span class="text-neutral-700">
                                            @if($order->shipping_cost > 0)
                                                @price($order->shipping_cost)
                                            @else
                                                <span class="text-emerald-600">Free</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between pt-2.5 mt-1 border-t border-dashed border-neutral-200">
                                        <span class="text-sm font-bold text-neutral-900">Total</span>
                                        <span class="text-sm font-bold text-neutral-900">@price($order->total)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping & Billing -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Shipping Address -->
                                <div class="bg-white rounded-xl border border-neutral-100 p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <h3 class="text-[13px] font-semibold text-neutral-900">Shipping Address</h3>
                                    </div>
                                    @php $shipping = $order->shipping_address_snapshot; @endphp
                                    @if($shipping)
                                        <div class="text-[13px] text-neutral-600 leading-relaxed">
                                            <p class="font-medium text-neutral-800 wrap-anywhere">{{ $shipping['name'] ?? '' }}</p>
                                            @if(!empty($shipping['phone']))
                                                <p class="text-neutral-600 text-[12px]">{{ $shipping['phone'] }}</p>
                                            @endif
                                            <p class="mt-1 wrap-anywhere">
                                                {{ $shipping['address_line_1'] ?? '' }}@if(!empty($shipping['address_line_2'])), {{ $shipping['address_line_2'] }}@endif<br>
                                                {{ $shipping['city'] ?? '' }}, {{ $shipping['state'] ?? '' }} {{ $shipping['postal_code'] ?? '' }}
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Billing Address -->
                                <div class="bg-white rounded-xl border border-neutral-100 p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <h3 class="text-[13px] font-semibold text-neutral-900">Billing Address</h3>
                                    </div>
                                    @php $billing = $order->billing_address_snapshot; @endphp
                                    @if($billing)
                                        <div class="text-[13px] text-neutral-600 leading-relaxed">
                                            <p class="font-medium text-neutral-800 wrap-anywhere">{{ $billing['name'] ?? '' }}</p>
                                            <p class="mt-1 wrap-anywhere">
                                                {{ $billing['address_line_1'] ?? '' }}<br>
                                                {{ $billing['city'] ?? '' }}, {{ $billing['state'] ?? '' }} {{ $billing['postal_code'] ?? '' }}
                                            </p>
                                        </div>
                                    @else
                                        <p class="text-[13px] text-neutral-600 italic">Same as shipping</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Order Notes -->
                            @if($order->notes)
                                <div class="bg-white rounded-xl border border-neutral-100 p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        <h3 class="text-[13px] font-semibold text-neutral-900">Order Notes</h3>
                                    </div>
                                    <p class="text-[13px] text-neutral-600">{{ $order->notes }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Right Sidebar -->
                        <div class="space-y-4">
                            <!-- Order Info -->
                            <div class="bg-white rounded-xl border border-neutral-100 p-4">
                                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Order Details</h3>
                                <dl class="space-y-2.5">
                                    <div class="flex justify-between text-[13px]">
                                        <dt class="text-neutral-600">Order Date</dt>
                                        <dd class="font-medium text-neutral-700">{{ format_date($order->created_at) }}</dd>
                                    </div>
                                    <div class="flex justify-between text-[13px]">
                                        <dt class="text-neutral-600">Payment</dt>
                                        <dd class="font-medium text-neutral-700">
                                            @php $paymentMethod = $order->metadata['payment_method'] ?? 'cod'; @endphp
                                            @switch($paymentMethod)
                                                @case('cod') Cash on Delivery @break
                                                @case('card') Credit/Debit Card @break
                                                @case('upi') UPI @break
                                                @case('paypal') PayPal @break
                                                @default {{ ucfirst($paymentMethod) }}
                                            @endswitch
                                        </dd>
                                    </div>
                                    <div class="flex justify-between items-center text-[13px]">
                                        <dt class="text-neutral-600">Payment Status</dt>
                                        <dd>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium
                                                {{ $order->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($order->payment_status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $order->payment_status === 'paid' ? 'bg-emerald-500' : ($order->payment_status === 'failed' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                                                {{ ucfirst($order->payment_status) }}
                                            </span>
                                        </dd>
                                    </div>
                                    @if($order->packed_at)
                                        <div class="flex justify-between text-[13px]">
                                            <dt class="text-neutral-600">Packed</dt>
                                            <dd class="font-medium text-neutral-700">{{ format_date($order->packed_at) }}</dd>
                                        </div>
                                    @endif
                                    @if($order->shipped_at)
                                        <div class="flex justify-between text-[13px]">
                                            <dt class="text-neutral-600">Shipped</dt>
                                            <dd class="font-medium text-neutral-700">{{ format_date($order->shipped_at) }}</dd>
                                        </div>
                                    @endif
                                    @if($order->delivered_at)
                                        <div class="flex justify-between text-[13px]">
                                            <dt class="text-neutral-600">Delivered</dt>
                                            <dd class="font-medium text-neutral-700">{{ format_date($order->delivered_at) }}</dd>
                                        </div>
                                    @endif
                                    @if($order->expected_delivery_date && !$order->delivered_at && !in_array($order->status, ['cancelled', 'returned']))
                                        <div class="flex justify-between text-[13px]">
                                            <dt class="text-neutral-600">Expected By</dt>
                                            <dd class="font-semibold text-success-700">{{ $order->expected_delivery_date->format('d M Y') }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            {{-- Expected Delivery Banner --}}
                            @if($order->expected_delivery_date && !$order->delivered_at && !in_array($order->status, ['cancelled', 'returned']))
                                <div class="bg-success-50 border border-success-100 rounded-xl p-4 flex items-start gap-3">
                                    <svg class="w-5 h-5 text-success-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <div>
                                        <p class="text-[13px] font-semibold text-success-800">Expected Delivery</p>
                                        <p class="text-[13px] text-success-700 mt-0.5">
                                            {{ $order->expected_delivery_date->format('l, d M Y') }}
                                            @if($order->expected_delivery_date->isToday())
                                                <span class="font-bold">(Today!)</span>
                                            @elseif($order->expected_delivery_date->isTomorrow())
                                                <span class="font-bold">(Tomorrow)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif

                            {{-- Delivery Partner Info --}}
                            @if($order->deliveryPartner && in_array($order->status, ['shipped', 'out_for_delivery', 'delivered']))
                                <div class="bg-white rounded-xl border border-neutral-100 p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                        </svg>
                                        <h3 class="text-[13px] font-semibold text-neutral-900">Your Delivery Partner</h3>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-[#6F9CA2]/10 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[13px] font-medium text-neutral-800 wrap-anywhere">{{ $order->deliveryPartner->user->full_name }}</p>
                                                @if($order->deliveryPartner->vehicle_type)
                                                    <p class="text-[11px] text-neutral-600">{{ ucfirst($order->deliveryPartner->vehicle_type) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        @if($order->deliveryPartner->phone)
                                            <a href="tel:{{ $order->deliveryPartner->phone }}" class="flex items-center gap-1.5 min-h-10 sm:min-h-0 text-[12px] text-primary-600 hover:text-primary-700 font-medium">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                </svg>
                                                {{ $order->deliveryPartner->phone }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Payment Collected (COD) --}}
                            @if($order->payment_collected && ($order->metadata['payment_method'] ?? 'cod') === 'cod')
                                <div class="bg-emerald-50 rounded-xl border border-emerald-100 p-4">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <h3 class="text-[13px] font-semibold text-emerald-800">Payment Collected</h3>
                                    </div>
                                    <p class="text-[12px] text-emerald-600 mt-1">
                                        Cash on Delivery payment of @price($order->total) has been collected.
                                        @if($order->payment_collected_at)
                                            <br>{{ $order->payment_collected_at->format('d M Y, h:i A') }}
                                        @endif
                                    </p>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="bg-white rounded-xl border border-neutral-100 p-4 space-y-2.5">
                                @if(!in_array($order->status, ['cancelled', 'returned']))
                                    <a href="{{ route('account.orders.track', $order) }}"
                                       class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-primary-600 text-white text-[13px] font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                        </svg>
                                        Track Order
                                    </a>
                                @endif

                                <a href="{{ route('account.orders.invoice', $order) }}" target="_blank"
                                   class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-neutral-200 text-neutral-700 text-[13px] font-medium rounded-lg hover:bg-neutral-50 hover:border-neutral-300 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Download Invoice
                                </a>

                                @if($order->canBeCancelled())
                                    <form action="{{ route('account.orders.cancel', $order) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                        @csrf
                                        <button type="submit"
                                                class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-red-200 text-red-600 text-[13px] font-medium rounded-lg hover:bg-red-50 hover:border-red-300 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Cancel Order
                                        </button>
                                    </form>
                                @endif

                                @if($order->canBeReturned())
                                    <a href="{{ route('account.returns.create', ['order' => $order->id]) }}"
                                       class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-neutral-200 text-neutral-700 text-[13px] font-medium rounded-lg hover:bg-neutral-50 hover:border-neutral-300 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                        Request Return
                                    </a>
                                @elseif($order->status === 'delivered' && $order->delivered_at)
                                    {{-- Delivered but still inside the waiting period, or past the
                                         window. Showing nothing left the customer wondering whether
                                         returns existed at all. --}}
                                    @php
                                        $kkWindow = (int) \App\Models\Setting::get('return_window_days', 7);
                                        $kkWait   = (int) \App\Models\Setting::get('return_min_hours', 0);
                                        $kkOpensAt = $order->delivered_at->copy()->addHours($kkWait);
                                    @endphp
                                    <p class="text-[12px] text-neutral-500 text-center px-2">
                                        @if($kkOpensAt->isFuture())
                                            Returns open {{ $kkOpensAt->diffForHumans() }}.
                                        @else
                                            The {{ $kkWindow }}-day return window for this order has closed.
                                        @endif
                                    </p>
                                @endif

                                @if(in_array($order->status, ['delivered', 'completed']))
                                    <form action="{{ route('account.orders.reorder', $order) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-[#F8931D] text-white text-[13px] font-semibold rounded-lg hover:bg-[#E07E0A] transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Buy Again
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <!-- Order Timeline -->
                            @if($order->statusHistory->count())
                                <div class="bg-white rounded-xl border border-neutral-100 p-4">
                                    <h3 class="text-sm font-semibold text-neutral-900 mb-3">Order Timeline</h3>
                                    <div class="relative">
                                        @foreach($order->statusHistory as $index => $history)
                                            <div class="flex gap-3 {{ !$loop->last ? 'pb-4' : '' }} relative">
                                                <!-- Vertical Line -->
                                                @if(!$loop->last)
                                                    <div class="absolute left-1.75 top-4 bottom-0 w-px bg-neutral-200"></div>
                                                @endif
                                                <!-- Dot -->
                                                <div class="w-3.75 h-3.75 rounded-full border-2 shrink-0 mt-0.5
                                                    {{ $loop->first ? 'bg-primary-600 border-primary-600' : 'bg-white border-neutral-300' }}"></div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-[13px] font-medium text-neutral-800">{{ str_replace('_', ' ', ucfirst($history->status)) }}</p>
                                                    @if($history->comment)
                                                        <p class="text-[12px] text-neutral-600 mt-0.5">{{ $history->comment }}</p>
                                                    @endif
                                                    <p class="text-[11px] text-neutral-600 mt-0.5">{{ $history->created_at->format('d M Y, h:i A') }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Need Help -->
                            <div class="bg-white rounded-xl border border-neutral-100 p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="text-[13px] font-semibold text-neutral-900">Need Help?</h3>
                                </div>
                                <p class="text-[12px] text-neutral-600 leading-relaxed">
                                    If you have any questions about your order, please contact our support team.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
