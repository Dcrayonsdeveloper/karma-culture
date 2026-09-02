@php
    $announcement = \App\Models\Setting::get('announcement_text') ?: 'Free Shipping on Orders Above Rs. {threshold} | Easy Returns';
    // Single source of truth for the free-shipping threshold (Task 8).
    // Admins can write "{threshold}" in the announcement text and it is interpolated site-wide.
    $freeShipThreshold = (int) \App\Models\Setting::get('free_shipping_threshold', 999);
    $announcement = str_replace(['{threshold}', '{free_shipping_threshold}'], number_format($freeShipThreshold), $announcement);
@endphp

<header id="main-header"
       class="bg-kk-cream sticky top-0 left-0 right-0 z-40 shadow-sm">
    <!-- Announcement Bar (seamless marquee - two identical groups, translateX(-50%) loop) -->
    @if($announcement)
    <div style="background:#2d1810;" class="kk-marquee text-kk-text-on-dark py-1.5 text-[11px] sm:text-xs font-medium tracking-[0.18em] uppercase">
        <div class="kk-marquee__track" aria-hidden="true">
            @for ($g = 0; $g < 2; $g++)
                <div class="kk-marquee__group">
                    @for ($i = 0; $i < 6; $i++)
                        <span class="kk-marquee__item">{{ $announcement }}</span>
                        <span class="kk-marquee__sep">&bull;</span>
                    @endfor
                </div>
            @endfor
        </div>
        <span class="sr-only">{{ $announcement }}</span>
    </div>
    <style>
        .kk-marquee { overflow: hidden; position: relative; }
        /* Track holds two identical groups; shifting by exactly one group width (-50%)
           loops seamlessly at any viewport width (fixes desktop/ultrawide gaps). */
        .kk-marquee__track {
            display: flex;
            width: max-content;
            flex-wrap: nowrap;
            animation: kk-marquee-scroll 40s linear infinite;
            will-change: transform;
        }
        .kk-marquee__group { display: flex; flex-shrink: 0; white-space: nowrap; }
        .kk-marquee__item,
        .kk-marquee__sep { padding: 0 22px; }
        .kk-marquee__sep { opacity: 0.5; }
        .kk-marquee:hover .kk-marquee__track { animation-play-state: paused; }
        @keyframes kk-marquee-scroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }
        @media (prefers-reduced-motion: reduce) {
            .kk-marquee__track { animation: none; width: 100%; justify-content: center; }
            .kk-marquee__group:nth-child(2) { display: none; }
            .kk-marquee__group:first-child .kk-marquee__item:nth-of-type(n+2),
            .kk-marquee__group:first-child .kk-marquee__sep { display: none; }
        }
    </style>
    @endif
    <div class="w-full px-3 lg:px-4">
        {{-- Bar height matches the logo exactly (h-16 lg:h-20), so there is no
             dead space above or below it. Previously h-20/h-24, which left 8px
             of padding on each side of the logo. --}}
        <div class="relative flex items-center justify-between h-16 lg:h-20">

            <!-- Left: Mobile menu + Desktop Nav -->
            {{-- lg:min-w-fit + shrink-0 on the nav: Chrome miscomputes this nested
                 flex row's automatic minimum (min-width:auto), so without explicit
                 floors the nav links slide under the search bar at lg-xl widths. --}}
            <div class="flex items-center gap-3 lg:gap-0 flex-1 lg:min-w-fit">
                <!-- Mobile menu button -->
                <button @click="$dispatch('toggle-mobile-nav')" class="lg:hidden p-1.5 -ml-1.5 text-kk-brown hover:text-kk-tan-dark" aria-label="Open menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                {{-- Logo: centered (absolute) only below sm, where the search bar is
                     collapsed to an icon. From sm up it must sit in normal flow on the
                     left, otherwise the inline search bar overlaps it on tablets. --}}
                <a href="{{ url('/') }}" class="absolute inset-0 flex items-center justify-center pointer-events-none sm:static sm:inset-auto sm:justify-start sm:pointer-events-auto shrink-0 sm:mr-3 lg:mr-8">
                    @php $siteLogo = \App\Models\Setting::get('site_logo', ''); @endphp
                    @if($siteLogo)
                        <img id="site-logo" src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name', 'Karmaa Kulture') }}" class="h-16 lg:h-20 object-contain pointer-events-auto">
                    @else
                        <img id="site-logo" src="{{ asset('images/karmaa-kulture-logo.png') }}" alt="Karmaa Kulture" class="h-16 lg:h-20 object-contain pointer-events-auto">
                    @endif
                </a>

                <!-- Desktop Navigation (Left side) -->
                <nav class="hidden lg:flex items-center gap-1 shrink-0">
                    <a href="{{ route('new-arrivals') }}" class="px-2.5 py-2 text-[12px] text-kk-brown hover:text-kk-tan-dark font-medium transition-colors tracking-[0.12em] uppercase whitespace-nowrap">New In</a>

                    {{-- Categories: hover-triggered mega menu - clean text layout, data from admin --}}
                    @php
                        // Every active top-level category from admin, each with its active
                        // children. Previously this matched only the two slugs "mens" and
                        // "womens", so any other category an admin created never appeared.
                        $kkMegaGroups = \Illuminate\Support\Facades\Cache::remember('kk_mega_menu_v5', 300, function () {
                            return \App\Models\Category::whereNull('parent_id')
                                ->where('is_active', true)
                                ->orderBy('position')
                                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
                                ->get();
                        });
                    @endphp
                    <div class="kk-mega"
                         x-data="{ open: false, closeT: null }"
                         @mouseenter="clearTimeout(closeT); open = true"
                         @mouseleave="closeT = setTimeout(() => open = false, 120)">
                        <button type="button" @click="open = !open"
                                class="px-2.5 py-2 text-[12px] text-kk-brown hover:text-kk-tan-dark font-medium transition-colors tracking-[0.12em] uppercase whitespace-nowrap inline-flex items-center gap-1 cursor-pointer bg-transparent border-0">
                            Categories
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-cloak x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="kk-dd">
                            @php $kkRendered = false; @endphp
                            @foreach($kkMegaGroups as $group)
                                @if($group->children->isNotEmpty())
                                    @if($kkRendered)<div class="kk-dd__divider"></div>@endif
                                    <div class="kk-dd__label">{{ $group->name }}</div>
                                    @foreach($group->children as $child)
                                        <a href="{{ route('category.show', $child) }}" class="kk-dd__item">{{ $child->name }}</a>
                                    @endforeach
                                @else
                                    <a href="{{ route('category.show', $group) }}" class="kk-dd__item">{{ $group->name }}</a>
                                @endif
                                @php $kkRendered = true; @endphp
                            @endforeach
                        </div>
                    </div>

                    <a href="{{ route('bestsellers') }}" class="px-2.5 py-2 text-[12px] text-kk-brown hover:text-kk-tan-dark font-medium transition-colors tracking-[0.12em] uppercase whitespace-nowrap">Bestsellers</a>
                    <a href="{{ route('deals') }}" class="px-2.5 py-2 text-[12px] text-kk-tan-dark hover:text-kk-brown font-semibold transition-colors tracking-widest uppercase whitespace-nowrap">Introductory Offer</a>
                </nav>

                <style>
                    .kk-mega { position: relative; }
                    /* Normal compact dropdown - categories listed in sequence */
                    .kk-dd {
                        position: absolute;
                        top: 100%;
                        left: 0;
                        margin-top: 6px;
                        min-width: 220px;
                        background: var(--color-kk-cream-lighter, #fbf5e8);
                        border: 1px solid var(--color-kk-cream-dark, #e3d2b3);
                        border-radius: 8px;
                        box-shadow: 0 12px 32px rgba(45, 24, 16, 0.14);
                        padding: 8px 0;
                        z-index: 60;
                        /* All active categories now render here, so cap the height and scroll. */
                        max-height: min(70vh, 520px);
                        overflow-y: auto;
                        overscroll-behavior: contain;
                    }
                    .kk-dd__label {
                        font-family: 'Cormorant Garamond', Georgia, serif;
                        font-size: 12px;
                        font-weight: 700;
                        letter-spacing: 0.16em;
                        text-transform: uppercase;
                        color: var(--color-kk-tan-dark, #8c5c34);
                        padding: 8px 18px 4px;
                    }
                    .kk-dd__item {
                        display: block;
                        padding: 8px 18px;
                        font-size: 13px;
                        letter-spacing: 0.04em;
                        color: var(--color-kk-text, #2d1810);
                        text-decoration: none;
                        transition: background 0.15s ease, color 0.15s ease, padding-left 0.15s ease;
                    }
                    .kk-dd__item:hover {
                        background: var(--color-kk-cream-light, #f7eedb);
                        color: var(--color-kk-tan-dark, #8c5c34);
                        padding-left: 22px;
                    }
                    .kk-dd__divider {
                        height: 1px;
                        background: var(--color-kk-cream-dark, #e3d2b3);
                        margin: 8px 0;
                    }
                </style>
            </div>

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
                <div class="relative hidden sm:block flex-1 min-w-0 max-w-xs lg:max-w-sm mx-1 lg:mx-3"
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
                               class="w-full pl-9 pr-16 py-1.5 text-xs bg-kk-cream-lighter border border-kk-cream-dark rounded-full text-kk-brown placeholder-kk-text-muted focus:bg-white focus:border-kk-brown transition-all"
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

                        {{-- Voice search panel. Shown while the browser's own
                             permission prompt is open, so the customer can see
                             what is being asked for and why, and again if the
                             microphone is unavailable - a bare alert() gave no
                             context and no way back. --}}
                        <template x-if="micPanel">
                            <div class="fixed inset-0 z-[80] flex items-center justify-center p-4"
                                 @click.self="closeMicPanel()" x-cloak>
                                <div class="absolute inset-0" style="background: rgba(45,24,16,.55);"></div>
                                <div class="relative w-full max-w-sm rounded-2xl p-6 text-center shadow-2xl"
                                     style="background:#fff;">
                                    <button type="button" @click="closeMicPanel()" aria-label="Close"
                                            class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>

                                    <div class="mx-auto mb-4 w-16 h-16 rounded-full flex items-center justify-center"
                                         :style="micPanel === 'listening' ? 'background:#fdecea;' : 'background:#f4efe6;'">
                                        <svg class="w-7 h-7" :class="micPanel === 'listening' ? 'animate-pulse' : ''"
                                             :style="micPanel === 'listening' ? 'color:#dc362e;' : 'color:#8C5C34;'"
                                             fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                            <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                                        </svg>
                                    </div>

                                    <template x-if="micPanel === 'waiting'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">Waiting for permission</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">Allow microphone access to search with your voice.</p>
                                        </div>
                                    </template>

                                    <template x-if="micPanel === 'listening'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">Listening&hellip;</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">Say what you are looking for, like &ldquo;polo shirt in blue&rdquo;.</p>
                                        </div>
                                    </template>

                                    <template x-if="micPanel === 'blocked'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">Microphone is blocked</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">
                                                Your browser is blocking the microphone for this site, so it will not ask again.
                                            </p>
                                            <ol class="text-sm text-left mt-3 space-y-1 mx-auto" style="color:#444; max-width:17rem;">
                                                <li>1. Click the icon left of the web address</li>
                                                <li>2. Choose <strong>Site settings</strong></li>
                                                <li>3. Set <strong>Microphone</strong> to <strong>Allow</strong></li>
                                                <li>4. Reload this page</li>
                                            </ol>
                                        </div>
                                    </template>

                                    <template x-if="micPanel === 'denied'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">Permission not granted</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">Choose <strong>Allow</strong> when your browser asks, then try again.</p>
                                            <button type="button" @click="toggleMic()" class="mt-4 px-5 py-2 rounded-full text-white text-xs font-semibold" style="background:#8C5C34;">Try again</button>
                                        </div>
                                    </template>

                                    <template x-if="micPanel === 'nodevice'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">No microphone found</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">Check that a microphone is connected, then try again.</p>
                                        </div>
                                    </template>

                                    <template x-if="micPanel === 'nospeech'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">Didn&rsquo;t catch that</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">No speech was detected.</p>
                                            <button type="button" @click="toggleMic()" class="mt-4 px-5 py-2 rounded-full text-white text-xs font-semibold" style="background:#8C5C34;">Try again</button>
                                        </div>
                                    </template>

                                    <template x-if="micPanel === 'unsupported'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">Not supported here</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">Voice search works in Chrome and Edge. You can still type your search.</p>
                                        </div>
                                    </template>

                                    <template x-if="micPanel === 'error'">
                                        <div>
                                            <h3 class="text-base font-semibold" style="color:#2d1810;">Voice search unavailable</h3>
                                            <p class="text-sm mt-1" style="color:#616161;">Something went wrong. Please try again in a moment.</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
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
<!-- Spacer no longer needed: header is now in normal flow (not fixed),
     so the hero sits directly below it with no cream gap. -->
<div id="header-spacer" class="hidden" aria-hidden="true"></div>
<script>
    (function () {
        function syncSpacer() {
            var hdr = document.getElementById('main-header');
            var spc = document.getElementById('header-spacer');
            if (!hdr) return;
            if (spc) spc.style.height = hdr.offsetHeight + 'px';
            // The header is sticky, so it always covers the top of the viewport.
            // Publish its height so bottom-anchored overlays (the chat panel)
            // can stop short of it instead of sliding underneath.
            document.documentElement.style.setProperty('--kk-header-h', hdr.offsetHeight + 'px');
        }
        syncSpacer();
        window.addEventListener('resize', syncSpacer);
    })();
</script>

@guest
{{-- ====================================================
     LOGIN / SIGNUP MODAL - opens via $dispatch('open-login-modal')
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

            {{-- novalidate: the browser's anonymous "Please fill out this field"
                 bubble names no field; submit() shows a message that does. --}}
            <form @submit.prevent="submit()" novalidate>
                {{-- Signup-only: full name --}}
                <div class="kk-loginmodal__group" x-show="mode === 'signup'">
                    <label class="kk-loginmodal__label" for="kk-auth-name">Full Name</label>
                    <input type="text" id="kk-auth-name" class="kk-loginmodal__field"
                           :class="fieldErrors.full_name && 'has-error'"
                           x-model="form.full_name" placeholder="Enter your full name" autocomplete="name">
                    <p class="kk-loginmodal__fielderror" x-show="fieldErrors.full_name" x-text="fieldErrors.full_name" x-cloak></p>
                </div>

                <div class="kk-loginmodal__group">
                    <label class="kk-loginmodal__label" for="kk-auth-email">Email Address</label>
                    <input type="email" id="kk-auth-email" class="kk-loginmodal__field"
                           :class="fieldErrors.email && 'has-error'"
                           x-model="form.email" placeholder="you@example.com" autocomplete="email">
                    <p class="kk-loginmodal__fielderror" x-show="fieldErrors.email" x-text="fieldErrors.email" x-cloak></p>
                </div>

                {{-- Signup-only: phone --}}
                <div class="kk-loginmodal__group" x-show="mode === 'signup'">
                    <label class="kk-loginmodal__label" for="kk-auth-phone">Mobile Number</label>
                    {{-- data-kk-mobile="10" caps the box at ten digits as it is typed
                         (_capMobile in app.js), stripping a pasted +91 rather than
                         truncating it. maxlength stays at 20 so that paste survives
                         the browser long enough to be normalised. --}}
                    <input type="tel" id="kk-auth-phone" class="kk-loginmodal__field"
                           :class="fieldErrors.phone && 'has-error'"
                           x-model="form.phone" placeholder="9876543210" autocomplete="tel"
                           inputmode="numeric" maxlength="20" data-kk-mobile="10">
                    <p class="kk-loginmodal__fielderror" x-show="fieldErrors.phone" x-text="fieldErrors.phone" x-cloak></p>
                </div>

                <div class="kk-loginmodal__group">
                    <label class="kk-loginmodal__label" for="kk-auth-password">Password</label>
                    <div class="kk-loginmodal__inputwrap">
                        <input :type="showPassword ? 'text' : 'password'" id="kk-auth-password"
                               class="kk-loginmodal__field kk-loginmodal__field--haseye"
                               :class="fieldErrors.password && 'has-error'"
                               x-model="form.password" placeholder="Enter your password"
                               :autocomplete="mode === 'login' ? 'current-password' : 'new-password'">
                        <button type="button" class="kk-loginmodal__eye" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'" tabindex="-1">
                            <svg x-show="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7s-8.27-2.94-9.54-7z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <svg x-show="showPassword" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M13.88 18.83A10.05 10.05 0 0112 19c-4.48 0-8.27-2.94-9.54-7a9.97 9.97 0 011.56-3.03m5.86.91a3 3 0 114.24 4.24M9.88 9.88l4.24 4.24M9.88 9.88L6.59 6.59m7.53 7.53l3.29 3.29M3 3l3.59 3.59m0 0A9.95 9.95 0 0112 5c4.48 0 8.27 2.94 9.54 7a10.03 10.03 0 01-4.13 5.41m0 0L21 21" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <p class="kk-loginmodal__fielderror" x-show="fieldErrors.password" x-text="fieldErrors.password" x-cloak></p>
                </div>

                {{-- Signup-only: confirm password --}}
                <div class="kk-loginmodal__group" x-show="mode === 'signup'">
                    <label class="kk-loginmodal__label" for="kk-auth-password2">Confirm Password</label>
                    <div class="kk-loginmodal__inputwrap">
                        <input :type="showConfirm ? 'text' : 'password'" id="kk-auth-password2"
                               class="kk-loginmodal__field kk-loginmodal__field--haseye"
                               :class="fieldErrors.password_confirmation && 'has-error'"
                               x-model="form.password_confirmation" placeholder="Repeat your password"
                               autocomplete="new-password">
                        <button type="button" class="kk-loginmodal__eye" @click="showConfirm = !showConfirm"
                                :aria-label="showConfirm ? 'Hide password' : 'Show password'" tabindex="-1">
                            <svg x-show="!showConfirm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7s-8.27-2.94-9.54-7z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <svg x-show="showConfirm" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M13.88 18.83A10.05 10.05 0 0112 19c-4.48 0-8.27-2.94-9.54-7a9.97 9.97 0 011.56-3.03m5.86.91a3 3 0 114.24 4.24M9.88 9.88l4.24 4.24M9.88 9.88L6.59 6.59m7.53 7.53l3.29 3.29M3 3l3.59 3.59m0 0A9.95 9.95 0 0112 5c4.48 0 8.27 2.94 9.54 7a10.03 10.03 0 01-4.13 5.41m0 0L21 21" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <p class="kk-loginmodal__fielderror" x-show="fieldErrors.password_confirmation" x-text="fieldErrors.password_confirmation" x-cloak></p>
                </div>

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
                <a href="{{ route('privacy') }}" class="kk-loginmodal__legal-link">Privacy Policy</a>
                <span> &amp; </span>
                <a href="{{ route('terms') }}" class="kk-loginmodal__legal-link">Terms &amp; Service</a>
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
            showPassword: false,
            showConfirm: false,
            fieldErrors: {},
            form: { full_name: '', email: '', phone: '', password: '', password_confirmation: '', remember: false },
            csrf: '{{ csrf_token() }}',
            openModal() { this.open = true; this.error = ''; this.notice = ''; this.fieldErrors = {}; },
            switchMode(m) {
                this.mode = m;
                this.error = '';
                this.notice = '';
                // Errors belong to the form that produced them; carrying them
                // across marks fields the other tab doesn't even show.
                this.fieldErrors = {};
                this.showPassword = false;
                this.showConfirm = false;
            },
            /**
             * Errors are keyed by field so each one renders under the input it
             * belongs to, rather than as a single message at the top that makes
             * the reader work out which box it means.
             */
            validate() {
                const e = {};
                const signup = this.mode === 'signup';

                if (signup) {
                    const name = this.form.full_name.trim();
                    if (!name) {
                        e.full_name = 'Please enter your full name.';
                    } else if ([...name].length > 30) {
                        // The same limit the server holds (RegisterController::NAME_LIMIT)
                        // and the same sentence, so the modal never posts a name the
                        // endpoint is about to hand straight back.
                        e.full_name = 'Please keep your name to 30 characters or fewer.';
                    }
                }

                const email = this.form.email.trim();
                if (!email) {
                    e.email = 'Please enter your email address.';
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
                    e.email = 'That does not look like a valid email address.';
                } else if (signup && !/^[A-Za-z0-9]/.test(email)) {
                    // The two headline checks from App\Rules\EmailAddress, which
                    // registration adds and sign-in deliberately does not: an address
                    // stored before that rule existed still has to be able to log in.
                    e.email = 'An email address must start with a letter or a number.';
                } else if (signup && email.includes('..')) {
                    e.email = 'An email address cannot contain two dots in a row.';
                }

                if (signup) {
                    if (!this.form.phone.trim()) {
                        e.phone = 'Please enter your mobile number.';
                    } else {
                        // Mirrors App\Rules\IndianMobile: strip the decoration and the
                        // +91/0 prefix, then test the ten digits that are left.
                        const digits = this.form.phone.replace(/\D/g, '')
                            .replace(/^0?91(?=[6-9]\d{9}$)/, '')
                            .replace(/^0(?=[6-9]\d{9}$)/, '');
                        if (!/^[6-9]\d{9}$/.test(digits)) {
                            e.phone = 'Please enter a valid 10-digit mobile number starting with 6, 7, 8 or 9.';
                        }
                    }
                }

                if (!this.form.password) {
                    e.password = 'Please enter your password.';
                } else if (signup) {
                    // Mirrors the Password::defaults() policy in AppServiceProvider.
                    // Deliberately no character-set restriction: any non-alphanumeric
                    // counts as the symbol, so '#', '_' and '-' are all fine.
                    const pw = this.form.password;
                    if (pw.length < 8) {
                        e.password = 'Your password must be at least 8 characters long.';
                    } else if (!/[a-z]/.test(pw) || !/[A-Z]/.test(pw)) {
                        e.password = 'Your password must include both an uppercase and a lowercase letter.';
                    } else if (!/\d/.test(pw)) {
                        e.password = 'Your password must include at least one number.';
                    } else if (!/[^A-Za-z0-9]/.test(pw)) {
                        e.password = 'Your password must include at least one special character, such as @ # ! or ?.';
                    }
                }

                if (signup) {
                    if (!this.form.password_confirmation) {
                        e.password_confirmation = 'Please confirm your password.';
                    } else if (this.form.password && this.form.password !== this.form.password_confirmation) {
                        e.password_confirmation = 'The two passwords do not match.';
                    }
                }

                this.fieldErrors = e;
                return Object.keys(e).length === 0;
            },
            async submit() {
                this.error = '';
                if (!this.validate()) return;
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
                        // This modal has no Terms checkbox — consent is the
                        // "By continuing you agree to our Privacy Policy &
                        // Terms" notice above the button, so submitting IS the
                        // acceptance. Send it explicitly: the server requires
                        // the field, and without it signup 422s against an
                        // input this form does not render, which shows nothing.
                        body: JSON.stringify(
                            this.mode === 'signup' ? { ...this.form, terms: true } : this.form
                        )
                    });
                    // The page's CSRF token goes stale once the session expires
                    // (tab left open); a fresh load is the only way to renew it.
                    if (res.status === 419) {
                        this.error = 'Your session expired. Refreshing the page…';
                        setTimeout(() => window.location.reload(), 1200);
                        return;
                    }
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
                        // Laravel keys its errors by field, so they land under the
                        // same inputs the client-side checks use.
                        const mapped = {};
                        for (const [field, messages] of Object.entries(data.errors)) {
                            mapped[field] = Array.isArray(messages) ? messages[0] : messages;
                        }
                        this.fieldErrors = mapped;
                        // A credentials failure is about the pair, not one box.
                        if (this.mode === 'login' && mapped.email) {
                            this.error = mapped.email;
                            this.fieldErrors = {};
                        }

                        // An error for a field this modal does not render (the
                        // server validates more than the modal shows) would map
                        // to nothing and leave the button looking dead. Anything
                        // unplaceable goes in the banner instead.
                        const shown = ['full_name', 'email', 'phone', 'password', 'password_confirmation'];
                        const orphan = Object.keys(mapped).find(f => !shown.includes(f));
                        if (!this.error && orphan) {
                            this.error = mapped[orphan];
                        }
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
    /* The signup tab adds three more fields, so the shell can end up taller than
       a short viewport. The overlay scrolls, and `margin: auto` on the shell
       centres it while there is room but collapses to zero once there is not --
       which is what keeps the top of the form reachable instead of clipped. */
    .kk-loginmodal {
        position: fixed; inset: 0; z-index: 60;
        display: flex; align-items: flex-start; justify-content: center;
        padding: 16px;
        overflow-y: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain;
    }
    /* Fixed, not absolute: an absolute overlay is sized to the scroll container
       and would slide off the backdrop as soon as the modal is scrolled. */
    .kk-loginmodal__overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); }
    .kk-loginmodal__shell {
        position: relative;
        margin: auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        width: 100%;
        max-width: 720px;
        background: #2d1810;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(0,0,0,0.35);
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
    .kk-loginmodal__group { margin-bottom: 12px; text-align: left; }
    .kk-loginmodal__label {
        display: block; margin-bottom: 5px;
        font-size: 12px; font-weight: 600; color: #2d1810; letter-spacing: 0.01em;
    }
    .kk-loginmodal__inputwrap { position: relative; }
    .kk-loginmodal__field {
        width: 100%; box-sizing: border-box;
        padding: 11px 12px; margin-bottom: 0;
        border: 1px solid #d4d4d4; border-radius: 4px;
        font-size: 14px; color: #2d1810; background: #fff;
        outline: none; transition: border-color 0.15s ease, background 0.15s ease;
    }
    .kk-loginmodal__field--haseye { padding-right: 42px; }
    .kk-loginmodal__field:focus { border-color: #2d1810; }
    .kk-loginmodal__field::placeholder { color: #9ca3af; }
    .kk-loginmodal__field.has-error { border-color: #d72c0d; background: #fffafa; }
    .kk-loginmodal__eye {
        position: absolute; top: 0; right: 0; height: 100%;
        width: 40px; display: flex; align-items: center; justify-content: center;
        background: none; border: none; padding: 0; cursor: pointer;
        color: #9ca3af; transition: color 0.15s ease;
    }
    .kk-loginmodal__eye:hover { color: #2d1810; }
    .kk-loginmodal__eye svg { width: 18px; height: 18px; display: block; }
    .kk-loginmodal__fielderror {
        margin: 5px 0 0; font-size: 11.5px; color: #d72c0d; line-height: 1.4;
    }
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

    /* Short viewport, either orientation: trim the vertical rhythm so signup
       fits without scrolling wherever it still can. */
    @media (max-height: 760px) {
        .kk-loginmodal__left { padding: 28px 24px; }
        .kk-loginmodal__logo { height: 78px; }
        .kk-loginmodal__right { padding: 24px 26px; }
        .kk-loginmodal__tabs { margin-bottom: 14px; }
        .kk-loginmodal__group { margin-bottom: 10px; }
        .kk-loginmodal__field { padding: 9px 12px; }
    }
    /* Below the two-panel breakpoint the brand panel stacks on top of the form,
       so it is tightened up rather than left pushing the fields down a screen. */
    @media (max-width: 860px) {
        .kk-loginmodal__shell { grid-template-columns: 1fr; max-width: 420px; }
        .kk-loginmodal__left { padding: 22px 20px 20px; }
        .kk-loginmodal__logo { height: 64px; }
        .kk-loginmodal__welcome { font-size: 16px; }
        .kk-loginmodal__subtitle { font-size: 14px; }
        .kk-loginmodal__right { padding: 24px 20px; }
    }
    /* Stacked *and* short -- the brand panel is decoration and it is the one
       block that can be dropped to buy the form a whole screen of height. */
    @media (max-width: 860px) and (max-height: 720px) {
        .kk-loginmodal__left { display: none; }
    }
    @media (max-width: 420px) {
        .kk-loginmodal { padding: 12px 10px; }
        .kk-loginmodal__right { padding: 22px 16px; }
        .kk-loginmodal__close { top: 8px; right: 8px; width: 28px; height: 28px; }
        .kk-loginmodal__close svg { width: 16px; height: 16px; }
    }
</style>
@endguest
