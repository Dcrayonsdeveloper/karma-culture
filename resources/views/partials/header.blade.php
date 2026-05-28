@php $announcement = \App\Models\Setting::get('announcement_text') ?: 'Our All Products are 100% Made in India'; @endphp

<header id="main-header" x-data="{ visible: true, lastScroll: 0 }"
       x-on:scroll.window="
           let y = window.scrollY;
           if (y < 60) { visible = true }
           else if (y < lastScroll) { visible = true }
           else if (y > lastScroll + 5) { visible = false }
           lastScroll = y;
       "
       class="bg-kk-cream border-b border-kk-cream-dark fixed left-0 right-0 z-40"
       :style="'top:0; transition: transform 0.3s ease; transform: translateY(' + (visible ? '0' : '-100%') + ')'">
    <!-- Announcement Bar (marquee) -->
    @if($announcement)
    <div style="background:#2d1810;" class="kk-marquee text-kk-text-on-dark py-1.5 text-[11px] sm:text-xs font-medium tracking-[0.18em] uppercase">
        <div class="kk-marquee__track" aria-hidden="true">
            @for ($i = 0; $i < 8; $i++)
                <span class="kk-marquee__item">{{ $announcement }}</span>
                <span class="kk-marquee__sep">&bull;</span>
            @endfor
        </div>
        <span class="sr-only">{{ $announcement }}</span>
    </div>
    <style>
        .kk-marquee { overflow: hidden; position: relative; }
        .kk-marquee__track {
            display: inline-flex;
            white-space: nowrap;
            animation: kk-marquee-scroll 35s linear infinite;
            will-change: transform;
        }
        .kk-marquee__item,
        .kk-marquee__sep { padding: 0 22px; }
        .kk-marquee__sep { opacity: 0.5; }
        .kk-marquee:hover .kk-marquee__track { animation-play-state: paused; }
        @keyframes kk-marquee-scroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }
        @media (prefers-reduced-motion: reduce) {
            .kk-marquee__track { animation: none; justify-content: center; width: 100%; }
            .kk-marquee__track > :nth-child(n+3) { display: none; }
        }
    </style>
    @endif
    <div class="w-full px-4 lg:px-6">
        <div class="relative flex items-center justify-between h-16 lg:h-24">

            <!-- Left: Mobile menu + Desktop Nav -->
            <div class="flex items-center gap-3 lg:gap-0 flex-1">
                <!-- Mobile menu button -->
                <button @click="$dispatch('toggle-mobile-nav')" class="lg:hidden p-1.5 -ml-1.5 text-kk-brown hover:text-kk-tan-dark" aria-label="Open menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Desktop Navigation (Left side) -->
                <nav class="hidden lg:flex items-center gap-1">
                    <a href="{{ route('new-arrivals') }}" class="px-3 py-2 text-[12px] text-kk-brown hover:text-kk-tan-dark font-medium transition-colors tracking-[0.18em] uppercase">New In</a>

                    {{-- Categories: hover-triggered mega menu --}}
                    <div class="kk-mega"
                         x-data="{ open: false, closeT: null, menuTop: 0 }"
                         @mouseenter="clearTimeout(closeT); menuTop = $el.closest('header').getBoundingClientRect().bottom; open = true"
                         @mouseleave="closeT = setTimeout(() => open = false, 120)"
                         @resize.window="if (open) menuTop = $el.closest('header').getBoundingClientRect().bottom">
                        <a href="{{ route('categories.index') }}"
                           class="px-3 py-2 text-[12px] text-kk-brown hover:text-kk-tan-dark font-medium transition-colors tracking-[0.18em] uppercase inline-flex items-center gap-1">
                            Categories
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </a>
                        <div x-cloak x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             :style="`top: ${menuTop - 1}px`"
                             class="kk-mega__panel">
                            <div class="kk-mega__grid">
                                {{-- Men's section --}}
                                <div class="kk-mega__col">
                                    <h3 class="kk-mega__heading">Men's</h3>
                                    <div class="kk-mega__cats kk-mega__cats--4">
                                        <div class="kk-mega__group">
                                            <a href="{{ route('categories.index') }}?gender=mens&type=tshirts" class="kk-mega__cat">T-Shirts</a>
                                            <ul class="kk-mega__subs">
                                                <li><a href="{{ route('categories.index') }}?type=polo">Polo</a></li>
                                                <li><a href="{{ route('categories.index') }}?type=round-neck">Round Neck</a></li>
                                                <li><a href="{{ route('categories.index') }}?type=henley">Henley / Wide Neck</a></li>
                                                <li><a href="{{ route('categories.index') }}?type=mandarin">Mandarin</a></li>
                                                <li><a href="{{ route('categories.index') }}?type=oversized">Oversized</a></li>
                                            </ul>
                                        </div>

                                        <div class="kk-mega__group">
                                            <a href="{{ route('categories.index') }}?gender=mens&type=shirts" class="kk-mega__cat">Shirts</a>
                                            <ul class="kk-mega__subs">
                                                <li><a href="{{ route('categories.index') }}?fit=slim">Slim Fit</a></li>
                                                <li><a href="{{ route('categories.index') }}?fit=regular">Regular</a></li>
                                                <li><a href="{{ route('categories.index') }}?type=denim">Denims</a></li>
                                            </ul>
                                        </div>

                                        <div class="kk-mega__group">
                                            <a href="{{ route('categories.index') }}?gender=mens&type=kurtas" class="kk-mega__cat">Kurtas</a>
                                            <ul class="kk-mega__subs">
                                                <li><a href="{{ route('categories.index') }}?length=short">Short</a></li>
                                                <li><a href="{{ route('categories.index') }}?length=knee">Knee Length</a></li>
                                            </ul>
                                        </div>

                                        <div class="kk-mega__group">
                                            <a href="{{ route('categories.index') }}?gender=mens&type=trousers" class="kk-mega__cat">Trousers</a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Women's section --}}
                                <div class="kk-mega__col">
                                    <h3 class="kk-mega__heading">Women's</h3>
                                    <div class="kk-mega__cats kk-mega__cats--2">
                                        <div class="kk-mega__group"><a href="{{ route('categories.index') }}?gender=womens&type=tops" class="kk-mega__cat">Tops</a></div>
                                        <div class="kk-mega__group"><a href="{{ route('categories.index') }}?gender=womens&type=tshirts" class="kk-mega__cat">T-Shirts</a></div>
                                        <div class="kk-mega__group"><a href="{{ route('categories.index') }}?gender=womens&type=jumpsuit" class="kk-mega__cat">Jumpsuit</a></div>
                                        <div class="kk-mega__group"><a href="{{ route('categories.index') }}?gender=womens&type=co-ord" class="kk-mega__cat">Co-ord Sets</a></div>
                                        <div class="kk-mega__group"><a href="{{ route('categories.index') }}?gender=womens&type=kurtas" class="kk-mega__cat">Kurtas</a></div>
                                        <div class="kk-mega__group"><a href="{{ route('categories.index') }}?gender=womens&type=one-piece" class="kk-mega__cat">One Piece</a></div>
                                        <div class="kk-mega__group"><a href="{{ route('categories.index') }}?gender=womens&type=trousers" class="kk-mega__cat">Trousers</a></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('bestsellers') }}" class="px-3 py-2 text-[12px] text-kk-brown hover:text-kk-tan-dark font-medium transition-colors tracking-[0.18em] uppercase">Bestsellers</a>
                    <a href="{{ route('deals') }}" class="px-3 py-2 text-[12px] text-kk-tan-dark hover:text-kk-brown font-semibold transition-colors tracking-[0.18em] uppercase">Sale</a>
                </nav>

                <style>
                    .kk-mega { position: relative; }
                    .kk-mega__panel {
                        position: fixed;
                        left: 0;
                        right: 0;
                        width: 100vw;
                        background: #EFE2CB;
                        border-top: none;
                        border-bottom: 1px solid var(--color-kk-cream-dark, #e3d2b3);
                        box-shadow: none;
                        padding: 32px 48px;
                        z-index: 60;
                    }
                    .kk-mega__grid {
                        display: grid;
                        grid-template-columns: 1.6fr 1fr;
                        gap: 64px;
                        max-width: 1400px;
                        margin: 0 auto;
                    }
                    .kk-mega__heading {
                        font-family: 'Cormorant Garamond', Georgia, serif;
                        font-size: 20px;
                        font-weight: 700;
                        color: var(--color-kk-brown, #4a2d1a);
                        margin: 0 0 16px;
                    }
                    .kk-mega__cats { display: grid; gap: 24px 28px; }
                    .kk-mega__cats--4 { grid-template-columns: repeat(4, 1fr); }
                    .kk-mega__cats--2 { grid-template-columns: repeat(2, 1fr); }
                    .kk-mega__group { margin: 0; }
                    .kk-mega__cat {
                        display: inline-block;
                        font-family: 'Cormorant Garamond', Georgia, serif;
                        font-size: 15px;
                        font-weight: 600;
                        color: var(--color-kk-text, #2d1810);
                        text-decoration: none;
                        margin-bottom: 4px;
                        transition: color 0.15s ease;
                    }
                    .kk-mega__cat:hover { color: var(--color-kk-tan-dark, #8c5c34); }
                    .kk-mega__subs {
                        list-style: none;
                        padding: 0;
                        margin: 0 0 0 2px;
                    }
                    .kk-mega__subs li { margin: 2px 0; }
                    .kk-mega__subs a {
                        display: inline-block;
                        font-family: 'Cormorant Garamond', Georgia, serif;
                        font-size: 13px;
                        color: var(--color-kk-text-muted, #7a6555);
                        text-decoration: none;
                        padding: 2px 0;
                        transition: color 0.15s ease;
                    }
                    .kk-mega__subs a:hover { color: var(--color-kk-brown, #4a2d1a); }
                </style>
            </div>

            <!-- Center: Logo (absolute-centered so it stays in the geometric middle regardless of left/right widths) -->
            <a href="{{ url('/') }}" class="absolute left-[44%] top-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center shrink-0 pointer-events-auto">
                @php $siteLogo = \App\Models\Setting::get('site_logo', ''); @endphp
                @if($siteLogo)
                    <img id="site-logo" src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name', 'Karmaa Kulture') }}" class="h-14 lg:h-20 object-contain">
                @else
                    <img id="site-logo" src="{{ asset('images/karmaa-kulture-logo.png') }}" alt="Karmaa Kulture" class="h-14 lg:h-20 object-contain">
                @endif
            </a>
            <style>@media (max-width: 767px) { #site-logo { height: 52px; } }</style>

            <!-- Right: Nav links + Icons -->
            <div class="flex items-center gap-1 lg:gap-0 flex-1 justify-end">

                <!-- Desktop Navigation (Right side) -->
                <nav class="hidden lg:flex items-center gap-1 mr-2">
                    @if(config('app.wholesale_enabled'))
                        <a href="{{ route('wholesale') }}" class="px-3 py-2 text-[12px] text-kk-brown hover:text-kk-tan-dark font-medium transition-colors tracking-[0.18em] uppercase">Wholesale</a>
                    @endif
                </nav>

                <!-- Mobile search icon (shown below sm, links to search page) -->
                <a href="{{ route('search') }}" class="sm:hidden p-2 text-kk-brown hover:text-kk-tan-dark transition-colors" aria-label="Search">
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
                        <svg class="absolute left-2.5 w-4 h-4 text-kk-brown pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
                               class="w-full pl-8 pr-16 py-2 text-sm bg-kk-cream-lighter border border-kk-cream-dark rounded-full text-kk-brown placeholder-kk-text-muted focus:bg-white focus:border-kk-brown transition-all"
                               autocomplete="off">

                        <!-- Mic button (only shown when browser supports Speech Recognition) -->
                        <button x-show="recognition" x-cloak
                                type="button"
                                @click.prevent="toggleMic()"
                                class="absolute right-8 p-1 transition-colors z-10"
                                :class="listening ? 'text-red-500 animate-pulse' : 'text-kk-brown hover:text-kk-tan-dark'"
                                :title="listening ? 'Stop listening' : 'Voice search'"
                                aria-label="Voice search">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                            </svg>
                        </button>

                        <!-- Submit button -->
                        <button type="submit" class="absolute right-2 p-1 text-kk-brown hover:text-kk-tan-dark transition-colors" aria-label="Search">
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
                         class="absolute left-0 right-0 top-full mt-1 bg-kk-cream-lighter border border-kk-cream-dark rounded-lg shadow-dropdown z-50 overflow-hidden">
                        <div x-show="results.length > 0" class="max-h-60 overflow-y-auto">
                            <ul class="py-1">
                                <template x-for="result in results" :key="result.id">
                                    <li>
                                        <a :href="result.url" class="flex items-center gap-2.5 px-3 py-2 hover:bg-kk-cream transition-colors">
                                            <img :src="result.image" :alt="result.name" class="w-8 h-8 object-cover rounded">
                                            <div class="min-w-0">
                                                <div class="text-sm text-kk-text truncate" x-text="result.name"></div>
                                                <div class="text-xs text-kk-text-muted" x-text="result.category"></div>
                                            </div>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div x-show="query.length >= 2 && results.length === 0 && !loading" class="px-4 py-4 text-center">
                            <p class="text-sm text-kk-text-muted">No results found</p>
                        </div>
                    </div>
                </div>

                <!-- Wishlist -->
                <a href="{{ route('wishlist') }}" class="relative p-2 text-kk-brown hover:text-kk-tan-dark transition-colors hidden sm:flex" aria-label="Wishlist">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-cloak
                          x-show="$store.wishlist.count > 0"
                          x-text="$store.wishlist.count"
                          class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-kk-tan-dark text-kk-cream text-[10px] font-bold rounded-full flex items-center justify-center">
                    </span>
                </a>

                <!-- User account - desktop -->
                @guest
                    <button type="button"
                            class="hidden lg:block p-2 text-kk-brown hover:text-kk-tan-dark transition-colors"
                            aria-label="Login"
                            @click="$dispatch('open-login-modal')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </button>
                @else
                <div class="relative hidden lg:block" x-data="dropdown()">
                    <button @click="toggle()" class="p-2 text-kk-brown hover:text-kk-tan-dark transition-colors" aria-label="Account">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </button>

                    <div x-cloak x-show="open" x-transition @click.outside="close()" class="absolute right-0 mt-1 w-48 bg-kk-cream-lighter border border-kk-cream-dark rounded-lg shadow-dropdown z-50 overflow-hidden">
                        @if(false)
                            <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-kk-brown hover:bg-kk-cream hover:text-kk-tan-dark">Login</a>
                            <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-kk-brown hover:bg-kk-cream hover:text-kk-tan-dark">Register</a>
                        @else
                            <div class="px-4 py-2 border-b border-kk-cream-dark">
                                <div class="text-sm font-medium text-kk-text">{{ auth()->user()->full_name }}</div>
                                <div class="text-xs text-kk-text-muted">{{ auth()->user()->email }}</div>
                            </div>
                            <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 text-sm text-kk-brown hover:bg-kk-cream hover:text-kk-tan-dark">Dashboard</a>
                            <a href="{{ route('account.orders.index') }}" class="block px-4 py-2 text-sm text-kk-brown hover:bg-kk-cream hover:text-kk-tan-dark">My Orders</a>
                            <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-sm text-kk-brown hover:bg-kk-cream hover:text-kk-tan-dark">Profile Settings</a>
                            @if(auth()->user()->deliveryPartner)
                                <a href="{{ route('delivery.login') }}" class="block px-4 py-2 text-sm text-kk-tan-dark hover:bg-kk-cream font-medium">Delivery Panel</a>
                            @else
                                <a href="{{ route('account.become-delivery-partner') }}" class="block px-4 py-2 text-sm text-kk-tan-dark hover:bg-kk-cream font-medium">Become a Delivery Partner</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-kk-brown hover:bg-kk-cream hover:text-kk-tan-dark">Logout</button>
                            </form>
                        @endif
                    </div>
                </div>
                @endguest

                <!-- Cart -->
                <a href="{{ route('cart.index') }}" class="relative p-2 text-kk-brown hover:text-kk-tan-dark transition-colors" aria-label="Cart">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span x-cloak
                          x-show="$store.cart.itemCount > 0"
                          x-text="$store.cart.itemCount"
                          class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-kk-tan-dark text-kk-cream text-[10px] font-bold rounded-full flex items-center justify-center">
                    </span>
                </a>
            </div>
        </div>
    </div>
</header>
<!-- Spacer for fixed header + announcement bar -->
<div id="header-spacer"
     class="{{ $announcement ? 'h-24 lg:h-32' : 'h-16 lg:h-24' }}"
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

@guest
{{-- ====================================================
     LOGIN / SIGNUP MODAL — opens via $dispatch('open-login-modal')
     AJAX-wired to LoginController@login and RegisterController@register
     ==================================================== --}}
<div x-data="kkAuthModal()"
     @open-login-modal.window="openModal()"
     @keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     class="kk-loginmodal">
    <div class="kk-loginmodal__overlay" @click="open = false"></div>
    <div class="kk-loginmodal__shell"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <button type="button" class="kk-loginmodal__close" @click="open = false" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- LEFT (dark) --}}
        <div class="kk-loginmodal__left">
            <div class="kk-loginmodal__brand">
                <img src="{{ asset('images/karmaa-kulture-logo.png') }}" alt="Karmaa Kulture" class="kk-loginmodal__logo">
            </div>
            <h3 class="kk-loginmodal__welcome">Welcome to {{ config('app.name', 'Karmaa Kulture') }}!</h3>
            <p class="kk-loginmodal__subtitle">Login now to avail best offers</p>
        </div>

        {{-- RIGHT (form) --}}
        <div class="kk-loginmodal__right">
            <div class="kk-loginmodal__tabs">
                <button type="button" :class="mode === 'login' ? 'is-active' : ''" @click="switchMode('login')">Login</button>
                <button type="button" :class="mode === 'signup' ? 'is-active' : ''" @click="switchMode('signup')">Sign Up</button>
            </div>

            <p class="kk-loginmodal__notice" x-show="notice" x-text="notice" x-cloak></p>
            <p class="kk-loginmodal__error" x-show="error" x-text="error" x-cloak></p>

            <form @submit.prevent="submit()">
                {{-- Signup-only: full name --}}
                <input type="text" class="kk-loginmodal__field" x-show="mode === 'signup'"
                       x-model="form.full_name" placeholder="Full Name" autocomplete="name">

                <input type="email" class="kk-loginmodal__field"
                       x-model="form.email" placeholder="Email Address" autocomplete="email" required>

                {{-- Signup-only: phone --}}
                <input type="tel" class="kk-loginmodal__field" x-show="mode === 'signup'"
                       x-model="form.phone" placeholder="Phone Number (optional)" autocomplete="tel"
                       inputmode="numeric" maxlength="15">

                <input type="password" class="kk-loginmodal__field"
                       x-model="form.password" placeholder="Password" autocomplete="current-password" required>

                {{-- Signup-only: confirm password --}}
                <input type="password" class="kk-loginmodal__field" x-show="mode === 'signup'"
                       x-model="form.password_confirmation" placeholder="Confirm Password" autocomplete="new-password">

                <label class="kk-loginmodal__notify" x-show="mode === 'login'">
                    <input type="checkbox" x-model="form.remember">
                    <span>Remember me</span>
                </label>

                <button type="submit" class="kk-loginmodal__submit" :disabled="loading">
                    <span x-show="!loading" x-text="mode === 'login' ? 'Login' : 'Create Account'">Login</span>
                    <span x-show="loading" x-cloak>Please wait...</span>
                </button>
            </form>

            <p class="kk-loginmodal__legal">
                By continuing you agree to our
                <a href="#" class="kk-loginmodal__legal-link">Privacy Policy</a>
                <span> &amp; </span>
                <a href="#" class="kk-loginmodal__legal-link">T&amp;Cs.</a>
            </p>

            <div class="kk-loginmodal__fallback">
                <span x-show="mode === 'login'">New here? <a href="#" @click.prevent="switchMode('signup')">Create an account</a></span>
                <span x-show="mode === 'signup'" x-cloak>Already have an account? <a href="#" @click.prevent="switchMode('login')">Login</a></span>
            </div>
        </div>
    </div>
</div>

<script>
    function kkAuthModal() {
        return {
            open: false,
            mode: 'login',
            loading: false,
            error: '',
            notice: '',
            form: { full_name: '', email: '', phone: '', password: '', password_confirmation: '', remember: false },
            csrf: '{{ csrf_token() }}',
            openModal() { this.open = true; this.error = ''; this.notice = ''; },
            switchMode(m) { this.mode = m; this.error = ''; this.notice = ''; },
            async submit() {
                this.error = '';
                if (this.mode === 'signup' && this.form.password !== this.form.password_confirmation) {
                    this.error = 'Passwords do not match.';
                    return;
                }
                this.loading = true;
                const url = this.mode === 'login' ? '{{ route('login') }}' : '{{ route('register') }}';
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        if (this.mode === 'signup') {
                            this.notice = 'Account created! Please log in.';
                            this.mode = 'login';
                            this.form.full_name = '';
                            this.form.phone = '';
                            this.form.password = '';
                            this.form.password_confirmation = '';
                        } else {
                            window.location.reload();
                        }
                    } else if (data.errors) {
                        this.error = Object.values(data.errors)[0][0];
                    } else {
                        this.error = data.message || 'Something went wrong. Please try again.';
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                }
                this.loading = false;
            }
        };
    }
</script>

<style>
    .kk-loginmodal { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 16px; }
    .kk-loginmodal__overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.55); }
    .kk-loginmodal__shell {
        position: relative;
        display: grid;
        grid-template-columns: 1fr 1fr;
        width: 100%;
        max-width: 720px;
        background: #2d1810;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(0,0,0,0.35);
    }
    @media (max-width: 720px) {
        .kk-loginmodal__shell { grid-template-columns: 1fr; max-width: 420px; }
    }
    .kk-loginmodal__close {
        position: absolute; top: 10px; right: 10px;
        width: 32px; height: 32px;
        background: #2d1810; color: #efe2cb;
        border: 1px solid rgba(239, 226, 203, 0.4); border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        z-index: 3;
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .kk-loginmodal__close:hover { background: #1f1109; transform: rotate(90deg); }
    .kk-loginmodal__close svg { width: 18px; height: 18px; display: block; }
    .kk-loginmodal__left {
        background: #2d1810; color: #efe2cb;
        padding: 48px 32px;
        display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
    }
    .kk-loginmodal__brand { margin-bottom: 16px; }
    .kk-loginmodal__logo { height: 110px; filter: brightness(0) invert(1); }
    .kk-loginmodal__welcome { font-size: 18px; font-weight: 700; margin: 8px 0 4px; color: #efe2cb; }
    .kk-loginmodal__subtitle { font-size: 16px; font-weight: 600; margin: 0; color: #efe2cb; }
    .kk-loginmodal__right {
        background: #fff;
        padding: 36px 30px;
        display: flex; flex-direction: column; justify-content: center;
    }
    .kk-loginmodal__tabs {
        display: flex; gap: 4px; margin-bottom: 18px;
        border-bottom: 1px solid #ececec;
    }
    .kk-loginmodal__tabs button {
        flex: 1; padding: 10px 4px; background: none; border: none;
        font-size: 14px; font-weight: 600; color: #9ca3af; cursor: pointer;
        border-bottom: 2px solid transparent; margin-bottom: -1px;
        transition: color 0.15s ease, border-color 0.15s ease;
    }
    .kk-loginmodal__tabs button.is-active { color: #2d1810; border-bottom-color: #2d1810; }
    .kk-loginmodal__field {
        width: 100%; box-sizing: border-box;
        padding: 11px 12px; margin-bottom: 12px;
        border: 1px solid #d4d4d4; border-radius: 4px;
        font-size: 14px; color: #2d1810; background: #fff;
        outline: none; transition: border-color 0.15s ease;
    }
    .kk-loginmodal__field:focus { border-color: #2d1810; }
    .kk-loginmodal__field::placeholder { color: #9ca3af; }
    .kk-loginmodal__notify {
        display: flex; align-items: center; gap: 8px;
        font-size: 12px; color: #6b6b6b;
        margin-bottom: 16px; cursor: pointer;
    }
    .kk-loginmodal__notify input { width: 14px; height: 14px; accent-color: #2d1810; cursor: pointer; }
    .kk-loginmodal__submit {
        width: 100%; padding: 12px; border: none; border-radius: 4px;
        background: #2d1810; color: #fff;
        font-size: 14px; font-weight: 600;
        cursor: pointer; transition: background 0.15s ease;
        margin-bottom: 14px;
    }
    .kk-loginmodal__submit:hover:not(:disabled) { background: #1f1109; }
    .kk-loginmodal__submit:disabled { background: #6b6b6b; cursor: not-allowed; }
    .kk-loginmodal__error {
        background: #fdeceb; border: 1px solid #f3c9c5; color: #b71c00;
        font-size: 12px; padding: 8px 10px; border-radius: 4px; margin: 0 0 12px;
    }
    .kk-loginmodal__notice {
        background: #e8f6ec; border: 1px solid #bfe5c7; color: #1a7a2e;
        font-size: 12px; padding: 8px 10px; border-radius: 4px; margin: 0 0 12px;
    }
    .kk-loginmodal__legal {
        text-align: center; font-size: 11px; color: #6b6b6b; margin: 0 0 12px; line-height: 1.6;
    }
    .kk-loginmodal__legal-link { color: #2d1810; text-decoration: underline; font-weight: 500; }
    .kk-loginmodal__fallback {
        text-align: center; font-size: 12px; color: #6b6b6b;
        padding-top: 12px; border-top: 1px solid #efefef;
    }
    .kk-loginmodal__fallback a { color: #2d1810; text-decoration: none; font-weight: 600; }
    .kk-loginmodal__fallback a:hover { text-decoration: underline; }
</style>
@endguest
