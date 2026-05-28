@php $announcement = \App\Models\Setting::get('announcement_text') ?: 'Free Shipping on Orders Above Rs. 999 | Easy Returns'; @endphp

<header id="main-header" x-data="{ visible: true, lastScroll: 0 }"
       x-on:scroll.window="
           let y = window.scrollY;
           if (y < 60) { visible = true }
           else if (y < lastScroll) { visible = true }
           else if (y > lastScroll + 5) { visible = false }
           lastScroll = y;
       "
       class="fixed left-0 right-0 z-40"
       :style="'top:0; transition: transform 0.3s ease; transform: translateY(' + (visible ? '0' : '-100%') + ')'">
    <!-- Announcement Bar -->
    @if($announcement)
    <div class="bg-[#212121] text-white text-center py-2 text-[11px] sm:text-xs font-normal tracking-[0.12em] uppercase">
        {{ $announcement }}
    </div>
    @endif

    <!-- Main Header -->
    <div class="bg-[#FAF5EF] border-b border-[#e8e3da]">
        <div class="container mx-auto px-4 lg:px-12">
            <div class="flex items-center justify-between h-24 py-2">

                <!-- Left: Mobile menu + Desktop Nav -->
                <div class="flex items-center gap-3 lg:gap-0 flex-1">
                    <!-- Mobile menu button -->
                    <button @click="$dispatch('toggle-mobile-nav')" class="lg:hidden p-2.5 -ml-1 text-[#212121] hover:text-[#3E2A1F]" aria-label="Open menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Desktop Navigation (Left side) -->
                    @php
                        $megaRoots = \App\Models\Category::whereNull('parent_id')
                            ->where('is_active', true)
                            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('position'),
                                    'children.children' => fn($q) => $q->where('is_active', true)->orderBy('position')])
                            ->orderBy('position')
                            ->get();
                    @endphp
                    <nav class="hidden lg:flex items-center gap-0" aria-label="Primary">
                        <a href="{{ route('new-arrivals') }}" class="px-2 py-2 text-[15px] text-[#212121] hover:text-[#3E2A1F] font-medium transition-colors tracking-[0.08em] uppercase">New Arrivals</a>

                        {{-- Collections — full-width mega menu on hover --}}
                        @php
                            // Build groups by gender root. Each group has a label + its columns.
                            // Men's: each child = a column. Women's: one column listing all children.
                            $megaGroups = [];
                            foreach ($megaRoots as $root) {
                                $isWomen = \Illuminate\Support\Str::startsWith(strtolower($root->name), 'wom');
                                $cols = [];
                                if ($isWomen) {
                                    $cols[] = ['title' => null, 'titleSlug' => null, 'items' => $root->children];
                                } else {
                                    foreach ($root->children as $cat) {
                                        $cols[] = ['title' => $cat->name, 'titleSlug' => $cat->slug, 'items' => $cat->children];
                                    }
                                }
                                $megaGroups[] = ['name' => $root->name, 'slug' => $root->slug, 'cols' => $cols];
                            }
                        @endphp
                        <div class="relative" x-data="{ mega: false, megaT: null }"
                             @mouseenter="clearTimeout(megaT); mega = true"
                             @mouseleave="megaT = setTimeout(() => mega = false, 220)">
                            <a href="{{ route('categories.index') }}"
                               class="px-2 py-2 text-[15px] text-[#212121] hover:text-[#3E2A1F] font-medium transition-colors tracking-[0.08em] uppercase inline-flex items-center gap-1">
                                Collections
                                <svg class="w-3 h-3 transition-transform" :class="mega ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            </a>
                            <div x-show="mega" x-cloak
                                 @mouseenter="clearTimeout(megaT); mega = true"
                                 @mouseleave="megaT = setTimeout(() => mega = false, 220)"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 :style="'position:fixed;left:0;width:100vw;z-index:50;top:' + ((document.getElementById('main-header')?.offsetHeight) || 128) + 'px'">
                                <div style="background:#FAF5EF;border-top:1px solid #e8e3da;border-bottom:1px solid #e8e3da;box-shadow:0 22px 44px -16px rgba(62,42,31,0.3);">
                                    <div style="width:100%;display:flex;flex-wrap:wrap;gap:40px 64px;padding:38px 48px 44px;">
                                        @foreach($megaGroups as $group)
                                            <div>
                                                {{-- Gender group label --}}
                                                <a href="{{ route('category.show', $group['slug']) }}"
                                                   style="display:block;font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;letter-spacing:0.2em;text-transform:uppercase;color:#5E3A26;margin-bottom:20px;">
                                                    {{ $group['name'] }}
                                                </a>
                                                {{-- Columns for this group --}}
                                                <div style="display:flex;flex-wrap:wrap;gap:24px 44px;">
                                                    @foreach($group['cols'] as $col)
                                                        <div style="min-width:150px;">
                                                            @if($col['title'])
                                                                <a href="{{ route('category.show', $col['titleSlug']) }}"
                                                                   style="display:block;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#1a1a1a;margin-bottom:16px;">
                                                                    {{ $col['title'] }}
                                                                </a>
                                                            @endif
                                                            <div style="display:flex;flex-direction:column;gap:11px;">
                                                                @foreach($col['items'] as $item)
                                                                    <a href="{{ route('category.show', $item->slug) }}"
                                                                       style="font-size:14px;color:#595959;transition:color 0.15s;"
                                                                       onmouseover="this.style.color='#3E2A1F'" onmouseout="this.style.color='#595959'">
                                                                        {{ $item->name }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('bestsellers') }}" class="px-2 py-2 text-[15px] text-[#212121] hover:text-[#3E2A1F] font-medium transition-colors tracking-[0.08em] uppercase">Bestsellers</a>
                        <a href="{{ route('deals') }}" class="px-2 py-2 text-[15px] text-[#3E2A1F] hover:text-[#5E3A26] font-semibold transition-colors tracking-[0.08em] uppercase">Sale</a>
                    </nav>
                </div>

                <!-- Center: Logo -->
                <a href="{{ url('/') }}" class="flex items-center shrink-0 mx-4">
                    @php $siteLogo = \App\Models\Setting::get('site_logo', ''); @endphp
                    @if($siteLogo)
                        <img id="site-logo" src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name') }}" class="h-8 lg:h-12 object-contain">
                    @else
                        <img id="site-logo" src="{{ asset('images/update-logo.png') }}" alt="{{ config('app.name', 'Karmaa Kulture') }}" class="h-20 object-contain">
                    @endif
                </a>
                <style>#site-logo { height: 80px; } @media (max-width: 767px) { #site-logo { height: 56px; } }</style>

                <!-- Right: Nav links + Icons -->
                <div class="flex items-center gap-0.5 lg:gap-1 flex-1 justify-end">

                    <!-- Desktop Navigation (Right side) -->
                    <nav class="hidden lg:flex items-center gap-0 mr-2" aria-label="Secondary">
                        @if(config('app.wholesale_enabled'))
                            <a href="{{ route('wholesale') }}" class="px-2 py-2 text-[15px] text-[#212121] hover:text-[#3E2A1F] font-medium transition-colors tracking-[0.08em] uppercase">Wholesale</a>
                        @endif
                    </nav>

                    <!-- Mobile search icon -->
                    <a href="{{ route('search') }}" class="sm:hidden p-2.5 text-[#212121] hover:text-[#3E2A1F] transition-colors" aria-label="Search">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </a>

                    <!-- Inline Search Bar -->
                    <div class="relative hidden sm:block flex-1 max-w-xs lg:max-w-sm mx-1 lg:mx-3"
                         x-data="searchBar()"
                         @click.outside="showResults = false">
                        <form action="{{ route('search') }}" method="GET" class="relative flex items-center">
                            <svg class="absolute left-3 w-4 h-4 text-[#3E2A1F] pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <label for="header-search" class="sr-only">Search products</label>
                            <input type="text"
                                   id="header-search"
                                   name="q"
                                   x-ref="searchInput"
                                   x-model="query"
                                   @input.debounce.300ms="fetchSuggestions()"
                                   @focus="showResults = true; stopTypewriter()"
                                   @blur="if(!query) startTypewriter()"
                                   @keydown.escape="showResults = false; $refs.searchInput.blur()"
                                   :placeholder="currentPlaceholder"
                                   aria-label="Search products"
                                   class="w-full pl-9 pr-16 py-2 text-sm bg-[#F2E4D2] border border-[#e8e3da] rounded-[3px] placeholder:text-[#5E3A26]/70 hover:border-transparent focus:bg-white focus:border-transparent transition-all"
                                   autocomplete="off">
                            <button x-show="recognition" x-cloak
                                    type="button"
                                    @click.prevent="toggleMic()"
                                    class="absolute right-8 p-1 transition-colors z-10"
                                    :class="listening ? 'text-red-500 animate-pulse' : 'text-neutral-700 hover:text-[#3E2A1F]'"
                                    :title="listening ? 'Stop listening' : 'Voice search'"
                                    aria-label="Voice search">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                    <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                                </svg>
                            </button>
                            <button type="submit" class="absolute right-2 p-1 text-neutral-700 hover:text-[#3E2A1F] transition-colors" aria-label="Search">
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
                             class="absolute left-0 right-0 top-full mt-1 bg-white border border-[#e8e3da] shadow-dropdown z-50 overflow-hidden">
                            <div x-show="results.length > 0" class="max-h-60 overflow-y-auto">
                                <ul class="py-1">
                                    <template x-for="result in results" :key="result.id">
                                        <li>
                                            <a :href="result.url" class="flex items-center gap-2.5 px-3 py-2 hover:bg-[#F2E4D2] transition-colors">
                                                <img :src="result.image" :alt="result.name" class="w-8 h-8 object-cover">
                                                <div class="min-w-0">
                                                    <div class="text-sm text-[#212121] truncate" x-text="result.name"></div>
                                                    <div class="text-xs text-neutral-700" x-text="result.category"></div>
                                                </div>
                                            </a>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            <div x-show="query.length >= 2 && results.length === 0 && !loading" class="px-4 py-4 text-center">
                                <p class="text-sm text-neutral-700">No results found</p>
                            </div>
                        </div>
                    </div>

                    <!-- Wishlist -->
                    <a href="{{ route('wishlist') }}" class="relative p-2 text-[#212121] hover:text-[#3E2A1F] transition-colors hidden sm:flex" aria-label="Wishlist">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span x-cloak
                              x-show="$store.wishlist.count > 0"
                              x-text="$store.wishlist.count"
                              class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-[#3E2A1F] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                        </span>
                    </a>

                    <!-- User account - desktop -->
                    <div class="relative hidden lg:block" x-data="dropdown()">
                        <button @click="toggle()" class="p-2 text-[#212121] hover:text-[#3E2A1F] transition-colors" aria-label="Account">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </button>

                        <div x-cloak x-show="open" x-transition @click.outside="close()" class="absolute right-0 mt-1 w-48 bg-white border border-[#e8e3da] shadow-dropdown z-50 overflow-hidden">
                            @guest
                                <a href="{{ route('login') }}" class="block px-4 py-2.5 text-sm text-[#212121] hover:text-[#3E2A1F] hover:bg-[#F2E4D2]">Login</a>
                                <a href="{{ route('register') }}" class="block px-4 py-2.5 text-sm text-[#212121] hover:text-[#3E2A1F] hover:bg-[#F2E4D2]">Register</a>
                            @else
                                <div class="px-4 py-2.5 border-b border-[#e8e3da]">
                                    <div class="text-sm font-medium text-[#212121]">{{ auth()->user()->full_name }}</div>
                                    <div class="text-xs text-neutral-500">{{ auth()->user()->email }}</div>
                                </div>
                                <a href="{{ route('account.dashboard') }}" class="block px-4 py-2.5 text-sm text-[#212121] hover:text-[#3E2A1F] hover:bg-[#F2E4D2]">Dashboard</a>
                                <a href="{{ route('account.orders.index') }}" class="block px-4 py-2.5 text-sm text-[#212121] hover:text-[#3E2A1F] hover:bg-[#F2E4D2]">My Orders</a>
                                <a href="{{ route('account.profile') }}" class="block px-4 py-2.5 text-sm text-[#212121] hover:text-[#3E2A1F] hover:bg-[#F2E4D2]">Profile Settings</a>
                                @if(auth()->user()->deliveryPartner)
                                    <a href="{{ route('delivery.login') }}" class="block px-4 py-2.5 text-sm text-[#3E2A1F] hover:text-[#5E3A26] font-medium hover:bg-[#F2E4D2]">Delivery Panel</a>
                                @else
                                    <a href="{{ route('account.become-delivery-partner') }}" class="block px-4 py-2.5 text-sm text-[#3E2A1F] hover:text-[#5E3A26] font-medium hover:bg-[#F2E4D2]">Become a Delivery Partner</a>
                                @endif
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-[#212121] hover:text-[#3E2A1F] hover:bg-[#F2E4D2]">Logout</button>
                                </form>
                            @endguest
                        </div>
                    </div>

                    <!-- Cart -->
                    <a href="{{ route('cart.index') }}" class="relative p-2 text-[#212121] hover:text-[#3E2A1F] transition-colors" aria-label="Cart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span x-cloak
                              x-show="$store.cart.itemCount > 0"
                              x-text="$store.cart.itemCount"
                              class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-[#3E2A1F] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Spacer for fixed header + announcement bar -->
<div id="header-spacer"
     class="{{ $announcement ? 'h-32' : 'h-24' }}"
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
