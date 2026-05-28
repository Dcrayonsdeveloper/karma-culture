<!-- Mobile sidebar backdrop -->
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-black/50 z-20 lg:hidden"></div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-neutral-200 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0">

    <!-- Logo -->
    <div class="flex items-center gap-3 h-16 px-6 border-b border-neutral-200">
        <x-application-logo class="w-8 h-8" />
        <span class="font-bold text-lg text-neutral-900">Seller Center</span>
    </div>

    <!-- Seller info -->
    <div class="p-4 border-b border-neutral-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                @if(auth()->user()->seller?->logo_url)
                    <img src="{{ auth()->user()->seller->logo_url }}" alt="{{ auth()->user()->seller->business_name }}" class="w-full h-full rounded-full object-cover">
                @else
                    <span class="font-medium text-primary-600">{{ substr(auth()->user()->seller?->business_name ?? 'S', 0, 1) }}</span>
                @endif
            </div>
            <div>
                <div class="font-medium text-neutral-900 text-sm">{{ auth()->user()->seller?->business_name ?? 'My Store' }}</div>
                <div class="text-xs {{ auth()->user()->seller?->is_verified ? 'text-success-600' : 'text-warning-600' }}">
                    {{ auth()->user()->seller?->is_verified ? 'Verified Seller' : 'Pending Verification' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-8rem)]">
        <!-- Dashboard -->
        <a href="{{ route('seller.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.dashboard') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Dashboard
        </a>

        <!-- Orders Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Orders</p>
            <a href="{{ route('seller.orders.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.orders.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                All Orders
                @if($pendingOrdersCount ?? 0)
                    <span class="ml-auto bg-warning-100 text-warning-700 text-xs font-medium px-2 py-0.5 rounded-full">
                        {{ $pendingOrdersCount }}
                    </span>
                @endif
            </a>
            <a href="{{ route('seller.returns.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.returns.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                Returns
            </a>
        </div>

        <!-- Products Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Products</p>
            <a href="{{ route('seller.products.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.products.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                My Products
            </a>
            <a href="{{ route('seller.products.create') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.products.create') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Product
            </a>
            <a href="{{ route('seller.inventory.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.inventory.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Inventory
            </a>
        </div>

        <!-- Marketing Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Marketing</p>
            <a href="{{ route('seller.promotions.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.promotions.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
                Promotions
            </a>
            <a href="{{ route('seller.coupons.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.coupons.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Coupons
            </a>
        </div>

        <!-- Engagement Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Engagement</p>
            <a href="{{ route('seller.reviews.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.reviews.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                Reviews
            </a>
            <a href="{{ route('seller.questions.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.questions.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Q&A
            </a>
            <a href="{{ route('seller.messages.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.messages.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Messages
            </a>
        </div>

        <!-- Finance Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Finance</p>
            <a href="{{ route('seller.earnings.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.earnings.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Earnings
            </a>
            <a href="{{ route('seller.payouts.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.payouts.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Payouts
            </a>
        </div>

        <!-- Reports Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Reports</p>
            <a href="{{ route('seller.reports.sales') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.reports.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics
            </a>
        </div>

        <!-- Settings Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Settings</p>
            <a href="{{ route('seller.settings.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('seller.settings.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Store Settings
            </a>
        </div>
    </nav>
</aside>
