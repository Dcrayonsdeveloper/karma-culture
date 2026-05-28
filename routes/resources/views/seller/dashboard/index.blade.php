<x-layouts.seller>
    <x-slot name="title">Dashboard</x-slot>

    <!-- Welcome Banner -->
    @unless($seller->is_verified)
        <div class="mb-6 p-4 bg-warning-50 border border-warning-200 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 text-warning-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h3 class="font-medium text-warning-800">Account Verification Pending</h3>
                <p class="text-sm text-warning-700 mt-1">
                    Your seller account is under review. Some features may be limited until verification is complete.
                    <a href="{{ route('seller.settings.index') }}" class="font-medium underline">Upload verification documents</a>
                </p>
            </div>
        </div>
    @endunless

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Today's Orders -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Today's Orders</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ $todayOrders }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ route('seller.orders.index') }}" class="text-sm text-primary-600 hover:text-primary-700">View orders &rarr;</a>
            </div>
        </div>

        <!-- Today's Revenue -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Today's Revenue</p>
                    <p class="text-2xl font-bold text-neutral-900">@price($todayRevenue)</p>
                </div>
                <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ route('seller.earnings.index') }}" class="text-sm text-primary-600 hover:text-primary-700">View earnings &rarr;</a>
            </div>
        </div>

        <!-- Active Products -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Active Products</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ $activeProducts }}<span class="text-sm text-neutral-600">/{{ $totalProducts }}</span></p>
                </div>
                <div class="w-12 h-12 bg-info-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ route('seller.products.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Manage products &rarr;</a>
            </div>
        </div>

        <!-- Store Rating -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Store Rating</p>
                    <div class="flex items-center gap-2">
                        <p class="text-2xl font-bold text-neutral-900">{{ number_format($averageRating, 1) }}</p>
                        <div class="flex">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= round($averageRating) ? 'text-warning-400' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    </div>
                </div>
                <div class="w-12 h-12 bg-warning-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ route('seller.reviews.index') }}" class="text-sm text-primary-600 hover:text-primary-700">View reviews &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('seller.products.create') }}" class="card p-4 text-center hover:border-primary-300 transition-colors group">
            <div class="w-12 h-12 mx-auto bg-primary-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary-200 transition-colors">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-neutral-700">Add Product</span>
        </a>

        <a href="{{ route('seller.orders.index', ['status' => 'pending']) }}" class="card p-4 text-center hover:border-primary-300 transition-colors group">
            <div class="w-12 h-12 mx-auto bg-warning-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-warning-200 transition-colors relative">
                <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @if($pendingOrders > 0)
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-error-500 text-white text-xs rounded-full flex items-center justify-center">{{ $pendingOrders }}</span>
                @endif
            </div>
            <span class="text-sm font-medium text-neutral-700">Pending Orders</span>
        </a>

        <a href="{{ route('seller.inventory.low-stock') }}" class="card p-4 text-center hover:border-primary-300 transition-colors group">
            <div class="w-12 h-12 mx-auto bg-error-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-error-200 transition-colors">
                <svg class="w-6 h-6 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-neutral-700">Low Stock</span>
        </a>

        <a href="{{ route('seller.payouts.create') }}" class="card p-4 text-center hover:border-primary-300 transition-colors group">
            <div class="w-12 h-12 mx-auto bg-success-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-success-200 transition-colors">
                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-neutral-700">Request Payout</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
                    <h2 class="font-semibold text-neutral-900">Recent Orders</h2>
                    <a href="{{ route('seller.orders.index') }}" class="text-sm text-primary-600 hover:text-primary-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('seller.orders.show', $order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                            {{ $order->order_number }}
                                        </a>
                                        <p class="text-xs text-neutral-600">{{ $order->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-neutral-900">
                                        {{ $order->user->full_name ?? 'Guest' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-neutral-600">
                                        {{ $order->items->count() }} items
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge {{ $order->status === 'completed' ? 'badge-success' : ($order->status === 'pending' ? 'badge-warning' : 'badge-info') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-medium">
                                        @price($order->items->sum('total'))
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-neutral-600">
                                        No orders yet. Keep promoting your products!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Low Stock Alert -->
            @if($lowStockProducts->count())
                <div class="card">
                    <div class="p-4 border-b border-neutral-200 flex items-center gap-2">
                        <svg class="w-5 h-5 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h2 class="font-semibold text-neutral-900">Low Stock Alert</h2>
                    </div>
                    <div class="divide-y divide-neutral-200">
                        @foreach($lowStockProducts as $product)
                            <div class="p-4 flex items-center gap-3">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-neutral-900 truncate">{{ $product->name }}</p>
                                    <p class="text-xs text-error-600">Only {{ $product->stock_quantity }} left</p>
                                </div>
                                <a href="{{ route('seller.products.edit', $product) }}" class="text-sm text-primary-600 hover:text-primary-700">
                                    Update
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Top Products -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Top Selling Products</h2>
                </div>
                <div class="divide-y divide-neutral-200">
                    @forelse($topProducts as $product)
                        <div class="p-4 flex items-center gap-3">
                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-neutral-900 truncate">{{ $product->name }}</p>
                                <p class="text-xs text-neutral-600">{{ $product->total_sold }} sold</p>
                            </div>
                            <span class="text-sm font-medium">@price($product->price)</span>
                        </div>
                    @empty
                        <div class="p-4 text-center text-neutral-600 text-sm">
                            No sales yet
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Lifetime Stats</h2>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Total Revenue</span>
                        <span class="font-semibold">@price($totalRevenue)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Total Orders</span>
                        <span class="font-semibold">{{ number_format($totalOrders) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Total Products</span>
                        <span class="font-semibold">{{ number_format($totalProducts) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.seller>
