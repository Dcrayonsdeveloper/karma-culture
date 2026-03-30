@php $announcement = \App\Models\Setting::get('announcement_text') ?: 'Our All Products are 100% Made in India'; @endphp

<header id="main-header" x-data="{ visible: true, lastScroll: 0 }"
       x-on:scroll.window="
           let y = window.scrollY;
           if (y < 60) { visible = true }
           else if (y < lastScroll) { visible = true }
           else if (y > lastScroll + 5) { visible = false }
           lastScroll = y;
       "
       class="bg-white border-b border-neutral-100 fixed left-0 right-0 z-40"
       :style="'top:0; transition: transform 0.3s ease; transform: translateY(' + (visible ? '0' : '-100%') + ')'">
    <!-- Announcement Bar -->
    @if($announcement)
    <div style="background:#6F9CA2;" class="text-white text-center py-1.5 text-[11px] sm:text-xs font-medium tracking-wide">
        {{ $announcement }}
    </div>
    @endif
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-14 lg:h-16">

            <!-- Left: Mobile menu + Desktop Nav -->
            <div class="flex items-center gap-3 lg:gap-0 flex-1">
                <!-- Mobile menu button -->
                <button @click="$dispatch('toggle-mobile-nav')" class="lg:hidden p-1.5 -ml-1.5 text-neutral-700 hover:text-[#6F9CA2]" aria-label="Open menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Desktop Navigation (Left side) -->
                <nav class="hidden lg:flex items-center gap-1">
                    <a href="{{ route('new-arrivals') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-[#6F9CA2] font-medium transition-colors tracking-wide uppercase">New Arrival</a>
                    <a href="{{ route('categories.index') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-[#6F9CA2] font-medium transition-colors tracking-wide uppercase">Categories</a>
                    <a href="{{ route('bestsellers') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-[#6F9CA2] font-medium transition-colors tracking-wide uppercase">Bestsellers</a>
                    <a href="{{ route('deals') }}" class="px-3 py-2 text-[13px] text-[#F8931D] hover:text-[#E07E0A] font-semibold transition-colors tracking-wide uppercase">Sale</a>
                </nav>
            </div>

            <!-- Center: Logo -->
            <a href="{{ url('/') }}" class="flex items-center shrink-0">
                @php $siteLogo = \App\Models\Setting::get('site_logo', ''); @endphp
                @if($siteLogo)
                    <img id="site-logo" src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name') }}" class="h-7 lg:h-11 object-contain">
                @else
                    <img id="site-logo" src="{{ asset('images/colorlogo.png') }}" alt="ForeverKids" class="h-7 lg:h-11 object-contain">
                @endif
            </a>
            <style>@media (max-width: 767px) { #site-logo { height: 25px; } }</style>

            <!-- Right: Nav links + Icons -->
            <div class="flex items-center gap-1 lg:gap-0 flex-1 justify-end">

                <!-- Desktop Navigation (Right side) -->
                <nav class="hidden lg:flex items-center gap-1 mr-2">
                    @if(config('app.wholesale_enabled'))
                        <a href="{{ route('wholesale') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-[#6F9CA2] font-medium transition-colors tracking-wide uppercase">Wholesale</a>
                    @endif
                </nav>

                <!-- Mobile search icon (shown below sm, links to search page) -->
                <a href="{{ route('search') }}" class="sm:hidden p-2 text-neutral-600 hover:text-[#6F9CA2] transition-colors" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </a>

                <!-- Inline Search Bar with Typewriter + Mic (hidden on mobile) -->
                <div class="relative hidden sm:block flex-1 max-w-xs lg:max-w-sm mx-1 lg:mx-3"
                     x-data="searchBar()"
                     @click.outside="showResults = false">
                    <form action="{{ route('search') }}" method="GET" class="relative flex items-center">
                        <!-- Search icon -->
                        <svg class="absolute left-2.5 w-4 h-4 text-neutral-600 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>

                        <!-- Input with typewriter placeholder -->
                        <input type="text"
                               name="q"
                               x-ref="searchInput"
                               x-model="query"
                               @input.debounce.300ms="fetchSuggestions()"
                               @focus="showResults = true; stopTypewriter()"
                               @blur="if(!query) startTypewriter()"
                               @keydown.escape="showResults = false; $refs.searchInput.blur()"
                               :placeholder="currentPlaceholder"
                               class="w-full pl-8 pr-16 py-2 text-sm bg-neutral-50 border border-neutral-200 rounded-full placeholder-neutral-400 focus:bg-white focus:border-[#6F9CA2] transition-all"
                               autocomplete="off">

                        <!-- Mic button (only shown when browser supports Speech Recognition) -->
                        <button x-show="recognition" x-cloak
                                type="button"
                                @click.prevent="toggleMic()"
                                class="absolute right-8 p-1 transition-colors z-10"
                                :class="listening ? 'text-red-500 animate-pulse' : 'text-neutral-600 hover:text-[#6F9CA2]'"
                                :title="listening ? 'Stop listening' : 'Voice search'"
                                aria-label="Voice search">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                            </svg>
                        </button>

                        <!-- Submit button -->
                        <button type="submit" class="absolute right-2 p-1 text-neutral-600 hover:text-[#6F9CA2] transition-colors" aria-label="Search">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>

                    <!-- Search results dropdown -->
                    <div x-show="showResults && (results.length > 0 || (query.length >= 2 && !loading))" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-neutral-200 rounded-lg shadow-dropdown z-50 overflow-hidden">
                        <div x-show="results.length > 0" class="max-h-60 overflow-y-auto">
                            <ul class="py-1">
                                <template x-for="result in results" :key="result.id">
                                    <li>
                                        <a :href="result.url" class="flex items-center gap-2.5 px-3 py-2 hover:bg-neutral-50 transition-colors">
                                            <img :src="result.image" :alt="result.name" class="w-8 h-8 object-cover rounded">
                                            <div class="min-w-0">
                                                <div class="text-sm text-neutral-900 truncate" x-text="result.name"></div>
                                                <div class="text-xs text-neutral-600" x-text="result.category"></div>
                                            </div>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div x-show="query.length >= 2 && results.length === 0 && !loading" class="px-4 py-4 text-center">
                            <p class="text-sm text-neutral-600">No results found</p>
                        </div>
                    </div>
                </div>

                <!-- Wishlist -->
                <a href="{{ route('wishlist') }}" class="relative p-2 text-neutral-600 hover:text-[#6F9CA2] transition-colors hidden sm:flex" aria-label="Wishlist">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-cloak
                          x-show="$store.wishlist.count > 0"
                          x-text="$store.wishlist.count"
                          class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-[#F8931D] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                    </span>
                </a>

                <!-- User account - desktop -->
                <div class="relative hidden lg:block" x-data="dropdown()">
                    <button @click="toggle()" class="p-2 text-neutral-600 hover:text-[#6F9CA2] transition-colors" aria-label="Account">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </button>

                    <div x-cloak x-show="open" x-transition @click.outside="close()" class="absolute right-0 mt-1 w-48 bg-white border border-neutral-200 rounded-lg shadow-dropdown z-50 overflow-hidden">
                        @guest
                            <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-[#6F9CA2]">Login</a>
                            <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-[#6F9CA2]">Register</a>
                        @else
                            <div class="px-4 py-2 border-b border-neutral-100">
                                <div class="text-sm font-medium text-neutral-900">{{ auth()->user()->full_name }}</div>
                                <div class="text-xs text-neutral-600">{{ auth()->user()->email }}</div>
                            </div>
                            <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-[#6F9CA2]">Dashboard</a>
                            <a href="{{ route('account.orders.index') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-[#6F9CA2]">My Orders</a>
                            <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-[#6F9CA2]">Profile Settings</a>
                            @if(auth()->user()->deliveryPartner)
                                <a href="{{ route('delivery.login') }}" class="block px-4 py-2 text-sm text-[#6F9CA2] hover:text-[#5B878D] font-medium">Delivery Panel</a>
                            @else
                                <a href="{{ route('account.become-delivery-partner') }}" class="block px-4 py-2 text-sm text-[#6F9CA2] hover:text-[#5B878D] font-medium">Become a Delivery Partner</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-neutral-700 hover:text-[#6F9CA2]">Logout</button>
                            </form>
                        @endguest
                    </div>
                </div>

                <!-- Cart -->
                <a href="{{ route('cart.index') }}" class="relative p-2 text-neutral-700 hover:text-[#6F9CA2] transition-colors" aria-label="Cart">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span x-cloak
                          x-show="$store.cart.itemCount > 0"
                          x-text="$store.cart.itemCount"
                          class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-[#F8931D] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                    </span>
                </a>
            </div>
        </div>
    </div>
</header>
<!-- Spacer for fixed header + announcement bar -->
<div id="header-spacer"
     class="{{ $announcement ? 'h-[86px] lg:h-[94px]' : 'h-14 lg:h-16' }}"
     aria-hidden="true"></div>
<script>
    (function () {
        function syncSpacer() {
            var hdr = document.getElementById('main-header');
            var spc = document.getElementById('header-spacer');
            if (hdr && spc) spc.style.height = hdr.offsetHeight + 'px';
        }
        syncSpacer();
        window.addEventListener('resize', syncSpacer);
    })();
</script>
