<x-layouts.seller>
    <x-slot name="title">Return #{{ $return->return_number }}</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.returns.index') }}" class="hover:text-primary-600">Returns</a>
        <span>/</span>
        <span>{{ $return->return_number }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Return {{ $return->return_number }}</h1>
            <p class="text-neutral-600">{{ $return->created_at->format('F d, Y H:i') }}</p>
        </div>
        <span class="badge text-base px-4 py-2 {{ $return->status === 'approved' ? 'badge-success' : ($return->status === 'rejected' ? 'badge-error' : 'badge-warning') }}">
            {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Return Items -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Returned Items</h2>
                </div>
                @if($return->items->count())
                    <div class="divide-y divide-neutral-200">
                        @foreach($return->items as $item)
                            <div class="p-4 flex gap-4">
                                @if($item->orderItem && $item->orderItem->product)
                                    <img src="{{ $item->orderItem->product->primary_image_url ?? '' }}" alt="{{ $item->orderItem->product_name ?? $item->orderItem->product->name ?? 'Product' }}"
                                         class="w-16 h-16 rounded-lg object-cover">
                                @endif
                                <div class="flex-1">
                                    <h3 class="font-medium text-neutral-900">{{ $item->orderItem->product_name ?? 'Product' }}</h3>
                                    @if($item->reason)
                                        <p class="text-sm text-neutral-600">Reason: {{ $item->reason }}</p>
                                    @endif
                                    @if($item->condition)
                                        <p class="text-sm text-neutral-600">Condition: {{ ucfirst($item->condition) }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">Qty: {{ $item->quantity }}</p>
                                    @if($item->orderItem)
                                        <p class="text-sm text-neutral-600">@price($item->orderItem->price) each</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-neutral-600">No items listed for this return.</div>
                @endif
            </div>

            <!-- Return Reason -->
            @if($return->reason || $return->description)
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Return Reason</h2>
                    @if($return->reason)
                        <p class="font-medium text-neutral-700 mb-2">{{ $return->reason }}</p>
                    @endif
                    @if($return->description)
                        <p class="text-neutral-600">{{ $return->description }}</p>
                    @endif
                </div>
            @endif

            <!-- Return Images -->
            @if($return->images && count($return->images))
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Images</h2>
                    <div class="grid grid-cols-3 gap-4">
                        @foreach($return->images as $image)
                            <img src="{{ $image }}" alt="Return image" class="rounded-lg object-cover w-full h-32">
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Update Status -->
            @if(in_array($return->status, ['requested', 'approved', 'received']))
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Update Status</h2>
                    <form action="{{ route('seller.returns.status', $return) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Status</label>
                            <select name="status" class="form-input w-full">
                                @if($return->status === 'requested')
                                    <option value="approved">Approve Return</option>
                                    <option value="rejected">Reject Return</option>
                                @endif
                                @if(in_array($return->status, ['requested', 'approved']))
                                    <option value="received">Mark as Received</option>
                                @endif
                            </select>
                        </div>

                        <button type="submit" class="btn-primary w-full">Update Status</button>
                    </form>
                </div>
            @endif

            <!-- Return Info -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Return Info</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Return Number</dt>
                        <dd class="font-medium">{{ $return->return_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Type</dt>
                        <dd class="font-medium">{{ ucfirst($return->type ?? 'return') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Submitted</dt>
                        <dd class="font-medium">{{ $return->created_at->format('M d, Y') }}</dd>
                    </div>
                    @if($return->refund_amount)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Refund Amount</dt>
                            <dd class="font-medium">@price($return->refund_amount)</dd>
                        </div>
                    @endif
                    @if($return->refund_method)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Refund Method</dt>
                            <dd class="font-medium">{{ ucfirst($return->refund_method) }}</dd>
                        </div>
                    @endif
                    @if($return->approved_at)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Approved</dt>
                            <dd class="font-medium">{{ $return->approved_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                    @if($return->completed_at)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Completed</dt>
                            <dd class="font-medium">{{ $return->completed_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Order Info -->
            @if($return->order)
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Related Order</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Order Number</dt>
                            <dd>
                                <a href="{{ route('seller.orders.show', $return->order) }}" class="text-primary-600 hover:text-primary-700 font-medium">
                                    {{ $return->order->order_number }}
                                </a>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Order Date</dt>
                            <dd class="font-medium">{{ $return->order->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Order Status</dt>
                            <dd>
                                <span class="badge {{ $return->order->status === 'completed' ? 'badge-success' : 'badge-info' }}">
                                    {{ ucfirst($return->order->status) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif

            <!-- Customer Info -->
            @if($return->user)
                <div class="card p-6">
                    <h2 class="font-semibold text-neutral-900 mb-4">Customer</h2>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="font-medium text-primary-600">{{ substr($return->user->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">{{ $return->user->full_name }}</p>
                            <p class="text-sm text-neutral-600">{{ $return->user->email }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.seller>
