<x-layouts.app>
    <x-slot name="title">Track Your Order</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Track Order', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full bg-primary-50 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Track Your Order</h1>
                <p class="text-[13px] text-neutral-600 mt-2">
                    @auth
                        Enter your order number to see the latest status
                    @else
                        Enter your order number and email address to track your order
                    @endauth
                </p>
            </div>

            @if(isset($error))
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-[13px]">{{ $error }}</p>
                    </div>
                </div>
            @endif

            @auth
                <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-[13px] text-green-700">Signed in as <span class="font-semibold">{{ auth()->user()->email }}</span> — just enter your order number.</p>
                </div>
            @endauth

            <div class="bg-white border border-neutral-100 rounded-xl">
                <form action="{{ route('track-order.track') }}" method="POST" class="p-5 sm:p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[13px] font-medium text-neutral-700 mb-1.5">Order Number</label>
                        <input type="text" name="order_number" value="{{ old('order_number') }}" required
                               class="w-full px-3.5 py-2.5 text-sm border border-neutral-200 rounded-lg bg-white text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors"
                               placeholder="e.g., ORD-20260211-A1B2C">
                        @error('order_number')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @guest
                        <div>
                            <label class="block text-[13px] font-medium text-neutral-700 mb-1.5">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full px-3.5 py-2.5 text-sm border border-neutral-200 rounded-lg bg-white text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors"
                                   placeholder="Enter the email used for the order">
                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endguest

                    <button type="submit" class="w-full px-5 py-2.5 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
                        Track Order
                    </button>
                </form>
            </div>

            @auth
                <p class="text-center text-[13px] text-neutral-600 mt-4">
                    Or view all your orders in
                    <a href="{{ route('account.orders.index') }}" class="text-primary-600 hover:text-primary-700 font-medium">My Orders</a>.
                </p>
            @endauth
        </div>
    </div>
</x-layouts.app>
