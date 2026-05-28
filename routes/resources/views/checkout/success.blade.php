<x-layouts.app>
    <x-slot name="title">Order Confirmed</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-10">
            <div class="max-w-2xl mx-auto">

                <!-- Success Header -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-neutral-900 mb-1">Order Confirmed!</h1>
                    <p class="text-[14px] text-neutral-600">Thank you for your order. We'll begin processing it shortly.</p>
                </div>

                <!-- Order Number Banner -->
                <div class="bg-white rounded-xl border border-neutral-100 p-4 mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-[12px] font-medium text-neutral-600 uppercase tracking-wider">Order Number</p>
                        <p class="text-base font-bold text-neutral-900 mt-0.5">{{ $order->order_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[12px] font-medium text-neutral-600 uppercase tracking-wider">Placed On</p>
                        <p class="text-sm font-medium text-neutral-700 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-xl border border-neutral-100 mb-4">
                    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-neutral-100">
                        <svg class="w-4.5 h-4.5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <h2 class="text-sm font-semibold text-neutral-900">Items Ordered</h2>
                        <span class="text-[12px] text-neutral-600 ml-auto">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</span>
                    </div>

                    <div class="divide-y divide-neutral-50">
                        @foreach($order->items as $item)
                            <div class="flex gap-3.5 p-4">
                                @if($item->product && $item->product->primary_image_url)
                                    <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product_name }}"
                                         class="w-14 h-14 object-cover rounded-lg border border-neutral-100 shrink-0">
                                @else
                                    <div class="w-14 h-14 rounded-lg bg-neutral-100 flex items-center justify-center shrink-0">
                                        <svg class="w-6 h-6 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-semibold text-neutral-900 truncate">{{ $item->product_name }}</p>
                                    @if($item->variant_name)
                                        <p class="text-[12px] text-neutral-600 mt-0.5">{{ $item->variant_name }}</p>
                                    @endif
                                    <p class="text-[12px] text-neutral-600 mt-0.5">Qty: {{ $item->quantity }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[13px] font-semibold text-neutral-900">@price($item->total)</p>
                                    @if($item->quantity > 1)
                                        <p class="text-[11px] text-neutral-600">@price($item->price) each</p>
                                    @endif
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
                                    Discount
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
                            <span class="text-sm font-bold text-neutral-900">Total Paid</span>
                            <span class="text-sm font-bold text-neutral-900">@price($order->total)</span>
                        </div>
                    </div>
                </div>

                <!-- Shipping & Payment Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
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
                                <p class="font-medium text-neutral-800">{{ $shipping['name'] ?? '' }}</p>
                                @if(!empty($shipping['phone']))
                                    <p class="text-neutral-600 text-[12px]">{{ $shipping['phone'] }}</p>
                                @endif
                                <p class="mt-1">
                                    {{ $shipping['address_line_1'] ?? '' }}@if(!empty($shipping['address_line_2'])), {{ $shipping['address_line_2'] }}@endif<br>
                                    {{ $shipping['city'] ?? '' }}, {{ $shipping['state'] ?? '' }} {{ $shipping['postal_code'] ?? '' }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-xl border border-neutral-100 p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <h3 class="text-[13px] font-semibold text-neutral-900">Payment</h3>
                        </div>
                        <div class="text-[13px] text-neutral-600">
                            @php $paymentMethod = $order->metadata['payment_method'] ?? 'cod'; @endphp
                            <p class="font-medium text-neutral-800">
                                @switch($paymentMethod)
                                    @case('cod')
                                        Cash on Delivery
                                        @break
                                    @case('card')
                                        Credit/Debit Card
                                        @break
                                    @case('upi')
                                        UPI
                                        @break
                                    @case('paypal')
                                        PayPal
                                        @break
                                    @default
                                        {{ ucfirst($paymentMethod) }}
                                @endswitch
                            </p>
                            <p class="text-[12px] mt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium
                                    {{ $order->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $order->payment_status === 'paid' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                @if($order->discount > 0)
                    <!-- Savings Banner -->
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 mb-4 flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <p class="text-[13px] font-medium text-emerald-800">
                            You saved @price($order->discount) on this order!
                        </p>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('account.orders.show', $order) }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Track Order
                    </a>
                    <a href="{{ route('products.index') }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 border border-neutral-200 text-neutral-700 text-sm font-medium rounded-lg hover:bg-neutral-50 hover:border-neutral-300 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                        Continue Shopping
                    </a>
                </div>

                <!-- Email Notice -->
                <p class="text-center text-[12px] text-neutral-600 mt-6">
                    A confirmation email has been sent to <span class="font-medium text-neutral-600">{{ auth()->user()->email }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- GA4 Purchase + FB Purchase tracking --}}
    @if(config('services.ga4.measurement_id') || config('services.facebook.pixel_id'))
    @php
        $trackingItems = $order->items->map(fn ($item) => [
            'item_id' => $item->sku ?? (string) $item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->price,
            'quantity' => $item->quantity,
        ]);
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var orderItems = @json($trackingItems);

            @if(config('services.ga4.measurement_id'))
            gtag('event', 'purchase', {
                transaction_id: '{{ $order->order_number }}',
                value: {{ (float) $order->total }},
                tax: {{ (float) $order->tax }},
                shipping: {{ (float) $order->shipping_cost }},
                currency: 'INR',
                items: orderItems
            });
            @endif

            @if(config('services.facebook.pixel_id'))
            fbq('track', 'Purchase', {
                content_ids: @json($order->items->pluck('product_id')->toArray()),
                content_type: 'product',
                value: {{ (float) $order->total }},
                currency: 'INR',
                num_items: {{ $order->items->sum('quantity') }}
            });
            @endif
        });
    </script>
    @endif
</x-layouts.app>
