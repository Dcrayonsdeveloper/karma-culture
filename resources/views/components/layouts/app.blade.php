<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth overflow-x-clip">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- The SEO tab's Meta Title was stored but read by nothing; it is the
         site-wide fallback for pages that do not set their own title. --}}
    <title>{{ $title ?? (\App\Models\Setting::get('meta_title') ?: config('app.name', 'Laravel')) }}</title>

    <!-- SEO Meta Tags -->
    @php
        // Pages contribute meta three ways: @section, a slot, or @push. Collect
        // all three into one string so the fallbacks below can see what a page
        // already set - checking only some of them duplicated the canonical.
        $kkPageMeta = trim(
            ($__env->hasSection('meta') ? $__env->yieldContent('meta') : (string) ($meta ?? ''))
            . $__env->yieldPushContent('meta')
        );
    @endphp
    {!! $kkPageMeta !!}

    @php
        // Global SEO defaults from DB settings (injected unless individual pages override)
        $seoMetaDesc = \App\Models\Setting::get('meta_description');
        $seoKeywords = \App\Models\Setting::get('meta_keywords');
        $seoOgImage  = \App\Models\Setting::get('og_image');
        $twitterSite = \App\Models\Setting::get('twitter_site');
        $gscCode     = preg_replace('/[^A-Za-z0-9_=\-]/', '', (string) \App\Models\Setting::get('google_search_console_verification'));
    @endphp
    {{-- Fill only the gaps: a page that already set one of these keeps it. --}}
    @if($seoMetaDesc && ! str_contains($kkPageMeta, 'name="description"'))
        <meta name="description" content="{{ $seoMetaDesc }}">
    @endif
    @if(! str_contains($kkPageMeta, 'rel="canonical"'))
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
    @if($seoKeywords)
    <meta name="keywords" content="{{ $seoKeywords }}">
    @endif
    @php
        $kkOgFallback = $seoOgImage
            ?: (\App\Models\Setting::get('site_logo')
                ? asset_v('storage/' . \App\Models\Setting::get('site_logo'))
                : asset_v('images/karmaa-kulture-logo.png'));
    @endphp
    @unless(str_contains($kkPageMeta, 'og:image'))
        <meta property="og:image" content="{{ $kkOgFallback }}">
        <meta name="twitter:image" content="{{ $kkOgFallback }}">
    @endunless
    @unless(str_contains($kkPageMeta, 'twitter:card'))
        <meta name="twitter:card" content="summary_large_image">
    @endunless
    @if($twitterSite)
    <meta name="twitter:site" content="{{ $twitterSite }}">
    @endif
    @if($gscCode)
    <meta name="google-site-verification" content="{{ $gscCode }}">
    @endif

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset_v('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset_v('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset_v('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset_v('site.webmanifest') }}">
    <meta name="theme-color" content="#6F9CA2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fredoka:400,500,600,700|poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Critical CSS first to prevent flash -->
    @vite(['resources/css/critical.css'])

    <!-- Main Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $styles ?? '' }}

    {{-- AAA Accessibility: prefers-reduced-motion + focus rings --}}
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
        :focus-visible {
            outline: 2px solid #6F9CA2;
            outline-offset: 2px;
        }
    </style>

    {{-- Analytics are loaded after cookie consent (see cookie-consent component) --}}
</head>
<body class="font-sans antialiased bg-white text-[#222222] overflow-x-clip" style="font-family: 'Poppins', sans-serif;" x-data data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
{{-- Full-bleed sections (the hero and A+ banners) need the viewport width
     *excluding* the scrollbar. 100vw includes it, so a 100%-wide banner
     overhangs by the scrollbar width and this body's overflow-x-clip silently
     cuts that strip off the right edge - the banner looks cropped.
     clientWidth excludes the scrollbar, so this measurement is exact. --}}
<script>
    (function () {
        function kkViewportWidth() {
            document.documentElement.style.setProperty('--kk-vw', document.documentElement.clientWidth + 'px');
        }
        kkViewportWidth();
        window.addEventListener('resize', kkViewportWidth);
        window.addEventListener('orientationchange', kkViewportWidth);
    })();
</script>
    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2" aria-live="polite">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div x-show="true" role="alert"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-8"
                 class="card px-4 py-3 flex items-center gap-3 shadow-lg border"
                 :class="{
                     'bg-success-50 border-success-200 text-success-800': toast.type === 'success',
                     'bg-error-50 border-error-200 text-error-800': toast.type === 'error',
                     'bg-warning-50 border-warning-200 text-warning-800': toast.type === 'warning',
                     'bg-info-50 border-info-200 text-info-800': toast.type === 'info'
                 }">
                <span x-text="toast.message"></span>
                <button @click="$store.toast.remove(toast.id)" class="text-current opacity-60 hover:opacity-100" aria-label="Dismiss notification">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Auth Login/Signup Modal -->
    @guest
    {{-- items-start + overflow-y-auto (with my-auto on the panel below): the
         register form outgrows a short viewport, and a centred non-scrolling
         overlay would clip its top out of reach. --}}
    <div x-show="$store.authModal.isOpen" x-cloak
         class="fixed inset-0 z-60 flex items-start justify-center p-4 overflow-y-auto overscroll-contain"
         @keydown.escape.window="$store.authModal.close()">

        {{-- Backdrop --}}
        <div x-show="$store.authModal.isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$store.authModal.close()"
             class="fixed inset-0 bg-black/50"></div>

        {{-- Modal --}}
        <div x-show="$store.authModal.isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative my-auto bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden"
             @click.stop>

            {{-- Close button --}}
            <button @click="$store.authModal.close()" aria-label="Close dialog"
                    class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center text-neutral-600 hover:text-neutral-800 rounded-full hover:bg-neutral-100 transition-colors z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Logo --}}
            <div class="pt-6 pb-2 px-6 text-center">
                <img src="{{ asset_v('images/karmaa-kulture-logo.png') }}" alt="{{ config('app.name', 'Karmaa Kulture') }}" class="h-14 mx-auto mb-3 object-contain">
                <h2 class="text-2xl font-bold text-neutral-900"
                    x-text="$store.authModal.mode === 'login' ? 'Login or Signup' : 'Create Account'"></h2>
            </div>

            {{-- Error message --}}
            <template x-if="$store.authModal.message">
                <div class="mx-6 mt-2 px-3 py-2 bg-error-50 border border-error-200 rounded text-sm text-error-700" x-text="$store.authModal.message"></div>
            </template>

            {{-- LOGIN FORM --}}
            <div x-show="$store.authModal.mode === 'login'"
                 x-data="{ email: '', password: '', remember: false }"
                 class="px-6 py-4">
                <form @submit.prevent="$store.authModal.login(email, password, remember)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address</label>
                        <input type="email" x-model="email" required autofocus
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                               placeholder="you@example.com">
                        <template x-if="$store.authModal.errors.email">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.email[0]"></p>
                        </template>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-sm font-medium text-neutral-700">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs text-[#6F9CA2] hover:text-[#5B878D]">Forgot password?</a>
                        </div>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" x-model="password" required
                                   class="w-full px-3 py-2.5 pr-10 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                                   placeholder="Enter your password">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-600 hover:text-neutral-600">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        <template x-if="$store.authModal.errors.password">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.password[0]"></p>
                        </template>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="remember" class="w-4 h-4 rounded border-neutral-300 text-[#6F9CA2] focus:ring-0">
                        <span class="text-sm text-neutral-600">Keep me signed in</span>
                    </label>

                    <button type="submit"
                            :disabled="$store.authModal.isLoading"
                            class="w-full py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white font-semibold rounded-lg text-sm transition-colors disabled:opacity-50">
                        <span x-show="!$store.authModal.isLoading">CONTINUE</span>
                        <span x-show="$store.authModal.isLoading" x-cloak class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Signing in...
                        </span>
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-neutral-600">
                    New to {{ config('app.name') }}?
                    <button @click="$store.authModal.switchMode('register')" class="font-semibold text-[#6F9CA2] hover:text-[#5B878D]">Create an account</button>
                </p>
            </div>

            {{-- REGISTER FORM --}}
            <div x-show="$store.authModal.mode === 'register'" x-cloak
                 x-data="{ name: '', email: '', password: '', password_confirmation: '' }"
                 class="px-6 py-4">
                <form @submit.prevent="$store.authModal.register(name, email, password, password_confirmation)" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Full Name</label>
                        <input type="text" x-model="name" required
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                               placeholder="Enter your full name">
                        <template x-if="$store.authModal.errors.full_name">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.full_name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address</label>
                        <input type="email" x-model="email" required
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                               placeholder="you@example.com">
                        <template x-if="$store.authModal.errors.email">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.email[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Password</label>
                        <input type="password" x-model="password" required
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                               placeholder="Min 8 characters">
                        <template x-if="$store.authModal.errors.password">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.password[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Confirm Password</label>
                        <input type="password" x-model="password_confirmation" required
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                               placeholder="Repeat password">
                    </div>

                    <button type="submit"
                            :disabled="$store.authModal.isLoading"
                            class="w-full py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white font-semibold rounded-lg text-sm transition-colors disabled:opacity-50">
                        <span x-show="!$store.authModal.isLoading">CREATE ACCOUNT</span>
                        <span x-show="$store.authModal.isLoading" x-cloak class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Creating account...
                        </span>
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-neutral-600">
                    Already have an account?
                    <button @click="$store.authModal.switchMode('login')" class="font-semibold text-[#6F9CA2] hover:text-[#5B878D]">Sign in</button>
                </p>
            </div>

            {{-- Footer --}}
            <div class="px-6 pb-5 pt-2 text-center">
                <p class="text-[11px] text-neutral-600 leading-relaxed">
                    By continuing, I agree to {{ config('app.name') }}'
                    <a href="{{ route('terms') }}" class="text-neutral-600 underline">T&C</a>,
                    <a href="{{ route('privacy') }}" class="text-neutral-600 underline">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>
    @endguest

    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-primary-500 text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>

    <!-- Header -->
    @include('partials.header')

    <!-- Mobile Navigation -->
    @include('partials.mobile-nav')

    <!-- Notify Stock Listener -->
    <div x-data @notify-stock.window="$store.toast.success('We\'ll notify you when this item is back in stock!')"></div>

    <!-- Main Content -->
    <main id="main-content" class="min-h-screen">
        {{ $slot }}
    </main>

    {{-- The assistant only renders once a provider key is saved in
         Admin -> Settings -> Integrations, so clearing the key hides it. --}}
    {{-- Shown to everyone. A guest who opens it is asked to sign in; the
         endpoint stays behind auth either way. --}}
    {{-- Not on /chat itself: the floating panel would sit on top of the
         full-page conversation it links to. --}}
    @php $kkChatbot = \App\Services\AiChatService::isConfigured() && ! request()->routeIs('chat'); @endphp
    @if($kkChatbot)
        <x-chatbot-widget />
    @endif

    <!-- Back to Top Button -->
    <div x-data="{ show: false }"
         x-init="window.addEventListener('scroll', () => { show = window.scrollY > 400 })"
         x-cloak>
        <button x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="fixed {{ $kkChatbot ? 'bottom-36 lg:bottom-24' : 'bottom-20 lg:bottom-6' }} right-4 lg:right-7 z-40 w-10 h-10 bg-kk-brown-dark hover:bg-kk-brown-darker text-white rounded-full shadow-lg flex items-center justify-center transition-colors"
                aria-label="Back to top">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
        </button>
    </div>

    <!-- Cart Drawer -->
    <div x-show="$store.cart.isOpen" x-cloak
         class="fixed inset-0 z-50"
         @keydown.escape.window="$store.cart.isOpen && $store.cart.close()">

        {{-- Backdrop --}}
        <div x-show="$store.cart.isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$store.cart.close()"
             class="absolute inset-0"
             style="background:rgba(0,0,0,0.5);"></div>

        {{-- Drawer Panel --}}
        <div x-show="$store.cart.isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 bottom-0 w-full max-w-md flex flex-col"
             style="background:#fff;box-shadow:-4px 0 24px rgba(0,0,0,0.12);"
             @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-3 shrink-0" style="border-bottom:1px solid #e5e5e5;">
                <h2 class="text-lg font-bold" style="color:#222;">
                    Shopping Cart
                    <span class="text-sm font-normal" style="color:#666;" x-text="'(' + $store.cart.itemCount + ')'"></span>
                </h2>
                <button @click="$store.cart.close()" class="w-8 h-8 flex items-center justify-center rounded-full transition-colors" style="color:#666;" onmouseenter="this.style.background='#f5f5f5'" onmouseleave="this.style.background='transparent'" aria-label="Close cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto">
                {{-- Empty state --}}
                <template x-if="$store.cart.items.length === 0">
                    <div class="flex flex-col items-center justify-center py-16 px-6">
                        <svg class="w-16 h-16 mb-4" style="color:#ccc;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <p class="text-sm" style="color:#666;">Your cart is empty</p>
                    </div>
                </template>

                {{-- Items list --}}
                <div class="px-4 py-2">
                    <template x-for="item in $store.cart.items" :key="item.id">
                        <div class="flex gap-3 py-3" style="border-bottom:1px solid #f0f0f0;">
                            <a :href="'/product/' + item.slug" class="shrink-0">
                                {{-- Contained, not cropped: a half-cut garment in the drawer
                                     leaves the shopper unable to tell what they added. --}}
                                <div class="kk-media kk-media--thumb rounded" style="width:64px;height:64px;background:#f8f8f8;">
                                    <img class="kk-media__fill" :src="item.image" alt="" aria-hidden="true">
                                    <img :src="item.image" :alt="item.product_name" data-fallback="{{ asset_v('images/no-product-image.svg') }}">
                                </div>
                            </a>
                            <div class="flex-1 min-w-0">
                                <a :href="'/product/' + item.slug" class="text-sm font-medium line-clamp-2 block" style="color:#222;" x-text="item.product_name"></a>
                                <template x-if="item.size">
                                    <p class="text-xs mt-0.5" style="color:#777;">Size: <span class="font-medium" style="color:#444;" x-text="item.size"></span></p>
                                </template>
                                <template x-if="item.colour">
                                    <p class="text-xs mt-0.5" style="color:#777;">Colour: <span class="font-medium" style="color:#444;" x-text="item.colour"></span></p>
                                </template>
                                <p class="text-sm font-bold mt-1" style="color:#222;" x-text="formatCurrency(item.price)"></p>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <div class="flex items-center rounded overflow-hidden" style="border:1px solid #ddd;">
                                        <button @click="item.quantity > 1 ? $store.cart.update(item.id, item.quantity - 1) : $store.cart.remove(item.id)"
                                                class="w-7 h-7 flex items-center justify-center text-sm transition-colors" style="color:#555;" onmouseenter="this.style.background='#f5f5f5'" onmouseleave="this.style.background='transparent'">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                        <span class="w-8 h-7 flex items-center justify-center text-xs font-semibold" style="border-left:1px solid #ddd;border-right:1px solid #ddd;background:#fafafa;" x-text="item.quantity"></span>
                                        <button @click="$store.cart.update(item.id, item.quantity + 1)"
                                                class="w-7 h-7 flex items-center justify-center text-sm transition-colors" style="color:#555;" onmouseenter="this.style.background='#f5f5f5'" onmouseleave="this.style.background='transparent'">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>
                                    <button @click="$store.cart.remove(item.id)" class="text-xs transition-colors" style="color:#999;" onmouseenter="this.style.color='#ef4444'" onmouseleave="this.style.color='#999'">Remove</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Recommendations --}}
                <template x-if="$store.cart.recommendations.length > 0">
                    <div class="px-4 py-4" style="border-top:1px solid #e5e5e5;background:#fafafa;">
                        <h3 class="text-sm font-bold mb-3" style="color:#222;">You Might Also Like</h3>
                        <div class="flex gap-3 overflow-x-auto pb-2" style="-webkit-overflow-scrolling:touch;">
                            <template x-for="rec in $store.cart.recommendations" :key="rec.id">
                                <div class="shrink-0" style="width:130px;">
                                    <a :href="rec.url" class="block">
                                        <div class="kk-media kk-media--thumb rounded" style="width:130px;height:130px;background:#f0f0f0;">
                                            <img class="kk-media__fill" :src="rec.image" alt="" aria-hidden="true">
                                            <img :src="rec.image" :alt="rec.name" data-fallback="{{ asset_v('images/no-product-image.svg') }}">
                                        </div>
                                    </a>
                                    <a :href="rec.url" class="text-xs font-medium line-clamp-2 mt-1.5 block" style="color:#222;" x-text="rec.name"></a>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-xs font-bold" style="color:#222;" x-text="formatCurrency(rec.price)"></span>
                                        <template x-if="rec.mrp > rec.price">
                                            <span class="text-[10px] line-through" style="color:#999;" x-text="formatCurrency(rec.mrp)"></span>
                                        </template>
                                    </div>
                                    <button @click="$store.cart.add(rec.id)"
                                            class="w-full mt-1.5 text-xs font-semibold py-1.5 rounded transition-colors"
                                            style="background:#F8931D;color:#fff;"
                                            onmouseenter="this.style.background='#E07E0A'" onmouseleave="this.style.background='#F8931D'">
                                        Add to Cart
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="shrink-0 px-4 py-4" style="border-top:1px solid #e5e5e5;background:#fff;">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium" style="color:#666;">Subtotal</span>
                    <span class="text-lg font-bold" style="color:#222;" x-text="formatCurrency($store.cart.subtotal)"></span>
                </div>
                <p class="text-xs mb-3" style="color:#666;">Shipping & taxes calculated at checkout</p>
                <a href="{{ route('cart.index') }}"
                   class="block w-full text-center py-2.5 text-sm font-semibold rounded-lg mb-2 transition-colors"
                   style="background:#F8931D;color:#fff;"
                   onmouseenter="this.style.background='#E07E0A'" onmouseleave="this.style.background='#F8931D'">
                    VIEW CART & CHECKOUT
                </a>
                <button @click="$store.cart.close()"
                        class="block w-full text-center py-2 text-sm font-medium rounded-lg transition-colors"
                        style="color:#6F9CA2;"
                        onmouseenter="this.style.background='#f0f7f8'" onmouseleave="this.style.background='transparent'">
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    @include('partials.footer')

    {{-- The mobile bottom bar is gone: Home, Categories, Cart, Wishlist and
         Account are all reachable from the hamburger menu and the header
         icons, so it only repeated them over the bottom of every page. --}}

    <script>
        function searchBar() {
            return {
                query: '',
                results: [],
                loading: false,
                showResults: false,
                listening: false,
                micPanel: null,   // waiting | listening | blocked | denied | nodevice | unsupported | error
                recognition: null,
                currentPlaceholder: '',
                placeholders: [
                    'Search for Shirts...',
                    'Search for Polo T-Shirts...',
                    'Search for Oxford Shirts...',
                    'Search for Formal Shirts...',
                    'Search for Linen Shirts...',
                    'Search for T-Shirts...',
                    'Search for Trousers...',
                    'Search for Chinos...',
                ],
                placeholderIndex: 0,
                charIndex: 0,
                isDeleting: false,
                typewriterTimer: null,
                typewriterActive: true,

                init() {
                    this.startTypewriter();
                    // Setup Speech Recognition
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (SpeechRecognition) {
                        this.recognition = new SpeechRecognition();
                        this.recognition.lang = 'en-IN';
                        this.recognition.continuous = false;
                        this.recognition.interimResults = false;
                        this.recognition.onresult = (event) => {
                            const transcript = event.results[0][0].transcript;
                            this.query = transcript;
                            this.listening = false;
                            this.micPanel = null;
                            this.fetchSuggestions();
                            // Auto-submit after voice input
                            this.$nextTick(() => {
                                this.$refs.searchInput.closest('form').submit();
                            });
                        };
                        this.recognition.onerror = (event) => {
                            this.listening = false;
                            if (event.error === 'not-allowed') { this.micPanel = 'blocked'; return; }
                            if (event.error === 'no-speech') { this.micPanel = 'nospeech'; return; }
                            if (event.error === 'aborted') { this.micPanel = null; return; }
                            this.micPanel = 'error';
                        };
                        this.recognition.onend = () => {
                            this.listening = false;
                            if (this.micPanel === 'listening') { this.micPanel = null; }
                        };
                    }
                },

                startTypewriter() {
                    this.typewriterActive = true;
                    this.charIndex = 0;
                    this.isDeleting = false;
                    this.currentPlaceholder = '';
                    this.typewrite();
                },

                stopTypewriter() {
                    this.typewriterActive = false;
                    if (this.typewriterTimer) {
                        clearTimeout(this.typewriterTimer);
                        this.typewriterTimer = null;
                    }
                    this.currentPlaceholder = 'Search products...';
                },

                typewrite() {
                    if (!this.typewriterActive) return;
                    const current = this.placeholders[this.placeholderIndex];

                    if (!this.isDeleting) {
                        this.currentPlaceholder = current.substring(0, this.charIndex + 1);
                        this.charIndex++;
                        if (this.charIndex >= current.length) {
                            // Pause at full text, then start deleting
                            this.typewriterTimer = setTimeout(() => {
                                this.isDeleting = true;
                                this.typewrite();
                            }, 2000);
                            return;
                        }
                        this.typewriterTimer = setTimeout(() => this.typewrite(), 80);
                    } else {
                        this.currentPlaceholder = current.substring(0, this.charIndex - 1);
                        this.charIndex--;
                        if (this.charIndex <= 0) {
                            this.isDeleting = false;
                            this.placeholderIndex = (this.placeholderIndex + 1) % this.placeholders.length;
                            this.typewriterTimer = setTimeout(() => this.typewrite(), 400);
                            return;
                        }
                        this.typewriterTimer = setTimeout(() => this.typewrite(), 40);
                    }
                },

                async fetchSuggestions() {
                    if (this.query.length < 2) {
                        this.results = [];
                        return;
                    }
                    this.loading = true;
                    this.showResults = true;
                    try {
                        const response = await axios.get('/search/suggestions', { params: { q: this.query } });
                        // API returns { suggestions: [...] }; keep old keys as fallbacks
                        const data = response.data || {};
                        this.results = Array.isArray(data) ? data : (data.suggestions || data.results || []);
                    } catch (e) {
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },

                /** Has the site been blocked outright, so no prompt will appear? */
                async micBlocked() {
                    try {
                        const status = await navigator.permissions.query({ name: 'microphone' });
                        return status.state === 'denied';
                    } catch (e) {
                        // Firefox and older Safari cannot query this; assume not blocked.
                        return false;
                    }
                },

                closeMicPanel() {
                    this.micPanel = null;
                    if (this.listening) {
                        this.recognition && this.recognition.stop();
                        this.listening = false;
                    }
                },

                async toggleMic() {
                    if (!this.recognition) {
                        this.micPanel = 'unsupported';
                        return;
                    }

                    if (this.listening) {
                        this.recognition.stop();
                        this.listening = false;
                        this.micPanel = null;
                        return;
                    }

                    // Show the waiting panel first, then ask. The browser prompt
                    // appears over it, so the customer can see what is being asked
                    // for and why - rather than a bare dialog with no context.
                    this.micPanel = 'waiting';

                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        if (await this.micBlocked()) {
                            // Chrome will not prompt again once a site is blocked,
                            // so explain where to undo it instead of waiting.
                            this.micPanel = 'blocked';
                            return;
                        }

                        try {
                            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                            stream.getTracks().forEach((track) => track.stop());
                        } catch (e) {
                            this.listening = false;
                            this.micPanel = (e && e.name === 'NotAllowedError') ? 'denied' : 'nodevice';
                            return;
                        }
                    }

                    this.stopTypewriter();
                    this.query = '';

                    try {
                        this.recognition.start();
                        this.listening = true;
                        this.micPanel = 'listening';
                    } catch (e) {
                        this.listening = false;
                        if (e.message && e.message.includes('already started')) {
                            this.recognition.stop();
                            this.micPanel = null;
                        } else {
                            this.micPanel = 'error';
                        }
                    }
                },

                destroy() {
                    this.stopTypewriter();
                    if (this.recognition && this.listening) {
                        this.recognition.stop();
                    }
                }
            };
        }

    </script>

    {{ $scripts ?? '' }}

    {{-- Broken media never leaves a hole.

         An <img> or <video> that 404s renders as an empty rectangle, so a card
         keeps its slot in the grid while showing nothing at all - that is how
         blank tiles appeared in the category rows and the qualities slider.
         error does not bubble, so this listens in the capture phase, which
         also catches media added after load (carousels, quick view). --}}
    <script>
        document.addEventListener('error', function (e) {
            var el = e.target;
            if (!el || !el.tagName) return;

            // <video> reports failure on its <source> child, not on itself.
            if (el.tagName === 'SOURCE') el = el.parentNode;
            if (!el || (el.tagName !== 'IMG' && el.tagName !== 'VIDEO')) return;

            // The blurred backdrop is decoration. If it cannot load, drop it
            // and let the subject decide whether the frame is really broken.
            if (el.classList && el.classList.contains('kk-media__fill')) {
                el.remove();
                return;
            }

            var frame = el.closest ? el.closest('.kk-media') : null;

            // Try the declared placeholder once, then give up - a fallback that
            // 404s too would otherwise re-fire this handler forever.
            var fb = el.getAttribute && el.getAttribute('data-fallback');
            if (fb && !el.hasAttribute('data-fallback-tried')) {
                el.setAttribute('data-fallback-tried', '');
                el.src = fb;
                if (frame) {
                    var fill = frame.querySelector('.kk-media__fill');
                    if (fill) fill.src = fb;
                }
                return;
            }

            if (frame) frame.classList.add('is-broken');
        }, true);
    </script>

    {{-- Pages that @push('scripts') rendered nothing without this, and the
         failure is silent: the markup ships, the JS behind it never does. --}}
    @stack('scripts')

    <!-- WhatsApp Chat Button -->
    @php $waNumber = \App\Models\Setting::get('whatsapp_number', ''); @endphp
    @if($waNumber)
    <a href="https://wa.me/{{ preg_replace('/\D/', '', $waNumber) }}?text={{ urlencode('Hi! I need help with my order.') }}"
       target="_blank" rel="noopener"
       title="Chat on WhatsApp"
       style="position:fixed;bottom:84px;right:16px;z-index:45;width:48px;height:48px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);transition:transform 0.2s;"
       onmouseenter="this.style.transform='scale(1.1)'" onmouseleave="this.style.transform='scale(1)'">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
    @endif

    <!-- Cookie Consent -->
    <x-cookie-consent />

    <!-- Exit-intent / abandoned-cart popup (once per visitor) -->
    @include('partials.exit-popup')

</body>
</html>
