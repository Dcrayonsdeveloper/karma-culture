<x-layouts.app>
    <x-slot name="title">{{ $siteSettings['site_name'] ?? 'Karmaa Kulture' }} - {{ $siteSettings['site_tagline'] ?? 'Premium tailored essentials' }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $siteSettings['site_tagline'] ?? 'Premium tailored essentials' }} - {{ $siteSettings['site_name'] ?? 'Karmaa Kulture' }}.">
        <link rel="canonical" href="{{ url('/') }}">
        <meta property="og:title" content="{{ $siteSettings['site_name'] ?? 'Karmaa Kulture' }} - {{ $siteSettings['site_tagline'] ?? '' }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endpush

    <x-slot name="styles">
        <style>
            :root {
                --kk-cream:        #efe2cb;
                --kk-cream-light:  #f7eedb;
                --kk-cream-lighter:#fbf5e8;
                --kk-cream-dark:   #e3d2b3;
                --kk-tan:          #b8895a;
                --kk-tan-dark:     #8c5c34;
                --kk-brown:        #4a2d1a;
                --kk-brown-dark:   #2d1810;
                --kk-brown-darker: #1f1109;
                --kk-text:         #2d1810;
                --kk-text-muted:   #7a6555;
                --kk-display: 'Playfair Display', Georgia, serif;
                --kk-body:    'Inter', ui-sans-serif, system-ui, sans-serif;
            }

            .kk-home { background: var(--kk-cream); color: var(--kk-text); font-family: var(--kk-body); }
            .kk-display { font-family: var(--kk-display); font-weight: 500; letter-spacing: -0.01em; }
            .kk-eyebrow { font-family: var(--kk-body); font-size: 11px; letter-spacing: 0.32em; text-transform: uppercase; color: var(--kk-tan-dark); font-weight: 700; }
            .kk-section { padding: 56px 0; }
            .kk-section--tight { padding: 32px 0; }
            .kk-section-title { font-family: var(--kk-display); font-size: 28px; line-height: 1.1; color: var(--kk-text); margin: 0; font-weight: 700; }
            .kk-section-title--lg { font-size: 38px; }
            .kk-view-all { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--kk-brown); text-decoration: none; font-weight: 700; }
            .kk-view-all:hover { color: var(--kk-tan-dark); }
            .kk-btn-brown { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 22px; background: var(--kk-brown); color: var(--kk-cream); border-radius: 999px; font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; font-weight: 600; border: none; cursor: pointer; transition: background .2s; text-decoration: none; }
            .kk-btn-brown:hover { background: var(--kk-brown-dark); }
            .kk-btn-cream { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 22px; background: var(--kk-cream-lighter); color: var(--kk-brown); border: 1px solid var(--kk-brown); border-radius: 999px; font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; font-weight: 600; cursor: pointer; transition: all .2s; text-decoration: none; }
            .kk-btn-cream:hover { background: var(--kk-brown); color: var(--kk-cream); }

            /* Hero */
            .kk-hero { position: relative; width: 100%; overflow: hidden; background: var(--kk-cream); }
            .kk-hero-slide { position: relative; width: 100%; aspect-ratio: 21 / 9; overflow: hidden; }
            .kk-hero-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
            @media (max-width: 767px) { .kk-hero-slide { aspect-ratio: 4 / 5; } }

            /* Tile cards (Category / Aesthetics / Occasions) */
            .kk-tile { position: relative; display: block; overflow: hidden; border-radius: 4px; color: var(--kk-cream); text-decoration: none; background: var(--kk-cream-dark); aspect-ratio: 4/5; }
            .kk-tile img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s ease; }
            .kk-tile:hover img { transform: scale(1.04); }
            .kk-tile-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(45,24,16,.72) 0%, rgba(45,24,16,.15) 45%, transparent 70%); }
            .kk-tile-label { position: absolute; left: 0; right: 0; bottom: 18px; text-align: center; }
            .kk-tile-label .pill { display: inline-block; background: var(--kk-brown-dark); color: var(--kk-cream); padding: 8px 22px; border-radius: 999px; font-size: 11px; letter-spacing: 0.28em; text-transform: uppercase; font-weight: 600; }
            .kk-tile-label .kk-tile-pill-lg { padding: 12px 36px; font-size: 13px; letter-spacing: 0.32em; }
            .kk-tile-banner { aspect-ratio: 16/9; }
            .kk-tile-gender { aspect-ratio: 3/4; }
            @media (min-width: 768px) { .kk-tile-gender { aspect-ratio: 4/5; } }

            /* ===== Bento (uneven mosaic grid) ===== */
            .kk-bento {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                grid-auto-rows: 240px;
                gap: 16px;
            }
            .kk-bento-tile { aspect-ratio: auto !important; height: 100%; width: 100%; }

            /* Men's: 4 items — 1 big + 2 medium + 1 wide */
            .kk-bento--mens > :nth-child(1) { grid-column: span 4; grid-row: span 2; }
            .kk-bento--mens > :nth-child(2) { grid-column: span 2; grid-row: span 1; }
            .kk-bento--mens > :nth-child(3) { grid-column: span 2; grid-row: span 1; }
            .kk-bento--mens > :nth-child(4) { grid-column: span 6; grid-row: span 1; }

            /* Women's: 7 items — 1 hero + 2 medium + 3 small + 1 wide */
            .kk-bento--womens > :nth-child(1) { grid-column: span 3; grid-row: span 2; }
            .kk-bento--womens > :nth-child(2) { grid-column: span 3; grid-row: span 1; }
            .kk-bento--womens > :nth-child(3) { grid-column: span 3; grid-row: span 1; }
            .kk-bento--womens > :nth-child(4) { grid-column: span 2; grid-row: span 1; }
            .kk-bento--womens > :nth-child(5) { grid-column: span 2; grid-row: span 1; }
            .kk-bento--womens > :nth-child(6) { grid-column: span 2; grid-row: span 1; }
            .kk-bento--womens > :nth-child(7) { grid-column: span 6; grid-row: span 1; }

            /* Tablet collapse */
            @media (max-width: 1024px) {
                .kk-bento { grid-auto-rows: 190px; }
            }

            /* Mobile collapse — keep some asymmetry but readable */
            @media (max-width: 767px) {
                .kk-bento { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 130px; gap: 10px; }
                .kk-bento--mens > :nth-child(1)   { grid-column: span 2; grid-row: span 2; }
                .kk-bento--mens > :nth-child(2)   { grid-column: span 1; grid-row: span 1; }
                .kk-bento--mens > :nth-child(3)   { grid-column: span 1; grid-row: span 1; }
                .kk-bento--mens > :nth-child(4)   { grid-column: span 2; grid-row: span 1; }

                .kk-bento--womens > :nth-child(1) { grid-column: span 2; grid-row: span 2; }
                .kk-bento--womens > :nth-child(2),
                .kk-bento--womens > :nth-child(3),
                .kk-bento--womens > :nth-child(4),
                .kk-bento--womens > :nth-child(5),
                .kk-bento--womens > :nth-child(6) { grid-column: span 1; grid-row: span 1; }
                .kk-bento--womens > :nth-child(7) { grid-column: span 2; grid-row: span 1; }
            }

            /* ===== Shop It Your Way — Rail of hangers ===== */
            .kk-shop-your-way { background: var(--kk-cream-light); padding: 32px 0 64px; }
            .kk-syw-heading {
                font-family: var(--kk-display);
                font-size: 44px;
                line-height: 1.05;
                color: var(--kk-text);
                margin: 8px 0 14px;
            }
            .kk-syw-heading em { font-style: italic; color: var(--kk-tan-dark); }
            .kk-syw-sub {
                color: var(--kk-text-muted);
                font-size: 14px;
                max-width: 520px;
                margin: 0 auto;
                line-height: 1.6;
            }

            /* Tab row with two-line pills */
            .kk-syw-tabs {
                display: inline-flex;
                padding: 6px;
                background: var(--kk-cream-lighter);
                border: 1px solid var(--kk-cream-dark);
                border-radius: 999px;
                gap: 4px;
                margin-top: 32px;
            }
            .kk-syw-tab {
                padding: 12px 36px;
                border-radius: 999px;
                background: transparent;
                border: none;
                cursor: pointer;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
                transition: background .35s, color .35s;
                color: var(--kk-text-muted);
                min-width: 170px;
            }
            .kk-syw-tab small {
                font-size: 9px;
                letter-spacing: 0.32em;
                text-transform: uppercase;
                font-weight: 600;
                opacity: 0.65;
            }
            .kk-syw-tab span {
                font-size: 16px;
                font-weight: 600;
                font-family: var(--kk-display);
                letter-spacing: 0.01em;
            }
            .kk-syw-tab.is-active { background: var(--kk-brown-dark); color: var(--kk-cream); }
            .kk-syw-tab.is-active small { color: var(--kk-tan); opacity: 1; }
            .kk-syw-tab:hover:not(.is-active) { color: var(--kk-brown); }

            /* Stage + panel */
            .kk-syw-stage { position: relative; margin-top: 64px; min-height: 420px; }
            .kk-syw-panel { position: absolute; inset: 0; display: flex; align-items: flex-start; justify-content: center; }
            .kk-syw-panel[data-on="true"] .kk-rail-cell {
                animation: kk-rise .55s var(--d, 0ms) cubic-bezier(.22,1,.36,1) both;
            }
            @keyframes kk-rise {
                from { opacity: 0; transform: translateY(24px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            /* The visible rail */
            .kk-rail-wrap {
                position: relative;
                padding-top: 14px;
                width: 100%;
                max-width: 980px;
                margin: 0 auto;
            }
            .kk-rail-bar {
                position: absolute;
                top: 38px;
                left: 4%;
                right: 4%;
                height: 5px;
                background: linear-gradient(to bottom, #2d1810, #1f1109);
                border-radius: 4px;
                box-shadow: 0 3px 6px rgba(45,24,16,.30);
                z-index: 0;
            }
            .kk-rail-bar::before, .kk-rail-bar::after {
                content: '';
                position: absolute;
                width: 14px; height: 14px;
                background: #2d1810;
                border-radius: 50%;
                top: -5px;
                box-shadow: 0 2px 4px rgba(0,0,0,.2);
            }
            .kk-rail-bar::before { left: -10px; }
            .kk-rail-bar::after  { right: -10px; }

            /* Cells along the rail */
            .kk-rail-cells {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 8px;
                position: relative;
                z-index: 1;
            }
            .kk-rail-cell {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
                text-decoration: none;
                color: inherit;
            }
            .kk-shirt-hanger {
                width: 100%;
                max-width: 150px;
                transform-origin: top center;
                transition: transform .4s cubic-bezier(.22,1,.36,1), filter .35s;
            }
            .kk-shirt-hanger svg { width: 100%; height: auto; display: block; filter: drop-shadow(0 8px 14px rgba(45,24,16,.18)); }
            .kk-rail-cell:hover .kk-shirt-hanger {
                transform: rotate(-3deg);
                filter: drop-shadow(0 12px 20px rgba(45,24,16,.28));
            }

            /* Labels under each hanger */
            .kk-rail-label {
                font-family: var(--kk-display);
                font-size: 22px;
                font-weight: 600;
                color: var(--kk-text);
                letter-spacing: 0.04em;
                margin-top: 6px;
                transition: color .3s;
            }
            .kk-rail-cell:hover .kk-rail-label { color: var(--kk-tan-dark); }
            .kk-rail-count {
                font-size: 10px;
                letter-spacing: 0.24em;
                text-transform: uppercase;
                color: var(--kk-text-muted);
                font-weight: 500;
                margin-top: -2px;
            }

            @media (max-width: 1024px) {
                .kk-syw-heading { font-size: 36px; }
                .kk-rail-cells { gap: 4px; }
                .kk-shirt-hanger { max-width: 120px; }
            }
            @media (max-width: 767px) {
                .kk-shop-your-way { padding: 24px 0 40px; }
                .kk-syw-heading { font-size: 28px; }
                .kk-syw-tabs { padding: 4px; gap: 2px; margin-top: 24px; }
                .kk-syw-tab { padding: 10px 16px; min-width: 100px; }
                .kk-syw-tab small { font-size: 8px; letter-spacing: 0.22em; }
                .kk-syw-tab span { font-size: 14px; }
                .kk-rail-bar { display: none; }
                .kk-rail-cells { grid-template-columns: repeat(3, 1fr); row-gap: 28px; }
                .kk-shirt-hanger { max-width: 110px; }
                .kk-syw-stage { min-height: 560px; }
                .kk-rail-label { font-size: 18px; }
            }

            /* Product cards */
            .kk-product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
            @media (max-width: 1024px) { .kk-product-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 640px)  { .kk-product-grid { grid-template-columns: 1fr; } }
            .kk-product { background: var(--kk-cream-lighter); border-radius: 6px; overflow: hidden; display: flex; flex-direction: column; }
            .kk-product__media { position: relative; aspect-ratio: 4/5; overflow: hidden; background: var(--kk-cream-dark); }
            .kk-product__media img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s; }
            .kk-product:hover .kk-product__media img { transform: scale(1.03); }
            .kk-product__tag { position: absolute; top: 12px; left: 12px; background: var(--kk-brown-dark); color: var(--kk-cream); padding: 4px 10px; border-radius: 999px; font-size: 9px; letter-spacing: 0.22em; text-transform: uppercase; font-weight: 700; }
            .kk-product__discount { position: absolute; top: 12px; right: 12px; background: var(--kk-tan-dark); color: var(--kk-cream); padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; }
            .kk-product__body { padding: 18px 16px 20px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
            .kk-product__label { font-size: 10px; letter-spacing: 0.22em; text-transform: uppercase; color: var(--kk-text-muted); font-weight: 600; }
            .kk-product__name { font-family: var(--kk-display); font-size: 16px; color: var(--kk-text); line-height: 1.25; margin: 0; }
            .kk-product__price { font-size: 14px; color: var(--kk-text); font-weight: 600; }
            .kk-product__price del { color: var(--kk-text-muted); font-weight: 400; margin-right: 6px; }
            .kk-product__cta { margin-top: 12px; }

            /* About Us */
            .kk-about { background: var(--kk-cream); padding: 80px 0; text-align: center; }
            .kk-about p.intro { max-width: 620px; margin: 18px auto 0; color: var(--kk-text-muted); font-size: 14px; line-height: 1.75; }
            .kk-about-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-top: 48px; }
            @media (max-width: 1024px) { .kk-about-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 640px)  { .kk-about-grid { grid-template-columns: 1fr; } }
            .kk-about-card { background: var(--kk-cream-lighter); border: 1px solid var(--kk-cream-dark); border-radius: 6px; padding: 26px 20px; text-align: center; }
            .kk-about-card .icon { width: 42px; height: 42px; margin: 0 auto 14px; color: var(--kk-brown); display: flex; align-items: center; justify-content: center; }
            .kk-about-card h4 { font-family: var(--kk-display); font-size: 16px; color: var(--kk-text); margin: 0 0 6px; }
            .kk-about-card p { font-size: 12px; color: var(--kk-text-muted); margin: 0; line-height: 1.5; }

            /* Qualities (dark) */
            .kk-qualities { background: var(--kk-brown-dark); color: var(--kk-cream); padding: 80px 0; text-align: center; }
            .kk-qualities h2 { font-family: var(--kk-display); font-size: 38px; color: var(--kk-cream); margin: 12px 0 8px; }
            .kk-qualities p.sub { color: rgba(239,226,203,.7); font-size: 13px; max-width: 520px; margin: 0 auto; }
            .kk-qualities-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; margin-top: 56px; border-top: 1px solid rgba(239,226,203,.12); border-left: 1px solid rgba(239,226,203,.12); }
            @media (max-width: 1024px) { .kk-qualities-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 640px)  { .kk-qualities-grid { grid-template-columns: 1fr; } }
            .kk-quality {
                padding: 32px 26px;
                border-right: 1px solid rgba(239,226,203,.12);
                border-bottom: 1px solid rgba(239,226,203,.12);
                text-align: left;
                opacity: 0;
                transform: translateY(24px);
                transition: opacity 600ms ease-out, transform 600ms cubic-bezier(0.19, 1, 0.22, 1);
                transition-delay: var(--reveal-delay, 0ms);
            }
            .kk-qualities-grid.is-revealed .kk-quality {
                opacity: 1;
                transform: translateY(0);
            }
            .kk-qualities-grid.is-revealed .kk-quality .icon {
                animation: kk-quality-check 700ms ease-out var(--reveal-delay, 0ms) both;
            }
            @keyframes kk-quality-check {
                0%   { transform: scale(0.6) rotate(-12deg); opacity: 0; }
                60%  { transform: scale(1.15) rotate(2deg);  opacity: 1; }
                100% { transform: scale(1)   rotate(0);     opacity: 1; }
            }
            @media (prefers-reduced-motion: reduce) {
                .kk-quality { opacity: 1; transform: none; transition: none; }
                .kk-qualities-grid.is-revealed .kk-quality .icon { animation: none; }
            }
            .kk-quality .icon { width: 28px; height: 28px; color: var(--kk-cream); margin-bottom: 14px; }
            .kk-quality h4 { font-family: var(--kk-display); font-size: 17px; color: var(--kk-cream); margin: 0 0 8px; }
            .kk-quality p { font-size: 12px; color: rgba(239,226,203,.65); margin: 0; line-height: 1.6; }

            /* Newsletter */
            .kk-newsletter { background: var(--kk-cream-light); color: var(--kk-text); padding: 72px 0; text-align: center; }
            .kk-newsletter h2 { font-family: var(--kk-display); font-size: 32px; color: var(--kk-text); margin: 8px 0 8px; }
            .kk-newsletter p { color: var(--kk-text-muted); font-size: 13px; margin-bottom: 28px; }
            .kk-newsletter-form { display: flex; max-width: 480px; margin: 0 auto; background: #fff; border: 1px solid var(--kk-cream-dark); border-radius: 999px; padding: 4px; }
            .kk-newsletter-form input { flex: 1; background: transparent; border: none; padding: 12px 20px; font-size: 14px; color: var(--kk-text); outline: none; }
            .kk-newsletter-form input::placeholder { color: var(--kk-text-muted); }
            .kk-newsletter-form button { background: var(--kk-brown-dark); color: var(--kk-cream); padding: 10px 24px; border-radius: 999px; font-size: 11px; letter-spacing: 0.22em; text-transform: uppercase; font-weight: 700; border: none; cursor: pointer; }
            .kk-newsletter-form button:hover { background: var(--kk-brown); }

            /* Section header (title + view all) shared */
            .kk-section-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 28px; gap: 16px; }
            .kk-section-header .left { display: flex; flex-direction: column; gap: 6px; }
            @media (max-width: 640px) {
                .kk-section { padding: 40px 0; }
                .kk-section-title { font-size: 22px; }
                .kk-section-title--lg { font-size: 28px; }
                .kk-about, .kk-qualities { padding: 48px 0; }
                .kk-qualities h2, .kk-newsletter h2 { font-size: 26px; }
            }
        </style>
    </x-slot>

    {{-- Flash Sale Popup (preserved from original) --}}
    @if($flashSale ?? false)
        <div x-data="flashSalePopup({{ $flashSale->remaining_time }}, '{{ $flashSale->slug }}')"
             x-show="open" x-cloak
             @keydown.escape.window="dismiss()"
             class="fixed inset-0 z-60 flex items-center justify-center p-4">
            <div x-show="open" @click="dismiss()" class="absolute inset-0 bg-kk-brown-darker/70 backdrop-blur-sm"></div>
            <div x-show="open" class="relative w-full max-w-md overflow-hidden rounded-2xl shadow-2xl" @click.stop>
                <button @click="dismiss()" class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center text-kk-cream/80 hover:text-kk-cream rounded-full hover:bg-kk-cream/10 z-10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="relative bg-kk-brown-dark px-6 pt-8 pb-6 text-center text-kk-cream">
                    <p class="text-kk-cream/70 text-[10px] font-semibold tracking-[0.28em] uppercase mb-2">Limited Time Offer</p>
                    <h2 class="kk-display text-2xl mb-2">{{ $flashSale->name }}</h2>
                    @if($flashSale->description)
                        <p class="text-kk-cream/75 text-sm leading-relaxed max-w-xs mx-auto mb-4">{{ Str::limit($flashSale->description, 100) }}</p>
                    @endif
                    <div class="flex items-center justify-center gap-2 sm:gap-3">
                        <div class="bg-kk-cream/10 rounded-xl px-3 py-2 min-w-[60px]">
                            <span class="block text-2xl font-bold text-kk-cream tabular-nums" x-text="hours">00</span>
                            <span class="block text-[10px] text-kk-cream/60 uppercase tracking-wide">Hours</span>
                        </div>
                        <span class="text-2xl font-bold text-kk-cream/40">:</span>
                        <div class="bg-kk-cream/10 rounded-xl px-3 py-2 min-w-[60px]">
                            <span class="block text-2xl font-bold text-kk-cream tabular-nums" x-text="minutes">00</span>
                            <span class="block text-[10px] text-kk-cream/60 uppercase tracking-wide">Mins</span>
                        </div>
                        <span class="text-2xl font-bold text-kk-cream/40">:</span>
                        <div class="bg-kk-cream/10 rounded-xl px-3 py-2 min-w-[60px]">
                            <span class="block text-2xl font-bold text-kk-cream tabular-nums" x-text="seconds">00</span>
                            <span class="block text-[10px] text-kk-cream/60 uppercase tracking-wide">Secs</span>
                        </div>
                    </div>
                </div>
                <div class="bg-kk-cream-lighter px-6 py-5 text-center">
                    <p class="text-xs text-kk-text-muted mb-3">
                        <span class="font-semibold text-kk-brown">{{ $flashSale->products_count }} {{ Str::plural('product', $flashSale->products_count) }}</span> on sale
                    </p>
                    <a href="{{ route('products.index') }}?flash_sale={{ $flashSale->slug }}" @click="dismiss()" class="kk-btn-brown w-full">
                        Shop the Sale Now
                    </a>
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

    <div class="kk-home">

        {{-- ============================================
             HERO BANNER
             ============================================ --}}
        <section class="kk-hero">
            @if(($banners ?? collect())->count())
                <div x-data="{
                        current: 0,
                        slides: [
                            @foreach($banners as $banner)
                            { img: '{{ $banner->image }}', link: '{{ $banner->link ?? route('products.index') }}' }{{ $loop->last ? '' : ',' }}
                            @endforeach
                        ],
                        timer: null,
                        init() { if (this.slides.length > 1) this.timer = setInterval(() => this.next(), 5500); },
                        next() { this.current = (this.current + 1) % this.slides.length; },
                     }" class="relative">
                    <template x-for="(slide, index) in slides" :key="index">
                        <a :href="slide.link" x-show="current === index"
                           x-transition:enter="transition-opacity duration-700"
                           x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                           class="kk-hero-slide">
                            <img :src="slide.img" alt="{{ $siteSettings['site_name'] ?? 'Karmaa Kulture' }}">
                        </a>
                    </template>
                </div>
            @else
                <a href="{{ route('new-arrivals') }}" class="kk-hero-slide block">
                    @if(file_exists(public_path('images/Web_banner_1920_x_766.webp')))
                        <img src="{{ asset('images/Web_banner_1920_x_766.webp') }}" alt="{{ $siteSettings['site_name'] ?? 'Karmaa Kulture' }}">
                    @else
                        <div class="w-full h-full" style="background: linear-gradient(135deg, var(--kk-cream-dark) 0%, var(--kk-tan) 100%);"></div>
                    @endif
                </a>
            @endif
        </section>

        {{-- ============================================
             SHOP BY CATEGORY — bento mosaics per gender
             ============================================ --}}
        @php
            $allCats = ($categories ?? collect());
            $mensRoot   = $allCats->first(fn($c) => str_contains(strtolower($c->slug ?? ''), 'men') && !str_contains(strtolower($c->slug ?? ''), 'women'));
            $womensRoot = $allCats->first(fn($c) => str_contains(strtolower($c->slug ?? ''), 'women'));

            $mensKids   = $mensRoot   ? $mensRoot->children->where('is_active', true)->sortBy('position')->values()->take(4) : collect();
            $womensKids = $womensRoot ? $womensRoot->children->where('is_active', true)->sortBy('position')->values()->take(7) : collect();

            $mensTints   = ['#7a6347', '#5a4a3c', '#3a2a1f', '#8a6f52'];
            $womensTints = ['#947254', '#7a6347', '#6e5238', '#5a4a3c', '#8a6f52', '#3a2a1f', '#4a3320'];
        @endphp

        @if($mensKids->count())
        <section class="kk-section">
            <div class="container mx-auto px-4">
                <div class="kk-section-header">
                    <div class="left">
                        <span class="kk-eyebrow">Shop</span>
                        <h2 class="kk-section-title">Men's</h2>
                    </div>
                    <a href="{{ route('category.show', $mensRoot) }}" class="kk-view-all">View All <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="kk-bento kk-bento--mens">
                    @foreach($mensKids as $i => $child)
                        <a href="{{ route('category.show', $child) }}" class="kk-tile kk-bento-tile">
                            @if($child->image_url)
                                <img src="{{ asset('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
                            @else
                                <div class="w-full h-full" style="background: linear-gradient(135deg, {{ $mensTints[$i % count($mensTints)] }} 0%, var(--kk-brown-dark) 100%);"></div>
                            @endif
                            <div class="kk-tile-overlay"></div>
                            <div class="kk-tile-label"><span class="pill">{{ Str::upper($child->name) }}</span></div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if($womensKids->count())
        <section class="kk-section" style="background: var(--kk-cream-light);">
            <div class="container mx-auto px-4">
                <div class="kk-section-header">
                    <div class="left">
                        <span class="kk-eyebrow">Shop</span>
                        <h2 class="kk-section-title">Women's</h2>
                    </div>
                    <a href="{{ route('category.show', $womensRoot) }}" class="kk-view-all">View All <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="kk-bento kk-bento--womens">
                    @foreach($womensKids as $i => $child)
                        <a href="{{ route('category.show', $child) }}" class="kk-tile kk-bento-tile">
                            @if($child->image_url)
                                <img src="{{ asset('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
                            @else
                                <div class="w-full h-full" style="background: linear-gradient(135deg, {{ $womensTints[$i % count($womensTints)] }} 0%, var(--kk-brown-dark) 100%);"></div>
                            @endif
                            <div class="kk-tile-overlay"></div>
                            <div class="kk-tile-label"><span class="pill">{{ Str::upper($child->name) }}</span></div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ============================================
             SHOP IT YOUR WAY — Rail of hangers per tab
             ============================================ --}}
        @php
            $kkSizeItems = [
                ['label' => 'S',   'shade' => '#f0d9b8', 'count' => '120', 'q' => 'size=S'],
                ['label' => 'M',   'shade' => '#d9b58a', 'count' => '210', 'q' => 'size=M'],
                ['label' => 'L',   'shade' => '#b8895a', 'count' => '185', 'q' => 'size=L'],
                ['label' => 'XL',  'shade' => '#8c5c34', 'count' => '140', 'q' => 'size=XL'],
                ['label' => 'XXL', 'shade' => '#5a3a22', 'count' => '85',  'q' => 'size=XXL'],
                ['label' => '3XL', 'shade' => '#2d1810', 'count' => '42',  'q' => 'size=3XL'],
            ];
            $kkPriceItems = [
                ['label' => 'Under ₹1k', 'shade' => '#c9986a', 'count' => '60',  'q' => 'price_max=1000'],
                ['label' => '₹1k – 2k',  'shade' => '#b8895a', 'count' => '95',  'q' => 'price_min=1000&price_max=2000'],
                ['label' => '₹2k – 3k',  'shade' => '#a07748', 'count' => '145', 'q' => 'price_min=2000&price_max=3000'],
                ['label' => '₹3k – 5k',  'shade' => '#8c5c34', 'count' => '110', 'q' => 'price_min=3000&price_max=5000'],
                ['label' => '₹5k – 7k',  'shade' => '#6e4527', 'count' => '70',  'q' => 'price_min=5000&price_max=7000'],
                ['label' => '₹7k+',      'shade' => '#4a2d1a', 'count' => '50',  'q' => 'price_min=7000'],
            ];
            $kkShadeItems = [
                ['label' => 'Cream',    'shade' => '#efe2cb', 'count' => '88',  'q' => 'shade=cream'],
                ['label' => 'Sand',     'shade' => '#d4b896', 'count' => '120', 'q' => 'shade=sand'],
                ['label' => 'Tan',      'shade' => '#b8895a', 'count' => '95',  'q' => 'shade=tan'],
                ['label' => 'Cinnamon', 'shade' => '#8c5c34', 'count' => '110', 'q' => 'shade=cinnamon'],
                ['label' => 'Cocoa',    'shade' => '#5a3a22', 'count' => '70',  'q' => 'shade=cocoa'],
                ['label' => 'Espresso', 'shade' => '#2d1810', 'count' => '45',  'q' => 'shade=espresso'],
            ];
            $kkTabs = [
                'size'  => ['eyebrow' => 'Find Your Fit',       'title' => 'Size',  'items' => $kkSizeItems],
                'price' => ['eyebrow' => 'Perfectly Portioned', 'title' => 'Price', 'items' => $kkPriceItems],
                'shade' => ['eyebrow' => 'The Dye Lab',         'title' => 'Shade', 'items' => $kkShadeItems],
            ];
        @endphp
        <section class="kk-shop-your-way" x-data="{ tab: 'size' }">
            <div class="container mx-auto px-4 text-center">
                <span class="kk-eyebrow">Curate The Edit</span>
                <h2 class="kk-syw-heading">Shop It Your <em>Way</em></h2>
                <p class="kk-syw-sub">Pick a size off the rail — every cut is tailored for a flattering drape.</p>

                <div class="kk-syw-tabs">
                    @foreach($kkTabs as $tabKey => $tabCfg)
                        <button class="kk-syw-tab"
                                :class="tab==='{{ $tabKey }}' ? 'is-active' : ''"
                                @click="tab='{{ $tabKey }}'">
                            <small>{{ $tabCfg['eyebrow'] }}</small>
                            <span>{{ $tabCfg['title'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="kk-syw-stage">
                    @foreach($kkTabs as $tabKey => $tabCfg)
                        <div class="kk-syw-panel"
                             :data-on="tab==='{{ $tabKey }}'"
                             x-show="tab==='{{ $tabKey }}'"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 translate-y-3"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            <div class="kk-rail-wrap">
                                <div class="kk-rail-bar" aria-hidden="true"></div>
                                <div class="kk-rail-cells">
                                    @foreach($tabCfg['items'] as $i => $item)
                                        <a href="{{ route('products.index') }}?{{ $item['q'] }}"
                                           class="kk-rail-cell"
                                           style="--d: {{ $i * 80 }}ms;">
                                            <div class="kk-shirt-hanger" style="color: {{ $item['shade'] }};">
                                                <svg viewBox="0 0 100 170" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    {{-- Hook --}}
                                                    <path d="M50 4 Q52 4 52 10 C52 14 47 15 47 20 Q49 24 52 24"
                                                          stroke="#3a2a1f" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                                    {{-- Hanger triangle --}}
                                                    <path d="M52 24 L17 51 L83 51"
                                                          stroke="#3a2a1f" stroke-width="2" fill="none" stroke-linejoin="round" stroke-linecap="round"/>
                                                    <line x1="17" y1="51" x2="83" y2="51" stroke="#3a2a1f" stroke-width="2" stroke-linecap="round"/>
                                                    {{-- T-shirt body --}}
                                                    <path d="M30 52 L15 60 L6 78 L20 90 L25 82 L25 156 Q25 162 31 162 L69 162 Q75 162 75 156 L75 82 L80 90 L94 78 L85 60 L70 52 L65 54 Q50 64 35 54 Z"
                                                          fill="currentColor" stroke="rgba(0,0,0,0.10)" stroke-width="1"/>
                                                    {{-- Neckline shadow --}}
                                                    <path d="M38 55 Q50 63 62 55"
                                                          fill="none" stroke="rgba(0,0,0,0.18)" stroke-width="1.2" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                            <div class="kk-rail-label">{{ $item['label'] }}</div>
                                            <div class="kk-rail-count">{{ $item['count'] }} Styles</div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================================
             NEW ARRIVALS
             ============================================ --}}
        @php $arrivals = ($newArrivals ?? collect())->merge($featuredProducts ?? collect())->unique('id')->take(3); @endphp
        @if($arrivals->count())
        <section class="kk-section">
            <div class="container mx-auto px-4">
                <div class="kk-section-header">
                    <h2 class="kk-section-title">New Arrivals</h2>
                    <a href="{{ route('new-arrivals') }}" class="kk-view-all">View All <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="kk-product-grid">
                    @foreach($arrivals as $product)
                        @include('partials.kk-product-card', ['product' => $product, 'tag' => 'Premium'])
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ============================================
             BESTSELLERS
             ============================================ --}}
        @php $bs = ($bestsellers ?? collect())->take(3); @endphp
        @if($bs->count())
        <section class="kk-section" style="background: var(--kk-cream-light);">
            <div class="container mx-auto px-4">
                <div class="kk-section-header">
                    <h2 class="kk-section-title">Bestsellers</h2>
                    <a href="{{ route('bestsellers') }}" class="kk-view-all">View All <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="kk-product-grid">
                    @foreach($bs as $product)
                        @include('partials.kk-product-card', ['product' => $product, 'tag' => 'Bestseller'])
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ============================================
             ABOUT US
             ============================================ --}}
        @php
            $aboutTitle = ($sections['about_us']->title ?? null) ?: 'About Us';
            $aboutText = ($sections['about_us']->content ?? null) ?: 'Trusted by 10k+ consumers across India, we\'ve curated clothing that blends modern tailoring, premium fabrics, and thoughtful detail. Every piece is designed to move with you — from desk to dinner, travel to weekend downtime.';
            $aboutLink = ($sections['about_us']->button_link ?? null) ?: route('about');
        @endphp
        <section class="kk-about">
            <div class="container mx-auto px-4">
                <span class="kk-eyebrow">About Us</span>
                <h2 class="kk-section-title kk-section-title--lg" style="margin-top:8px;">{{ $aboutTitle }}</h2>
                <p class="intro">{{ is_string($aboutText) ? $aboutText : '' }}</p>
                <div style="margin-top:26px;">
                    <a href="{{ $aboutLink }}" class="kk-btn-brown">Read More</a>
                </div>

                @php
                    $aboutCards = [
                        ['t' => 'Precision Tailored Fits', 'd' => 'EU-certified cottons, broad blends and long-staple linens — sourced from mills we know by name.', 'i' => 'fit'],
                        ['t' => 'Functional Detailing',    'd' => 'Seams, buttonholes, and hems hand-inspected. If it doesn\'t pass our table, it doesn\'t ship.', 'i' => 'star'],
                        ['t' => 'Breathable Fabrics',      'd' => 'Pattern grades across six sizes so the drape holds true — from the shoulder line to the hem break.', 'i' => 'leaf'],
                        ['t' => 'Durable Construction',    'd' => 'Pull-resistant garment, regular audits, and transparency reports published twice a year.', 'i' => 'shield'],
                    ];
                @endphp
                <div class="kk-about-grid">
                    @foreach($aboutCards as $c)
                        <div class="kk-about-card">
                            <div class="icon">
                                @switch($c['i'])
                                    @case('fit')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6l9-3 9 3v4l-4 1v10H7V11L3 10V6z"/></svg>
                                        @break
                                    @case('star')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3l2.6 6 6.4.6-5 4.6 1.4 6.4L12 17l-5.4 3.6L8 14.2 3 9.6l6.4-.6L12 3z"/></svg>
                                        @break
                                    @case('leaf')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 19c0-8 5-13 14-13 0 9-5 14-13 14-1 0-1 0-1-1z"/><path d="M5 19l8-8"/></svg>
                                        @break
                                    @default
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/></svg>
                                @endswitch
                            </div>
                            <h4>{{ $c['t'] }}</h4>
                            <p>{{ $c['d'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================================
             OUR QUALITIES (dark)
             ============================================ --}}
        <section class="kk-qualities">
            <div class="container mx-auto px-4">
                <span class="kk-eyebrow" style="color: var(--kk-tan);">What Sets Us Apart</span>
                <h2>Our Qualities</h2>
                <p class="sub">Six pillars every piece is measured against — no shortcuts, no exceptions.</p>

                @php
                    $qualities = [
                        ['t' => 'Premium Fabrics',     'd' => 'BCI-certified cottons, tencel blends and long-staple linens — sourced from mills we know by name.'],
                        ['t' => 'Hand-Finished Detailing','d' => 'Seams, buttonholes and hems hand-inspected. If it doesn\'t pass our table, it doesn\'t ship.'],
                        ['t' => 'Precision Tailoring', 'd' => 'Pattern graded across six sizes so the drape holds true — from the shoulder line to the hem break.'],
                        ['t' => 'Ethical Production',  'd' => 'Fair-wage partners, regular audits, and transparency reports published twice a year.'],
                        ['t' => 'Wash-Tested For Life','d' => 'Every fabric survives 50+ wash cycles in our lab before it makes the cut — colour-fast, shape-true.'],
                        ['t' => 'Lifetime Mend Promise','d' => 'Broken stitch? Lost button? We\'ll fix it on us. Because good clothes deserve long lives.'],
                    ];
                @endphp
                <div class="kk-qualities-grid"
                     x-data="{ revealed: false }"
                     x-intersect.once="revealed = true"
                     :class="revealed && 'is-revealed'">
                    @foreach($qualities as $q)
                        <div class="kk-quality" style="--reveal-delay: {{ $loop->index * 90 }}ms">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7L9 18l-5-5"/></svg>
                            <h4>{{ $q['t'] }}</h4>
                            <p>{{ $q['d'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    </div>

</x-layouts.app>
