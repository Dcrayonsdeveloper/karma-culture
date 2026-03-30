<x-layouts.app>
    <x-slot name="title">Notification Preferences</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                @include('account.partials.sidebar')

                <div class="flex-1 max-w-2xl">
                    <h1 class="text-xl font-bold text-neutral-900 mb-5">Notification Preferences</h1>

                    @if(session('success'))
                        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('account.notification-preferences.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Orders -->
                        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden mb-4">
                            <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <h2 class="text-sm font-bold text-neutral-900">Orders</h2>
                            </div>
                            <div class="divide-y divide-neutral-100">
                                <!-- Table Header -->
                                <div class="px-5 py-2.5 flex items-center bg-neutral-50">
                                    <div class="flex-1">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Notification</span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Email</span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">In-app</span>
                                    </div>
                                </div>

                                <!-- Order Placed -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Order Placed</p>
                                        <p class="text-xs text-neutral-600">Receive a confirmation when your order is placed</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_placed_email" value="0">
                                            <input type="checkbox" name="order_placed_email" value="1" class="sr-only peer"
                                                   {{ old('order_placed_email', $preferences['order_placed_email'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_placed_inapp" value="0">
                                            <input type="checkbox" name="order_placed_inapp" value="1" class="sr-only peer"
                                                   {{ old('order_placed_inapp', $preferences['order_placed_inapp'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Order Shipped -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Order Shipped</p>
                                        <p class="text-xs text-neutral-600">Get notified when your order is shipped</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_shipped_email" value="0">
                                            <input type="checkbox" name="order_shipped_email" value="1" class="sr-only peer"
                                                   {{ old('order_shipped_email', $preferences['order_shipped_email'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_shipped_inapp" value="0">
                                            <input type="checkbox" name="order_shipped_inapp" value="1" class="sr-only peer"
                                                   {{ old('order_shipped_inapp', $preferences['order_shipped_inapp'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Order Delivered -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Order Delivered</p>
                                        <p class="text-xs text-neutral-600">Get notified when your order is delivered</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_delivered_email" value="0">
                                            <input type="checkbox" name="order_delivered_email" value="1" class="sr-only peer"
                                                   {{ old('order_delivered_email', $preferences['order_delivered_email'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_delivered_inapp" value="0">
                                            <input type="checkbox" name="order_delivered_inapp" value="1" class="sr-only peer"
                                                   {{ old('order_delivered_inapp', $preferences['order_delivered_inapp'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Order Cancelled -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Order Cancelled</p>
                                        <p class="text-xs text-neutral-600">Get notified if your order is cancelled</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_cancelled_email" value="0">
                                            <input type="checkbox" name="order_cancelled_email" value="1" class="sr-only peer"
                                                   {{ old('order_cancelled_email', $preferences['order_cancelled_email'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="order_cancelled_inapp" value="0">
                                            <input type="checkbox" name="order_cancelled_inapp" value="1" class="sr-only peer"
                                                   {{ old('order_cancelled_inapp', $preferences['order_cancelled_inapp'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Returns -->
                        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden mb-4">
                            <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                <h2 class="text-sm font-bold text-neutral-900">Returns</h2>
                            </div>
                            <div class="divide-y divide-neutral-100">
                                <!-- Table Header -->
                                <div class="px-5 py-2.5 flex items-center bg-neutral-50">
                                    <div class="flex-1">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Notification</span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Email</span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">In-app</span>
                                    </div>
                                </div>

                                <!-- Return Approved -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Return Approved</p>
                                        <p class="text-xs text-neutral-600">Get notified when your return request is approved</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="return_approved_email" value="0">
                                            <input type="checkbox" name="return_approved_email" value="1" class="sr-only peer"
                                                   {{ old('return_approved_email', $preferences['return_approved_email'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="return_approved_inapp" value="0">
                                            <input type="checkbox" name="return_approved_inapp" value="1" class="sr-only peer"
                                                   {{ old('return_approved_inapp', $preferences['return_approved_inapp'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Refund Processed -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Refund Processed</p>
                                        <p class="text-xs text-neutral-600">Get notified when your refund has been processed</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="refund_processed_email" value="0">
                                            <input type="checkbox" name="refund_processed_email" value="1" class="sr-only peer"
                                                   {{ old('refund_processed_email', $preferences['refund_processed_email'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="refund_processed_inapp" value="0">
                                            <input type="checkbox" name="refund_processed_inapp" value="1" class="sr-only peer"
                                                   {{ old('refund_processed_inapp', $preferences['refund_processed_inapp'] ?? true) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alerts -->
                        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden mb-5">
                            <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <h2 class="text-sm font-bold text-neutral-900">Alerts</h2>
                            </div>
                            <div class="divide-y divide-neutral-100">
                                <!-- Table Header -->
                                <div class="px-5 py-2.5 flex items-center bg-neutral-50">
                                    <div class="flex-1">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Notification</span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Email</span>
                                    </div>
                                    <div class="w-20 text-center">
                                        <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">In-app</span>
                                    </div>
                                </div>

                                <!-- Price Drop -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Price Drop</p>
                                        <p class="text-xs text-neutral-600">Get notified when a wishlisted item drops in price</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="price_drop_email" value="0">
                                            <input type="checkbox" name="price_drop_email" value="1" class="sr-only peer"
                                                   {{ old('price_drop_email', $preferences['price_drop_email'] ?? false) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="price_drop_inapp" value="0">
                                            <input type="checkbox" name="price_drop_inapp" value="1" class="sr-only peer"
                                                   {{ old('price_drop_inapp', $preferences['price_drop_inapp'] ?? false) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Back in Stock -->
                                <div class="px-5 py-3.5 flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-900">Back in Stock</p>
                                        <p class="text-xs text-neutral-600">Get notified when an out-of-stock item becomes available</p>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="back_in_stock_email" value="0">
                                            <input type="checkbox" name="back_in_stock_email" value="1" class="sr-only peer"
                                                   {{ old('back_in_stock_email', $preferences['back_in_stock_email'] ?? false) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                    <div class="w-20 flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="back_in_stock_inapp" value="0">
                                            <input type="checkbox" name="back_in_stock_inapp" value="1" class="sr-only peer"
                                                   {{ old('back_in_stock_inapp', $preferences['back_in_stock_inapp'] ?? false) ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-neutral-200 peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                            Save Preferences
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
