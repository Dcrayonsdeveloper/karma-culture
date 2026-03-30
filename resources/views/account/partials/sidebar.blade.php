<aside class="lg:w-60 shrink-0">
    <nav class="bg-white border border-neutral-100 rounded-xl p-3 space-y-0.5">
        <a href="{{ route('account.dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.dashboard') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('account.orders.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.orders*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            My Orders
        </a>

        <a href="{{ route('wishlist') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('wishlist*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            Wishlist
        </a>

        <a href="{{ route('account.addresses.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.addresses*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Addresses
        </a>

        <a href="{{ route('account.returns.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.returns*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            Returns
        </a>

        <a href="{{ route('account.reviews') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.reviews*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            My Reviews
        </a>

        @if(\App\Models\Setting::get('support_tickets_enabled', true))
        <a href="{{ route('account.tickets.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.tickets*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
            Raise Ticket
        </a>
        @endif

        <div class="pt-3 mt-3 border-t border-neutral-100">
            @if(auth()->user()->deliveryPartner)
                <a href="{{ route('delivery.login') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium text-[#6F9CA2] hover:bg-[#6F9CA2]/5 transition-colors">
                    <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    Delivery Panel
                </a>
            @else
                <a href="{{ route('account.become-delivery-partner') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.become-delivery-partner') ? 'bg-primary-50 text-primary-600' : 'text-[#6F9CA2] hover:bg-[#6F9CA2]/5' }} transition-colors">
                    <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    Become a Delivery Partner
                </a>
            @endif

            <a href="{{ route('account.profile') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.profile') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
                <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile Settings
            </a>

            <a href="{{ route('account.notifications') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium {{ request()->routeIs('account.notifications') ? 'bg-primary-50 text-primary-600' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }} transition-colors">
                <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Notifications
            </a>

            <form action="{{ route('logout') }}" method="POST" class="mt-1">
                @csrf
                <button type="submit" class="flex items-center gap-2.5 px-3 py-2 rounded-lg w-full text-left text-[13px] font-medium text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 transition-colors">
                    <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </nav>
</aside>
