<x-layouts.app>
    <x-slot name="title">My Account - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'My Account', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar -->
            @include('account.partials.sidebar')

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Welcome Banner -->
                <div class="rounded-xl p-5 sm:p-6 mb-6 bg-linear-to-r from-primary-500 to-primary-600 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                            @if($user->avatar_url)
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="w-full h-full rounded-full object-cover">
                            @else
                                <span class="text-xl font-bold">{{ substr($user->first_name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-bold">Welcome back, {{ $user->first_name }}!</h1>
                            <p class="text-sm text-white/80">Member since {{ $user->created_at->format('F Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Order Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                    <div class="bg-white border border-neutral-100 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-neutral-900">{{ $orderStats['total'] }}</div>
                        <div class="text-xs text-neutral-600 mt-0.5">Total Orders</div>
                    </div>
                    <div class="bg-white border border-neutral-100 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ $orderStats['confirmed'] }}</div>
                        <div class="text-xs text-neutral-600 mt-0.5">Confirmed</div>
                    </div>
                    <div class="bg-white border border-neutral-100 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-info-600">{{ $orderStats['processing'] }}</div>
                        <div class="text-xs text-neutral-600 mt-0.5">Processing</div>
                    </div>
                    <div class="bg-white border border-neutral-100 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-success-600">{{ $orderStats['completed'] }}</div>
                        <div class="text-xs text-neutral-600 mt-0.5">Completed</div>
                    </div>
                </div>

                <!-- Wallet Balance -->
                @if($creditBalance > 0)
                    <div class="bg-white border border-success-200 rounded-xl p-5 mb-6 flex items-center gap-4">
                        <div class="w-12 h-12 bg-success-50 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-neutral-600">Store Credit Balance</p>
                            <p class="text-xl font-bold text-success-600">@price($creditBalance)</p>
                        </div>
                        <p class="ml-auto text-xs text-neutral-600">Available for your next order</p>
                    </div>
                @endif

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8">
                    <a href="{{ route('account.orders.index') }}" class="bg-white border border-neutral-100 rounded-xl p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all group">
                        <svg class="w-7 h-7 mx-auto text-primary-500 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="text-[13px] font-medium text-neutral-700">My Orders</span>
                    </a>
                    <a href="{{ route('wishlist') }}" class="bg-white border border-neutral-100 rounded-xl p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all group">
                        <svg class="w-7 h-7 mx-auto text-primary-500 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span class="text-[13px] font-medium text-neutral-700">Wishlist ({{ $wishlistCount }})</span>
                    </a>
                    <a href="{{ route('account.addresses.index') }}" class="bg-white border border-neutral-100 rounded-xl p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all group">
                        <svg class="w-7 h-7 mx-auto text-primary-500 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-[13px] font-medium text-neutral-700">Addresses</span>
                    </a>
                    <a href="{{ route('account.profile') }}" class="bg-white border border-neutral-100 rounded-xl p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all group">
                        <svg class="w-7 h-7 mx-auto text-primary-500 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-[13px] font-medium text-neutral-700">Settings</span>
                    </a>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                        <h2 class="text-[15px] font-semibold text-neutral-900">Recent Orders</h2>
                        <a href="{{ route('account.orders.index') }}" class="text-[13px] text-primary-600 hover:text-primary-700 font-medium">View All</a>
                    </div>

                    @if($recentOrders->count())
                        <div class="divide-y divide-neutral-100">
                            @foreach($recentOrders as $order)
                                <div class="px-5 py-4 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-11 h-11 bg-neutral-50 rounded-lg flex items-center justify-center shrink-0 overflow-hidden">
                                            @if($order->items->first()?->product?->primary_image_url)
                                                <img src="{{ $order->items->first()->product->primary_image_url }}" alt="{{ $order->items->first()->product->name }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('account.orders.show', $order) }}" class="text-[13px] font-semibold text-neutral-900 hover:text-primary-600">
                                                Order #{{ $order->order_number }}
                                            </a>
                                            <p class="text-xs text-neutral-600 mt-0.5">
                                                {{ $order->created_at->format('M d, Y') }} &middot; {{ $order->items_count }} items
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[13px] font-semibold text-neutral-900">@price($order->total)</div>
                                        <span class="badge mt-1 {{ $order->status === 'completed' ? 'badge-success' : ($order->status === 'pending' ? 'badge-warning' : ($order->status === 'cancelled' ? 'badge-error' : 'badge-info')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-neutral-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <p class="text-sm text-neutral-600 mb-4">You haven't placed any orders yet.</p>
                            <a href="{{ route('products.index') }}" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
                                Start Shopping
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
