<!-- Mobile Navigation Drawer -->
<div x-data="{ open: false }"
     @toggle-mobile-nav.window="open = !open"
     @keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     class="lg:hidden fixed inset-0 z-50"
     role="dialog"
     aria-modal="true"
     aria-label="Navigation menu">

    <!-- Backdrop -->
    <div x-show="open"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-black/50"></div>

    <!-- Drawer -->
    <div x-show="open"
         x-transition:enter="transition-transform ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-[85vw] max-w-xs bg-white shadow-xl flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100 shrink-0">
            <a href="{{ url('/') }}" class="flex items-center">
                @php $navLogo = \App\Models\Setting::get('site_logo', ''); @endphp
                @if($navLogo)
                    <img src="{{ asset('storage/' . $navLogo) }}" alt="{{ config('app.name') }}" class="h-8 object-contain">
                @else
                    <img src="{{ asset('images/colorlogo.png') }}" alt="{{ config('app.name') }}" class="h-8 object-contain">
                @endif
            </a>
            <button @click="open = false" class="p-2.5 text-neutral-600 hover:text-neutral-600 rounded-full hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]" aria-label="Close menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- User section -->
        <div class="px-4 py-3 border-b border-neutral-100 shrink-0">
            @guest
                <div class="flex gap-2">
                    <a href="{{ route('login') }}" class="flex-1 py-3 text-center text-sm font-semibold text-white bg-[#F8931D] hover:bg-[#E07E0A] rounded-lg transition-colors">Login</a>
                    <a href="{{ route('register') }}" class="flex-1 py-3 text-center text-sm font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Register</a>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#6F9CA2]/10 rounded-full flex items-center justify-center shrink-0">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->full_name }}" class="w-full h-full rounded-full object-cover">
                        @else
                            <span class="text-sm font-semibold text-[#6F9CA2]">{{ substr(auth()->user()->first_name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()->full_name }}</div>
                        <div class="text-xs text-neutral-600 truncate">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            @endguest
        </div>

        <!-- Search -->
        <div class="px-4 py-3 border-b border-neutral-100 shrink-0">
            <form action="{{ route('search') }}" method="GET">
                <div class="relative">
                    <svg class="w-4 h-4 text-neutral-600 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="q" placeholder="Search products..."
                           class="w-full pl-9 pr-3 py-2 text-sm bg-neutral-50 border border-neutral-200 rounded-lg focus:outline-none focus:border-[#6F9CA2] placeholder-neutral-400">
                </div>
            </form>
        </div>

        <!-- Scrollable Navigation -->
        <nav class="flex-1 overflow-y-auto">
            <div class="py-2">
                <!-- Quick Links -->
                <a href="{{ url('/') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50 {{ request()->routeIs('home') ? 'text-[#6F9CA2]! bg-[#6F9CA2]/5 font-medium' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Home
                </a>

                <a href="{{ route('new-arrivals') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    New Arrivals
                </a>

                <a href="{{ route('bestsellers') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Bestsellers
                </a>

                <a href="{{ route('deals') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-error-600 hover:bg-error-50/50 font-medium">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Deals & Offers
                </a>

                <!-- Categories Section -->
                <div class="mt-2 pt-2 border-t border-neutral-100">
                    <p class="px-4 py-2 text-[11px] font-semibold text-neutral-600 uppercase tracking-wider">Shop by Category</p>

                    @foreach($navCategories ?? [] as $cat)
                        @if($cat->children->count())
                            <div x-data="{ expanded: false }">
                                <button @click="expanded = !expanded"
                                        class="flex items-center justify-between w-full px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50 transition-colors">
                                    <span>{{ $cat->name }}</span>
                                    <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="expanded" x-collapse>
                                    <div class="bg-neutral-50/50 py-1">
                                        <a href="{{ route('category.show', $cat) }}" class="block pl-8 pr-4 py-2 text-xs font-medium text-[#6F9CA2] hover:bg-neutral-100/50">
                                            View All {{ $cat->name }}
                                        </a>
                                        @foreach($cat->children as $child)
                                            <a href="{{ route('category.show', $child) }}" class="block pl-8 pr-4 py-2 text-sm text-neutral-600 hover:text-[#6F9CA2] hover:bg-neutral-100/50">
                                                {{ $child->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('category.show', $cat) }}" class="block px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                                {{ $cat->name }}
                            </a>
                        @endif
                    @endforeach

                    <a href="{{ route('categories.index') }}" class="flex items-center gap-2 px-4 py-3 text-sm text-[#6F9CA2] hover:bg-[#6F9CA2]/5 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        All Categories
                    </a>
                </div>

                <!-- Account Links -->
                @auth
                    <div class="mt-2 pt-2 border-t border-neutral-100">
                        <p class="px-4 py-2 text-[11px] font-semibold text-neutral-600 uppercase tracking-wider">My Account</p>

                        <a href="{{ route('account.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Dashboard
                        </a>

                        <a href="{{ route('account.orders.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            My Orders
                        </a>

                        <a href="{{ route('wishlist') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            Wishlist
                        </a>

                        <a href="{{ route('account.profile') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </a>

                        @if(auth()->user()->deliveryPartner)
                            <a href="{{ route('delivery.login') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-[#6F9CA2] hover:bg-[#6F9CA2]/5 font-medium">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                                Delivery Panel
                            </a>
                        @else
                            <a href="{{ route('account.become-delivery-partner') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-[#6F9CA2] hover:bg-[#6F9CA2]/5 font-medium">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                                Become Delivery Partner
                            </a>
                        @endif
                    </div>

                    <!-- Logout -->
                    <div class="mt-2 pt-2 border-t border-neutral-100 pb-4">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-3 text-sm text-neutral-600 hover:text-error-600 hover:bg-error-50/50 transition-colors">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </nav>
    </div>
</div>
