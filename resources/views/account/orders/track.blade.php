<x-layouts.app>
    <x-slot name="title">Track Order {{ $order->order_number }}</x-slot>

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

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[
                ['label' => 'My Account', 'url' => route('account.dashboard')],
                ['label' => 'Orders', 'url' => route('account.orders.index')],
                ['label' => $order->order_number, 'url' => route('account.orders.show', $order)],
                ['label' => 'Track', 'url' => null],
            ]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar -->
            @include('account.partials.sidebar')

            <!-- Main Content -->
            <div class="flex-1">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Track Your Order</h1>
                        <p class="text-[13px] text-neutral-600 mt-1">Order {{ $order->order_number }}</p>
                    </div>
                    @if($latestShipment && $latestShipment->tracking_number)
                        <div class="sm:text-right">
                            <p class="text-xs text-neutral-600">Tracking ID</p>
                            <p class="font-mono font-bold text-primary-600 text-sm wrap-anywhere">{{ $latestShipment->tracking_number }}</p>
                            @if($latestShipment->carrier)
                                <p class="text-xs text-neutral-600 mt-0.5">via {{ $latestShipment->carrier }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Order Tracking Card --}}
                @include('account.orders.partials.tracking-timeline')

                {{-- Delivery Partner Info --}}
                @if($order->deliveryPartner && in_array($order->status, ['shipped', 'out_for_delivery', 'delivered']))
                    <div class="bg-white border border-neutral-100 rounded-xl mb-6">
                        <div class="px-5 py-4 border-b border-neutral-100">
                            <h2 class="text-[15px] font-semibold text-neutral-900">Your Delivery Partner</h2>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-neutral-900 wrap-anywhere">{{ $order->deliveryPartner->user->full_name }}</p>
                                    @if($order->deliveryPartner->vehicle_type)
                                        <p class="text-xs text-neutral-600 mt-0.5">{{ ucfirst($order->deliveryPartner->vehicle_type) }}{{ $order->deliveryPartner->vehicle_number ? ' - ' . $order->deliveryPartner->vehicle_number : '' }}</p>
                                    @endif
                                </div>
                                @if($order->deliveryPartner->phone)
                                    <a href="tel:{{ $order->deliveryPartner->phone }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ $order->deliveryPartner->phone }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Order Items Summary --}}
                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100">
                        <h2 class="text-[15px] font-semibold text-neutral-900">Items in this Order</h2>
                    </div>
                    <div class="divide-y divide-neutral-100">
                        @foreach($order->items as $item)
                            <div class="px-5 py-4 flex gap-3.5">
                                {{-- src used to fall back to an empty string, so an item whose product
                                     row is gone rendered as a bare grey square while the customer was
                                     trying to work out where their parcel is. --}}
                                <div class="kk-media kk-media--thumb w-14 h-14 rounded-lg shrink-0">
                                    <img class="kk-media__fill" src="{{ $item->product->primary_image_url ?? $placeholder }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                    <img src="{{ $item->product->primary_image_url ?? $placeholder }}" alt="{{ $item->product_name }}"
                                         data-fallback="{{ $placeholder }}" loading="lazy" decoding="async">
                                    <span class="kk-media__fallback" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                            <circle cx="8.5" cy="9.5" r="1.5"/>
                                            <path d="M21 15l-5-5L5 20"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-[13px] font-medium text-neutral-900 truncate">{{ $item->product_name }}</h3>
                                    @if($item->variant_name)
                                        <p class="text-xs text-neutral-600">{{ $item->variant_name }}</p>
                                    @endif
                                    <p class="text-xs text-neutral-600 mt-0.5">Qty: {{ $item->quantity }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[13px] font-semibold text-neutral-900">@price($item->total)</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Back to Order --}}
                <div class="mt-4">
                    <a href="{{ route('account.orders.show', $order) }}" class="text-[13px] text-primary-600 hover:text-primary-700 font-medium inline-flex items-center gap-1.5 min-h-10 sm:min-h-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Order Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
