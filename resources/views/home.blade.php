<x-layouts.app>
    <x-slot name="title">{{ $siteSettings['site_name'] }} - {{ $siteSettings['site_tagline'] }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $siteSettings['site_tagline'] }} - Shop kids' clothing, dresses, and accessories online at {{ $siteSettings['site_name'] }}.">
        <link rel="canonical" href="{{ url('/') }}">
        <meta property="og:title" content="{{ $siteSettings['site_name'] }} - {{ $siteSettings['site_tagline'] }}">
        <meta property="og:description" content="Shop kids' clothing, dresses, and accessories online at {{ $siteSettings['site_name'] }}.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        @if($siteSettings['site_logo'])
        <meta property="og:image" content="{{ asset('images/' . $siteSettings['site_logo']) }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $siteSettings['site_name'] }} - {{ $siteSettings['site_tagline'] }}">
        <meta name="twitter:description" content="Shop kids' clothing, dresses, and accessories online at {{ $siteSettings['site_name'] }}.">

        {{-- Organization + WebSite JSON-LD --}}
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/') . '#organization',
                    'name' => $siteSettings['site_name'],
                    'url' => url('/'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => asset('images/colorlogo.png'),
                    ],
                    'description' => $siteSettings['site_tagline'] . ' - Shop kids\' clothing, dresses, and accessories online.',
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'contactType' => 'customer service',
                        'url' => url('/contact'),
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'name' => $siteSettings['site_name'],
                    'url' => url('/'),
                    'publisher' => ['@id' => url('/') . '#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => url('/products') . '?search={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    <x-slot name="styles">
        <style>
            /* ===== TiberTaber-inspired Design System ===== */
            :root {
                --primary: #6f9ca2;
                --primary-light: rgba(111,156,162,.08);
                --primary-dark: #5B878D;
                --accent: #f8931d;
                --accent-dark: #E07E0A;
                --text-dark: #222222;
                --text-muted: #666;
                --bg-warm: #f8f6f3;
                --card-radius: 12px;
                --btn-radius: 30px;
            }

            /* ===== HERO BANNER SLIDER ===== */
            .hero-banner { position: relative; width: 100%; overflow: hidden; }
            .hero-banner img { width: 100%; height: 470px; object-fit: cover; display: block; }
            .hero-slides { position: relative; height: 470px; }
            .hero-slide { position: absolute; inset: 0; transition: opacity 0.6s ease; display: flex; align-items: center; justify-content: center; }
            .hero-arrow {
                position: absolute; top: 50%; transform: translateY(-50%); z-index: 10;
                width: 42px; height: 42px; border-radius: 50%; border: none; cursor: pointer;
                display: flex; align-items: center; justify-content: center;
                background: var(--primary); color: #fff; transition: all 0.3s;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
            .hero-arrow:hover { background: var(--primary-dark); transform: translateY(-50%) scale(1.08); }
            .hero-arrow--prev { left: 16px; }
            .hero-arrow--next { right: 16px; }
            .hero-dots {
                position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%);
                display: flex; gap: 8px; z-index: 10;
            }
            .hero-dot {
                width: 10px; height: 10px; border-radius: 50%;
                background: rgba(255,255,255,0.5); border: none; cursor: pointer; transition: all 0.3s;
            }
            .hero-dot.active { background: var(--accent); width: 28px; border-radius: 5px; }

            /* ===== SECTION HEADER (Title + View All) ===== */
            .section-header {
                display: flex; align-items: center; justify-content: space-between;
                margin-bottom: 24px; gap: 16px;
            }
            .section-title {
                font-family: 'Fredoka', 'Poppins', sans-serif; font-size: 28px; font-weight: 600;
                color: var(--text-dark); line-height: 1.2; margin: 0;
            }
            .view-all-link {
                display: inline-flex; align-items: center; gap: 6px;
                font-size: 14px; font-weight: 500; color: var(--primary);
                text-decoration: none; white-space: nowrap; transition: gap 0.3s;
            }
            .view-all-link:hover { gap: 10px; color: var(--primary-dark); }
            .view-all-link svg { width: 14px; height: 14px; transition: transform 0.3s; }
            .view-all-link:hover svg { transform: translateX(3px); }

            /* ===== PRODUCT SLIDER (Horizontal Scroll) ===== */
            .product-slider {
                display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x mandatory;
                -ms-overflow-style: none; scrollbar-width: none;
                padding: 0 0 4px;
            }
            .product-slider::-webkit-scrollbar { display: none; }
            .product-slider .slide-item {
                flex-shrink: 0; scroll-snap-align: start;
                width: 190px;
            }

            /* ===== "WHY CHOOSE US" FEATURE GRID ===== */
            .features-section { padding: 60px 0; }
            .features-header {
                display: flex; align-items: flex-start; justify-content: space-between;
                margin-bottom: 32px; gap: 20px;
            }
            .features-heading {
                font-family: 'Fredoka', 'Poppins', sans-serif; font-size: 32px; font-weight: 600;
                color: var(--text-dark); line-height: 1.2; max-width: 400px;
            }
            .features-grid {
                display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
            }
            .feature-card {
                text-align: center; padding: 24px 16px;
                background: var(--primary-light); border-radius: var(--card-radius);
                transition: transform 0.3s, box-shadow 0.3s;
            }
            .feature-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
            .feature-icon {
                width: 64px; height: 64px; margin: 0 auto 16px;
                display: flex; align-items: center; justify-content: center;
                background: var(--primary); border-radius: 50%; color: #fff;
            }
            .feature-card h3 {
                font-size: 15px; font-weight: 600; color: var(--text-dark);
                margin: 0 0 4px; text-transform: capitalize;
            }
            .feature-card p {
                font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.5;
            }
            .feature-hero {
                grid-column: span 3; border-radius: var(--card-radius); overflow: hidden;
                max-height: 260px;
            }
            .feature-hero img { width: 100%; height: 100%; object-fit: cover; display: block; }

            /* ===== COLLECTION LIST (Shop For Boys/Girls) ===== */
            /* ===== COLLAGE COLLECTION ===== */
            .collage-collection { margin-bottom: 60px; }
            .collage-collection__top {
                display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;
            }
            .collage-collection__banner {
                position: relative; border-radius: var(--card-radius); overflow: hidden;
                display: block; text-decoration: none; color: inherit;
                min-height: 320px;
            }
            .collage-collection__banner img {
                width: 100%; height: 100%; object-fit: cover; display: block;
                transition: transform 0.4s;
            }
            .collage-collection__banner:hover img { transform: scale(1.03); }
            .collage-collection__banner-text {
                position: absolute; bottom: 0; left: 0; right: 0;
                padding: 24px; background: linear-gradient(transparent, rgba(0,0,0,0.55));
                color: #fff;
            }
            .collage-collection__banner-text span {
                font-size: 14px; font-weight: 400; opacity: 0.85; display: block;
            }
            .collage-collection__banner-text h2 {
                font-size: 28px; font-weight: 700; margin: 2px 0 0; line-height: 1.1;
            }
            .collage-collection__banner-btn {
                position: absolute; bottom: 20px; right: 20px;
            }
            .collage-collection__btn {
                display: inline-flex; align-items: center; gap: 6px;
                padding: 8px 20px; background: #fff; color: var(--primary);
                border-radius: var(--btn-radius); font-size: 13px; font-weight: 600;
                border: none; cursor: pointer; transition: background 0.2s;
            }
            .collage-collection__btn:hover { background: #f0f0f0; }
            .collage-collection__top-cards {
                display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
            }
            .collage-collection__card {
                display: block; text-decoration: none; color: inherit;
                border-radius: var(--card-radius); overflow: hidden;
                position: relative;
            }
            .collage-collection__card img {
                width: 100%; height: 100%; object-fit: cover; display: block;
                aspect-ratio: 1/1; transition: transform 0.3s;
            }
            .collage-collection__card:hover img { transform: scale(1.05); }
            .collage-collection__card-overlay {
                position: absolute; bottom: 0; left: 0; right: 0;
                height: 50%;
                background: linear-gradient(to top, rgba(0,0,0,0.55) 0%, transparent 100%);
                border-radius: 0 0 var(--card-radius) var(--card-radius);
                pointer-events: none;
            }
            .collage-collection__label {
                position: absolute; bottom: 0; left: 0; right: 0;
                padding: 12px; font-size: 14px; font-weight: 600;
                color: #fff; text-align: center;
                text-shadow: 0 1px 3px rgba(0,0,0,0.3);
                z-index: 1;
            }
            .collage-collection__bottom {
                display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
            }

            /* ===== DEALS SECTION ===== */
            .deals-section { padding: 50px 0; }

            /* ===== TESTIMONIAL SECTION ===== */
            .testimonial-section { padding: 50px 0; }
            .testimonial-layout { display: flex; gap: 20px; align-items: stretch; }
            .testimonial-title-card {
                background: var(--primary); border-radius: var(--card-radius);
                padding: 32px 28px; display: flex; flex-direction: column;
                align-items: center; justify-content: center; text-align: center;
                min-width: 260px; max-width: 260px; flex-shrink: 0;
            }
            .testimonial-title-card h2 {
                font-size: 32px; font-weight: 700; color: #fff; margin: 0 0 8px; line-height: 1.1;
            }
            .testimonial-title-card p { font-size: 14px; color: rgba(255,255,255,0.7); margin: 0; }
            .testimonial-carousel-wrap { flex: 1; overflow: hidden; position: relative; }
            .testimonial-carousel {
                display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x mandatory;
                scrollbar-width: none; -ms-overflow-style: none; padding: 4px 0;
            }
            .testimonial-carousel::-webkit-scrollbar { display: none; }
            .testimonial-card {
                background: var(--primary-light); border-radius: var(--card-radius);
                padding: 24px 20px; display: flex; flex-direction: column;
                min-width: 280px; max-width: 300px; flex-shrink: 0; scroll-snap-align: start;
            }
            .testimonial-stars { color: var(--accent); font-size: 16px; margin-bottom: 12px; letter-spacing: 2px; }
            .testimonial-text {
                font-size: 14px; color: var(--text-dark); line-height: 1.6;
                flex: 1; margin-bottom: 16px;
            }
            .testimonial-author { display: flex; align-items: center; gap: 10px; }
            .testimonial-avatar {
                width: 36px; height: 36px; border-radius: 50%;
                background: var(--primary); color: #fff; display: flex;
                align-items: center; justify-content: center;
                font-size: 14px; font-weight: 600; flex-shrink: 0;
            }
            .testimonial-name { font-size: 13px; font-weight: 600; color: var(--text-dark); }
            .testimonial-label { font-size: 11px; color: var(--text-muted); }

            /* ===== NEWSLETTER ===== */
            .newsletter {
                background: var(--primary); padding: 50px 0; text-align: center;
            }
            .newsletter h2 {
                color: #fff; font-size: 22px; font-weight: 600;
                letter-spacing: 0.03em; margin: 0 0 20px;
            }
            .newsletter-form {
                display: flex; gap: 10px; max-width: 460px; margin: 0 auto;
                justify-content: center; align-items: center;
            }
            .newsletter-input {
                flex: 1; padding: 14px 20px; border: 2px solid rgba(255,255,255,0.3);
                border-radius: var(--btn-radius); background: transparent; color: #fff;
                font-size: 14px; outline: none;
            }
            .newsletter-input::placeholder { color: rgba(255,255,255,0.5); }
            .newsletter-input:focus { border-color: #fff; }
            .newsletter-btn {
                padding: 14px 28px; background: var(--accent); color: #fff;
                border-radius: var(--btn-radius); font-weight: 600; font-size: 14px;
                border: none; cursor: pointer; transition: background 0.2s; white-space: nowrap;
            }
            .newsletter-btn:hover { background: var(--accent-dark); }

            /* ===== RESPONSIVE ===== */

            /* Tablet */
            @media (max-width: 1024px) {
                .hero-slides, .hero-banner img { height: 350px; }
                .section-title { font-size: 24px; }
                .features-heading { font-size: 26px; }
                .features-grid { grid-template-columns: repeat(3, 1fr); gap: 14px; }
                .collage-collection__bottom { grid-template-columns: repeat(4, 1fr); }
                .testimonial-title-card { min-width: 220px; max-width: 220px; padding: 24px 20px; }
                .testimonial-title-card h2 { font-size: 26px; }
                .testimonial-card { min-width: 250px; }
                .product-slider .slide-item { width: 170px; }
            }

            /* Mobile landscape */
            @media (max-width: 767px) {
                .hero-slides { height: auto; aspect-ratio: 16 / 7; }
                .hero-banner img { height: 100%; object-fit: contain; }
                .hero-arrow { width: 32px; height: 32px; }
                .hero-arrow svg { width: 14px; height: 14px; }
                .hero-arrow--prev { left: 8px; }
                .hero-arrow--next { right: 8px; }
                .hero-dots { display: none; }

                .section-header { margin-bottom: 16px; }
                .section-title { font-size: 20px; }
                .view-all-link { font-size: 13px; }

                .product-slider { gap: 12px; }
                .product-slider .slide-item { width: 152px; }

                .features-section { padding: 40px 0; }
                .features-heading { font-size: 22px; max-width: none; }
                .features-header { flex-direction: column; gap: 8px; }
                .features-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
                .feature-hero { grid-column: span 2; max-height: 180px; }
                .feature-icon { width: 48px; height: 48px; }
                .feature-card { padding: 16px 12px; }
                .feature-card h3 { font-size: 13px; }
                .feature-card p { font-size: 12px; }

                .collage-collection { margin-bottom: 40px; }
                .collage-collection__top { grid-template-columns: 1fr; }
                .collage-collection__banner { min-height: 200px; }
                .collage-collection__banner-text h2 { font-size: 22px; }
                .collage-collection__bottom { grid-template-columns: repeat(2, 1fr); }
                .collage-collection__label { font-size: 13px; }

                .deals-section { padding: 30px 0; }

                .testimonial-section { padding: 30px 0; }
                .testimonial-layout { flex-direction: column; }
                .testimonial-title-card { min-width: 100%; max-width: 100%; padding: 20px 16px; }
                .testimonial-title-card h2 { font-size: 24px; }
                .testimonial-card { min-width: 260px; }

                .newsletter { padding: 36px 0; }
                .newsletter h2 { font-size: 18px; }
                .newsletter-form { flex-direction: column; padding: 0 20px; }
                .newsletter-input { max-width: none; }
            }

            /* Small mobile */
            @media (max-width: 480px) {
                .product-slider { gap: 10px; }
                .product-slider .slide-item { width: 140px; }
                .features-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
                .collage-collection__banner { min-height: 160px; }
                .collage-collection__banner-text h2 { font-size: 18px; }
                .collage-collection__top-cards { grid-template-columns: 1fr 1fr; gap: 10px; }
            }

            /* Scrollbar hide utility */
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
            .scrollbar-hide::-webkit-scrollbar { display: none; }
        </style>
    </x-slot>

    {{-- Flash Sale Popup --}}
    @if($flashSale)
        <div x-data="flashSalePopup({{ $flashSale->remaining_time }}, '{{ $flashSale->slug }}')"
             x-show="open" x-cloak
             @keydown.escape.window="dismiss()"
             class="fixed inset-0 z-60 flex items-center justify-center p-4">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="dismiss()" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300 delay-100" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                 class="relative w-full max-w-md overflow-hidden rounded-2xl shadow-2xl" @click.stop>
                <button @click="dismiss()" class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center text-white/80 hover:text-white rounded-full hover:bg-white/10 transition-colors z-10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="relative bg-gradient-to-br from-[#F8931D] via-[#E07E0A] to-[#D47200] px-6 pt-8 pb-6 text-center overflow-hidden">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-white/5 rounded-full"></div>
                    <div class="absolute -bottom-8 -right-8 w-32 h-32 bg-white/5 rounded-full"></div>
                    <div class="relative inline-flex items-center justify-center w-14 h-14 bg-white/15 rounded-full mb-4 ring-4 ring-white/10">
                        <svg class="w-7 h-7 text-yellow-200" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <p class="text-white/80 text-xs font-semibold tracking-widest uppercase mb-1">Limited Time Offer</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight mb-2">{{ $flashSale->name }}</h2>
                    @if($flashSale->description)
                        <p class="text-white/80 text-sm leading-relaxed max-w-xs mx-auto mb-4">{{ Str::limit($flashSale->description, 100) }}</p>
                    @endif
                    <div class="flex items-center justify-center gap-2 sm:gap-3">
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-2 min-w-[60px]">
                            <span class="block text-2xl font-bold text-white tabular-nums" x-text="hours">00</span>
                            <span class="block text-[10px] text-white/70 uppercase tracking-wide">Hours</span>
                        </div>
                        <span class="text-2xl font-bold text-white/50">:</span>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-2 min-w-[60px]">
                            <span class="block text-2xl font-bold text-white tabular-nums" x-text="minutes">00</span>
                            <span class="block text-[10px] text-white/70 uppercase tracking-wide">Mins</span>
                        </div>
                        <span class="text-2xl font-bold text-white/50">:</span>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-2 min-w-[60px]">
                            <span class="block text-2xl font-bold text-white tabular-nums" x-text="seconds">00</span>
                            <span class="block text-[10px] text-white/70 uppercase tracking-wide">Secs</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white px-6 py-5 text-center">
                    <p class="text-xs text-neutral-600 mb-3">
                        <span class="font-semibold text-neutral-700">{{ $flashSale->products_count }} {{ Str::plural('product', $flashSale->products_count) }}</span> on sale
                    </p>
                    <a href="{{ route('products.index') }}?flash_sale={{ $flashSale->slug }}" @click="dismiss()"
                       class="inline-flex items-center justify-center gap-2 w-full py-3 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-bold rounded-xl shadow-lg transition-all hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Shop the Sale Now
                    </a>
                    <button @click="dismiss()" class="mt-2 text-xs text-neutral-600 hover:text-neutral-600 transition-colors">No thanks, maybe later</button>
                </div>
            </div>
        </div>
        <script>
            function flashSalePopup(remainingSeconds, saleSlug) {
                return {
                    open: false, remaining: remainingSeconds, timer: null,
                    get hours() { return String(Math.floor(this.remaining / 3600)).padStart(2, '0'); },
                    get minutes() { return String(Math.floor((this.remaining % 3600) / 60)).padStart(2, '0'); },
                    get seconds() { return String(this.remaining % 60).padStart(2, '0'); },
                    init() {
                        const key = 'flash_sale_dismissed_' + saleSlug;
                        if (sessionStorage.getItem(key)) return;
                        setTimeout(() => { this.open = true; document.body.style.overflow = 'hidden'; }, 1500);
                        this.timer = setInterval(() => {
                            if (this.remaining > 0) { this.remaining--; } else { clearInterval(this.timer); this.dismiss(); }
                        }, 1000);
                    },
                    dismiss() {
                        this.open = false; document.body.style.overflow = '';
                        sessionStorage.setItem('flash_sale_dismissed_' + saleSlug, '1');
                        if (this.timer) clearInterval(this.timer);
                    }
                };
            }
        </script>
    @endif

    <!-- ==========================================
         HERO BANNER SLIDER
         ========================================== -->
    @if($banners->count())
    <section class="hero-banner"
             x-data="{
                current: 0,
                slides: [
                    @foreach($banners as $banner)
                    { img: '{{ $banner->image }}', link: '{{ $banner->link ?? route('products.index') }}' }{{ $loop->last ? '' : ',' }}
                    @endforeach
                ],
                timer: null,
                init() { this.startTimer(); },
                startTimer() { this.timer = setInterval(() => this.next(), 5000); },
                next() { this.current = (this.current + 1) % this.slides.length; },
                prev() { this.current = (this.current - 1 + this.slides.length) % this.slides.length; },
                goTo(i) { this.current = i; clearInterval(this.timer); this.startTimer(); }
             }">
        <div class="hero-slides">
            <template x-for="(slide, index) in slides" :key="index">
                <a :href="slide.link"
                   x-show="current === index"
                   x-transition:enter="transition-opacity ease-out duration-500"
                   x-transition:enter-start="opacity-0"
                   x-transition:enter-end="opacity-100"
                   x-transition:leave="transition-opacity ease-in duration-300"
                   x-transition:leave-start="opacity-100"
                   x-transition:leave-end="opacity-0"
                   class="hero-slide block">
                    <img :src="slide.img" :alt="'{{ $siteSettings['site_name'] }}'">
                </a>
            </template>

            <!-- Dots -->
            <div class="hero-dots">
                <template x-for="(slide, index) in slides" :key="'dot-'+index">
                    <button @click="goTo(index)" class="hero-dot" :class="current === index ? 'active' : ''"></button>
                </template>
            </div>
        </div>
    </section>
    @endif


    <!-- ==========================================
         FEATURED PRODUCTS - Horizontal Slider
         ========================================== -->
    @if($featuredProducts->count() && (!isset($sections['featured']) || $sections['featured']->is_active))
        <section class="py-8 lg:py-12 bg-white">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">{{ $sections['featured']->title ?? 'New Arrivals' }}</h2>
                    <a href="{{ route('products.index') }}" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="product-slider">
                    @foreach($featuredProducts->take(10) as $product)
                        <div class="slide-item">
                            <x-product-card :product="$product" :compact="true" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         CATEGORY COLLECTIONS (Collage Style)
         ========================================== -->
    @if($categories->count() && (!isset($sections['categories']) || $sections['categories']->is_active))
        @php
            $subcatGradients = [
                'linear-gradient(135deg, #6F9CA2 0%, #5B878D 100%)',
                'linear-gradient(135deg, #F8931D 0%, #E07E0A 100%)',
                'linear-gradient(135deg, #C1539C 0%, #A04080 100%)',
                'linear-gradient(135deg, #6FC2A2 0%, #4DAA85 100%)',
                'linear-gradient(135deg, #7B8CDE 0%, #5A6BC7 100%)',
                'linear-gradient(135deg, #E86F6F 0%, #D04545 100%)',
            ];
        @endphp
        @foreach($categories->take(3) as $rootCategory)
            @php
                $childCats = $rootCategory->children->where('is_active', true)->sortBy('position');
                if ($childCats->count() < 2) continue;
                $topCards = $childCats->take(2);
                $bottomCards = $childCats->slice(2)->take(4);
            @endphp

            <section class="py-6 lg:py-10 bg-white">
                <div class="container mx-auto px-4">
                    <div class="section-header">
                        <h2 class="section-title">{{ $rootCategory->name }}</h2>
                        <a href="{{ route('category.show', $rootCategory) }}" class="view-all-link">
                            View All
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="collage-collection">
                        {{-- TOP ROW: Banner + 2 Cards --}}
                        <div class="collage-collection__top">
                            <a href="{{ route('category.show', $rootCategory) }}" class="collage-collection__banner">
                                @if($rootCategory->image_url)
                                    <img src="{{ asset('storage/' . $rootCategory->image_url) }}" alt="{{ $rootCategory->name }}" loading="lazy">
                                @else
                                    <div class="w-full h-full min-h-[320px] bg-gradient-to-br from-[#6F9CA2]/20 to-[#6F9CA2]/5"></div>
                                @endif
                                <div class="collage-collection__banner-text">
                                    <span>Shop For</span>
                                    <h2>{{ $rootCategory->name }}</h2>
                                </div>
                                <div class="collage-collection__banner-btn">
                                    <button class="collage-collection__btn">View all &rarr;</button>
                                </div>
                            </a>

                            <div class="collage-collection__top-cards">
                                @foreach($topCards as $child)
                                    <a href="{{ route('category.show', $child) }}" class="collage-collection__card">
                                        @if($child->image_url)
                                            <img src="{{ asset('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
                                        @else
                                            <div style="background: {{ $subcatGradients[$loop->index % count($subcatGradients)] }}; width: 100%; aspect-ratio: 1/1; border-radius: var(--card-radius);"></div>
                                        @endif
                                        <div class="collage-collection__card-overlay"></div>
                                        <p class="collage-collection__label">{{ $child->name }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- BOTTOM ROW: Up to 4 Cards --}}
                        @if($bottomCards->count())
                            <div class="collage-collection__bottom">
                                @foreach($bottomCards as $child)
                                    <a href="{{ route('category.show', $child) }}" class="collage-collection__card">
                                        @if($child->image_url)
                                            <img src="{{ asset('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
                                        @else
                                            <div style="background: {{ $subcatGradients[($loop->index + 2) % count($subcatGradients)] }}; width: 100%; aspect-ratio: 1/1; border-radius: var(--card-radius);"></div>
                                        @endif
                                        <div class="collage-collection__card-overlay"></div>
                                        <p class="collage-collection__label">{{ $child->name }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endforeach
    @endif

    <!-- ==========================================
         BESTSELLERS - Horizontal Slider
         ========================================== -->
    @if($bestsellers->count() && (!isset($sections['bestsellers']) || $sections['bestsellers']->is_active))
        <section class="py-8 lg:py-12 bg-[#f8f6f3]">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">{{ $sections['bestsellers']->title ?? 'Bestsellers' }}</h2>
                    <a href="{{ route('bestsellers') }}" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="product-slider">
                    @foreach($bestsellers->take(10) as $product)
                        <div class="slide-item">
                            <x-product-card :product="$product" :compact="true" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         WHY CHOOSE US - Feature Grid
         ========================================== -->
    @if(isset($sections['benefits']) && $sections['benefits']->is_active && is_array($sections['benefits']->content))
    @php $benefitsSection = $sections['benefits']; @endphp
    <section class="features-section bg-white">
        <div class="container mx-auto px-4">
            <div class="features-header">
                <h2 class="features-heading">{{ $benefitsSection->title }}</h2>
                @if($benefitsSection->button_text)
                    <a href="{{ $benefitsSection->button_link ?? route('products.index') }}" class="view-all-link">
                        {{ $benefitsSection->button_text }}
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
            <div class="features-grid">
                @foreach($benefitsSection->content as $benefit)
                    <div class="feature-card">
                        <div class="feature-icon">
                            @include('partials.benefit-icon', ['icon' => $benefit['icon'] ?? 'default'])
                        </div>
                        <h3>{{ $benefit['title'] }}</h3>
                        <p>{{ $benefit['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- ==========================================
         TODAY'S DEALS
         ========================================== -->
    @if($deals->count() && (!isset($sections['deals']) || $sections['deals']->is_active))
        <section class="deals-section bg-[#f8f6f3]">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">{{ $sections['deals']->title ?? "Steal Deals" }}</h2>
                    <a href="{{ route('products.index') }}?on_sale=1" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="product-slider">
                    @foreach($deals->take(12) as $product)
                        <div class="slide-item">
                            <x-product-card :product="$product" :compact="true" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         PROMO BANNER (CTA)
         ========================================== -->
    @if(isset($sections['promo_banner']) && $sections['promo_banner']->is_active)
        @php $promo = $sections['promo_banner']; @endphp
        <section class="relative overflow-hidden" style="background-color: {{ $promo->background_color ?? '#6F9CA2' }};">
            @if($promo->image_url)
                <img src="{{ asset('storage/' . $promo->image_url) }}" alt="{{ $promo->title }}" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/40"></div>
            @endif
            <div class="container mx-auto px-4 relative z-10 py-14 lg:py-20 text-center">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white mb-3" style="color: {{ $promo->text_color ?? '#ffffff' }};">{{ $promo->title }}</h2>
                @if($promo->subtitle)
                    <p class="text-base sm:text-lg mb-6 max-w-xl mx-auto" style="color: {{ $promo->text_color ?? '#ffffff' }}; opacity: 0.85;">{{ $promo->subtitle }}</p>
                @endif
                @if($promo->button_text)
                    <a href="{{ $promo->button_link ?? route('products.index') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-white text-[#6F9CA2] rounded-full font-semibold text-sm hover:bg-neutral-100 transition-colors shadow-lg">
                        {{ $promo->button_text }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        </section>
    @endif

    <!-- ==========================================
         HAPPY CUSTOMERS / TESTIMONIALS
         ========================================== -->
    @if($testimonials->count() && (!isset($sections['testimonials']) || $sections['testimonials']->is_active))
        <section class="testimonial-section bg-white">
            <div class="container mx-auto px-4">
                <div class="testimonial-layout">
                    {{-- Static Title Card --}}
                    <div class="testimonial-title-card">
                        <h2>{{ $sections['testimonials']->title ?? 'Happy Parents' }}</h2>
                        <p>{{ $sections['testimonials']->subtitle ?? ($testimonials->count() . '+ reviews from happy families') }}</p>
                    </div>

                    {{-- Scrollable Testimonial Carousel --}}
                    <div class="testimonial-carousel-wrap">
                        <div class="testimonial-carousel">
                            @foreach($testimonials as $testimonial)
                                <div class="testimonial-card">
                                    <div class="testimonial-stars">★★★★★</div>
                                    <p class="testimonial-text">"{{ Str::limit($testimonial->content, 120) }}"</p>
                                    <div class="testimonial-author">
                                        @if($testimonial->avatar_url)
                                            <img src="{{ asset('storage/' . $testimonial->avatar_url) }}" alt="{{ $testimonial->name }}" class="w-9 h-9 rounded-full object-cover">
                                        @else
                                            <div class="testimonial-avatar">{{ strtoupper(substr($testimonial->name, 0, 1)) }}</div>
                                        @endif
                                        <div>
                                            <div class="testimonial-name">{{ $testimonial->name }}</div>
                                            <div class="testimonial-label">Verified Buyer</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         NEW ARRIVALS GRID
         ========================================== -->
    @if($newArrivals->count() && (!isset($sections['new_arrivals']) || $sections['new_arrivals']->is_active))
        <section class="py-8 lg:py-12 bg-[#f8f6f3]">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">{{ $sections['new_arrivals']->title ?? 'New Arrivals' }}</h2>
                    <a href="{{ route('new-arrivals') }}" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach($newArrivals->take(10) as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div class="text-center mt-8">
                    <a href="{{ route('new-arrivals') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-white text-[#6F9CA2] border border-[#6F9CA2] rounded-full font-medium text-sm hover:bg-[#6F9CA2] hover:text-white transition-colors">
                        View All New Arrivals
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         NEWSLETTER
         ========================================== -->
    @if(!isset($sections['newsletter']) || $sections['newsletter']->is_active)
    <section class="newsletter">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl mx-auto text-center">
                <h2>{{ $sections['newsletter']->title ?? 'Join the ForeverKids Family' }}</h2>
                @if(isset($sections['newsletter']) && $sections['newsletter']->subtitle)
                    <p class="text-white/80 text-sm mb-4">{{ $sections['newsletter']->subtitle }}</p>
                @endif
                <form class="newsletter-form"
                      x-data="{ email: '', loading: false, message: '', success: false }"
                      @submit.prevent="
                          loading = true; message = '';
                          fetch('/newsletter/subscribe', {
                              method: 'POST',
                              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                              body: JSON.stringify({ email, source: 'homepage' })
                          }).then(r => r.json()).then(data => {
                              success = data.success; message = data.message; loading = false;
                              if (data.success) email = '';
                          }).catch(() => { message = 'Something went wrong. Please try again.'; loading = false; })
                      ">
                    <template x-if="message">
                        <p class="w-full text-sm text-center py-2 rounded" :class="success ? 'text-white' : 'text-red-200'" x-text="message"></p>
                    </template>
                    <template x-if="!message">
                        <input type="email" x-model="email" required placeholder="Email Address" class="newsletter-input">
                    </template>
                    <button type="submit" :disabled="loading" x-show="!message" class="newsletter-btn">
                        <span x-text="loading ? 'Subscribing...' : 'Subscribe'">Subscribe</span>
                    </button>
                </form>
            </div>
        </div>
    </section>
    @endif

</x-layouts.app>
