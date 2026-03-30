<x-layouts.seller>
    <x-slot name="title">Order {{ $order->order_number }}</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.orders.index') }}" class="hover:text-primary-600">Orders</a>
        <span>/</span>
        <span>{{ $order->order_number }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Order {{ $order->order_number }}</h1>
            <p class="text-neutral-600">{{ $order->created_at->format('F d, Y H:i') }}</p>
        </div>
        <span class="badge text-base px-4 py-2 {{ $order->status === 'completed' || $order->status === 'delivered' ? 'badge-success' : ($order->status === 'confirmed' ? 'badge-warning' : ($order->status === 'cancelled' ? 'badge-error' : 'badge-info')) }}">
            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Your Items -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Your Items in This Order</h2>
                </div>
                <div class="divide-y divide-neutral-200">
                    @foreach($order->items as $item)
                        <div class="p-4 flex gap-4">
                            <img src="{{ $item->product->primary_image_url ?? '' }}" alt="{{ $item->product_name }}"
                                 class="w-16 h-16 rounded-lg object-cover">
                            <div class="flex-1">
                                <h3 class="font-medium text-neutral-900">{{ $item->product_name }}</h3>
                                @if($item->variant_name)
                                    <p class="text-sm text-neutral-600">{{ $item->variant_name }}</p>
                                @endif
                                <p class="text-sm text-neutral-600">SKU: {{ $item->sku }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">@price($item->price) x {{ $item->quantity }}</p>
                                <p class="text-lg font-bold">@price($item->total)</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 bg-neutral-50">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Your Total</span>
                        <span>@price($sellerTotal)</span>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Ship To</h2>
                @php $shipping = $order->shipping_address_snapshot; @endphp
                @if($shipping)
                    <div class="text-neutral-600">
                        <p class="font-medium text-neutral-900">{{ $shipping['name'] ?? '' }}</p>
                        @if(!empty($shipping['phone']))
                            <p>{{ $shipping['phone'] }}</p>
                        @endif
                        <p>{{ $shipping['address_line_1'] ?? '' }}</p>
                        @if(!empty($shipping['address_line_2']))
                            <p>{{ $shipping['address_line_2'] }}</p>
                        @endif
                        <p>{{ $shipping['city'] ?? '' }}, {{ $shipping['state'] ?? '' }} {{ $shipping['postal_code'] ?? '' }}</p>
                        @if(!empty($shipping['country']))
                            <p>{{ $shipping['country'] }}</p>
                        @endif
                    </div>
                @else
                    <p class="text-neutral-600">No address available</p>
                @endif
            </div>

            <!-- Order Notes -->
            @if($order->notes)
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Customer Notes</h2>
                    <p class="text-neutral-600">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Update Status -->
            @if(in_array($order->status, ['confirmed', 'processing']))
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Update Status</h2>
                    <form action="{{ route('seller.orders.update-status', $order) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Status</label>
                            <select name="status" class="form-input w-full" id="order-status"
                                    onchange="document.getElementById('shipping-fields').style.display = this.value === 'shipped' ? 'block' : 'none'">
                                @if($order->status === 'confirmed')
                                    <option value="processing">Mark as Processing</option>
                                @endif
                                <option value="shipped">Mark as Shipped</option>
                            </select>
                        </div>

                        <div id="shipping-fields" style="display: {{ old('status') === 'shipped' ? 'block' : 'none' }}" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Carrier *</label>
                                <select name="carrier" class="form-input w-full">
                                    <option value="">Select carrier</option>
                                    <option value="USPS">USPS</option>
                                    <option value="UPS">UPS</option>
                                    <option value="FedEx">FedEx</option>
                                    <option value="DHL">DHL</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Tracking Number *</label>
                                <input type="text" name="tracking_number" class="form-input w-full"
                                       placeholder="Enter tracking number">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full">Update Status</button>
                    </form>
                </div>
            @endif

            <!-- Customer Info -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Customer</h2>
                @if($order->user)
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="font-medium text-primary-600">{{ substr($order->user->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">{{ $order->user->full_name }}</p>
                            <p class="text-sm text-neutral-600">{{ $order->user->email }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-neutral-600">Guest checkout</p>
                @endif
            </div>

            <!-- Order Info -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Order Info</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Order Date</dt>
                        <dd class="font-medium">{{ $order->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Payment Status</dt>
                        <dd>
                            <span class="badge {{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </dd>
                    </div>
                    @if($order->shipped_at)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Shipped Date</dt>
                            <dd class="font-medium">{{ $order->shipped_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                    @if($order->shipments && $order->shipments->last()?->tracking_number)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Tracking</dt>
                            <dd class="font-medium">{{ $order->shipments->last()->tracking_number }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Status History -->
            @if($order->statusHistory->count())
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Timeline</h2>
                    <div class="space-y-3">
                        @foreach($order->statusHistory as $history)
                            <div class="flex gap-3 text-sm">
                                <div class="w-2 h-2 mt-1.5 rounded-full bg-primary-500"></div>
                                <div>
                                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</p>
                                    @if($history->comment)
                                        <p class="text-neutral-600">{{ $history->comment }}</p>
                                    @endif
                                    <p class="text-xs text-neutral-600">{{ $history->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.seller>
