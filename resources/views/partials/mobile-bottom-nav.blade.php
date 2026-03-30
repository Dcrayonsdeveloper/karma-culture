<!-- Mobile Bottom Navigation -->
<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-neutral-200 z-40 safe-area-inset-bottom">
    <div class="flex items-center justify-around h-16">
        <a href="{{ url('/') }}" class="flex flex-col items-center gap-1 px-3 py-2 {{ request()->routeIs('home') ? 'text-primary-500' : 'text-neutral-600' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-xs font-medium">Home</span>
        </a>

        <a href="{{ route('categories.index') }}" class="flex flex-col items-center gap-1 px-3 py-2 {{ request()->routeIs('categories*') ? 'text-primary-500' : 'text-neutral-600' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span class="text-xs font-medium">Categories</span>
        </a>

        <a href="{{ route('cart.index') }}" class="flex flex-col items-center gap-1 px-3 py-2 text-neutral-600 relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="text-xs font-medium">Cart</span>
            <span x-cloak
                  x-show="$store.cart.itemCount > 0"
                  x-text="$store.cart.itemCount"
                  class="absolute -top-1 right-0 w-5 h-5 bg-[#F8931D] text-white text-xs font-medium rounded-full flex items-center justify-center">
            </span>
        </a>

        <a href="{{ route('wishlist') }}" class="flex flex-col items-center gap-1 px-3 py-2 {{ request()->routeIs('wishlist') ? 'text-primary-500' : 'text-neutral-600' }} relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <span class="text-xs font-medium">Wishlist</span>
            <span x-cloak
                  x-show="$store.wishlist.count > 0"
                  x-text="$store.wishlist.count"
                  class="absolute -top-1 right-0 w-5 h-5 bg-[#F8931D] text-white text-xs font-medium rounded-full flex items-center justify-center">
            </span>
        </a>

        <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}" class="flex flex-col items-center gap-1 px-3 py-2 {{ request()->routeIs('account.*') ? 'text-primary-500' : 'text-neutral-600' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-xs font-medium">Account</span>
        </a>
    </div>
</nav>

<!-- Add padding to body to account for bottom nav -->
<style>
    @media (max-width: 1023px) {
        body {
            padding-bottom: 64px;
        }
    }
</style>
