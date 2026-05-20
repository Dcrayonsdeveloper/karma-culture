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
            .kk-eyebrow { font-family: var(--kk-body); font-size: 11px; letter-spacing: 0.32em; text-transform: uppercase; color: var(--kk-tan-dark); font-weight: 600; }
            .kk-section { padding: 56px 0; }
            .kk-section--tight { padding: 32px 0; }
            .kk-section-title { font-family: var(--kk-display); font-size: 28px; line-height: 1.1; color: var(--kk-text); margin: 0; }
            .kk-section-title--lg { font-size: 38px; }
            .kk-view-all { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--kk-brown); text-decoration: none; font-weight: 600; }
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
                grid-auto-rows: 170px;
                gap: 14px;
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
                .kk-bento { grid-auto-rows: 140px; }
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

            /* Shop It Your Way */
            .kk-shop-your-way { background: var(--kk-cream-light); padding: 64px 0; }
            .kk-tab-row { display: inline-flex; padding: 4px; background: var(--kk-cream-lighter); border: 1px solid var(--kk-cream-dark); border-radius: 999px; gap: 4px; }
            .kk-tab { padding: 10px 24px; border-radius: 999px; font-size: 11px; letter-spacing: 0.22em; text-transform: uppercase; font-weight: 600; color: var(--kk-text-muted); background: transparent; border: none; cursor: pointer; transition: all .2s; }
            .kk-tab.is-active { background: var(--kk-brown-dark); color: var(--kk-cream); }
            .kk-tab:hover:not(.is-active) { color: var(--kk-brown); }
            .kk-hanger-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 18px; margin-top: 40px; max-width: 880px; margin-left: auto; margin-right: auto; }
            .kk-hanger-cell { display: flex; flex-direction: column; align-items: center; gap: 14px; }
            .kk-hanger { width: 100%; max-width: 110px; aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; color: var(--kk-brown); }
            .kk-hanger svg { width: 100%; height: 100%; }
            .kk-size-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 56px; padding: 6px 14px; border: 1px solid var(--kk-brown); border-radius: 999px; font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--kk-brown); background: transparent; font-weight: 600; cursor: pointer; transition: all .2s; }
            .kk-size-pill:hover, .kk-size-pill.is-active { background: var(--kk-brown); color: var(--kk-cream); }
            .kk-size-pill small { display: block; font-size: 9px; font-weight: 500; letter-spacing: 0.1em; opacity: .8; margin-top: 2px; }
            @media (max-width: 767px) {
                .kk-hanger-row { grid-template-columns: repeat(3, 1fr); gap: 14px; }
            }

            /* ===== Shop It Your Way — animated panels ===== */
            .kk-syw-stage { position: relative; margin-top: 44px; min-height: 240px; }
            .kk-syw-panel { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
            .kk-syw-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 28px; width: 100%; max-width: 920px; }
            .kk-syw-cell { display: flex; flex-direction: column; align-items: center; gap: 18px; }
            .kk-syw-panel[data-on="true"] .kk-syw-cell {
                animation: kk-rise .55s var(--d, 0ms) cubic-bezier(.22,1,.36,1) both;
            }
            @keyframes kk-rise {
                from { opacity: 0; transform: translateY(24px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            /* ---- Size: shirts ---- */
            .kk-shirt {
                width: 96px; height: 96px;
                display: flex; align-items: center; justify-content: center;
                color: var(--kk-brown);
                transition: transform .4s cubic-bezier(.22,1,.36,1), color .3s;
            }
            .kk-shirt svg { width: 100%; height: 100%; filter: drop-shadow(0 8px 14px rgba(45,24,16,.18)); }
            .kk-syw-cell:hover .kk-shirt {
                transform: translateY(-8px) rotate(-4deg) scale(1.06);
                color: var(--kk-tan-dark);
            }
            .kk-syw-cell.is-active .kk-shirt {
                color: var(--kk-tan-dark);
                animation: kk-sway 2.4s ease-in-out infinite;
            }
            @keyframes kk-sway {
                0%, 100% { transform: rotate(-3deg); }
                50%      { transform: rotate(3deg); }
            }

            /* ---- Price: bundle of notes ---- */
            .kk-notes {
                position: relative; width: 110px; height: 92px;
                transition: transform .35s cubic-bezier(.22,1,.36,1);
            }
            .kk-syw-cell:hover .kk-notes { transform: translateY(-6px); }
            .kk-note {
                position: absolute; left: 50%; top: 50%;
                width: 86px; height: 56px;
                background: var(--c, var(--kk-tan));
                border-radius: 5px;
                border: 1px solid rgba(255,255,255,.35);
                box-shadow: 0 6px 16px rgba(45,24,16,.22);
                transition: transform .55s cubic-bezier(.22,1,.36,1), box-shadow .3s;
                will-change: transform;
            }
            .kk-note::before {
                content: ''; position: absolute; inset: 7px;
                border: 1px dashed rgba(255,255,255,.35); border-radius: 3px;
            }
            .kk-note::after {
                content: '₹'; position: absolute; left: 50%; top: 50%;
                transform: translate(-50%, -50%);
                color: rgba(255,255,255,.85);
                font-family: var(--kk-display);
                font-size: 24px; font-weight: 600;
                text-shadow: 0 2px 4px rgba(0,0,0,.2);
            }
            .kk-note:nth-child(1) { transform: translate(-50%, -50%) translate(-6px, 8px)  rotate(-9deg); z-index: 1; }
            .kk-note:nth-child(2) { transform: translate(-50%, -50%) translate( 0,   0)    rotate( 0deg); z-index: 2; }
            .kk-note:nth-child(3) { transform: translate(-50%, -50%) translate( 6px,-8px) rotate( 9deg); z-index: 3; }
            .kk-syw-cell:hover .kk-note:nth-child(1) { transform: translate(-50%, -50%) translate(-30px, 6px)  rotate(-22deg); }
            .kk-syw-cell:hover .kk-note:nth-child(3) { transform: translate(-50%, -50%) translate( 30px,-6px)  rotate( 22deg); }
            .kk-syw-cell.is-active .kk-notes {
                animation: kk-bob 2.2s ease-in-out infinite;
            }
            @keyframes kk-bob {
                0%, 100% { transform: translateY(0); }
                50%      { transform: translateY(-6px); }
            }

            /* ---- Shade: animated color palette ---- */
            .kk-swatch-wrap {
                position: relative;
                width: 100px; height: 100px;
                border-radius: 50%;
                padding: 5px;
                background: conic-gradient(from 0deg, var(--kk-tan), var(--kk-cream), var(--kk-tan-dark), var(--kk-brown), var(--kk-cream-dark), var(--kk-tan));
                box-shadow: 0 10px 28px rgba(45,24,16,.20);
                animation: kk-spin 9s linear infinite;
            }
            @keyframes kk-spin { to { transform: rotate(360deg); } }
            .kk-swatch {
                width: 100%; height: 100%;
                border-radius: 50%;
                background: var(--c, #fff);
                box-shadow: inset 0 -10px 18px rgba(0,0,0,.18), inset 0 8px 14px rgba(255,255,255,.18);
                animation: kk-spin 9s linear infinite reverse; /* counter so it visually stays still */
                position: relative;
                transition: transform .35s;
            }
            .kk-swatch::after {
                content: '';
                position: absolute; inset: 22%;
                border-radius: 50%;
                background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.5), transparent 55%);
            }
            .kk-syw-cell:hover .kk-swatch { transform: scale(1.08); }
            .kk-syw-cell.is-active .kk-swatch-wrap {
                animation: kk-spin 4s linear infinite, kk-glow 2s ease-in-out infinite;
            }
            @keyframes kk-glow {
                0%, 100% { box-shadow: 0 10px 28px rgba(45,24,16,.20); }
                50%      { box-shadow: 0 10px 36px rgba(184,137,90,.55), 0 0 0 6px rgba(184,137,90,.12); }
            }

            /* Result CTA */
            .kk-syw-result {
                margin-top: 44px;
                display: flex; align-items: center; justify-content: center;
                gap: 18px; flex-wrap: wrap;
            }
            .kk-syw-result .chip {
                display: inline-flex; align-items: center; gap: 6px;
                padding: 6px 14px; background: var(--kk-cream-lighter);
                border: 1px solid var(--kk-cream-dark); border-radius: 999px;
                font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase;
                color: var(--kk-text-muted); font-weight: 600;
            }
            .kk-syw-result .chip b { color: var(--kk-brown); font-weight: 700; }

            @media (max-width: 767px) {
                .kk-syw-grid { grid-template-columns: repeat(3, 1fr); gap: 18px; }
                .kk-syw-stage { min-height: 380px; }
                .kk-shirt, .kk-notes, .kk-swatch-wrap { width: 78px; height: 78px; }
                .kk-notes { height: 70px; }
                .kk-note { width: 66px; height: 44px; }
                .kk-note::after { font-size: 18px; }
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
            .kk-quality { padding: 32px 26px; border-right: 1px solid rgba(239,226,203,.12); border-bottom: 1px solid rgba(239,226,203,.12); text-align: left; }
            .kk-quality .icon { width: 28px; height: 28px; color: var(--kk-cream); margin-bottom: 14px; }
            .kk-quality h4 { font-family: var(--kk-display); font-size: 17px; color: var(--kk-cream); margin: 0 0 8px; }
            .kk-quality p { font-size: 12px; color: rgba(239,226,203,.65); margin: 0; line-height: 1.6; }

            /* Newsletter */
            .kk-newsletter { background: var(--kk-brown-darker); color: var(--kk-cream); padding: 72px 0; text-align: center; }
            .kk-newsletter h2 { font-family: var(--kk-display); font-size: 32px; color: var(--kk-cream); margin: 8px 0 8px; }
            .kk-newsletter p { color: rgba(239,226,203,.65); font-size: 13px; margin-bottom: 28px; }
            .kk-newsletter-form { display: flex; max-width: 480px; margin: 0 auto; background: var(--kk-cream-lighter); border-radius: 999px; padding: 4px; }
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
             SHOP IT YOUR WAY (animated, per-tab visuals)
             ============================================ --}}
        @php
            $kkPrices = [
                ['k' => '<1k',   'label' => 'Under ₹1k', 'tint' => '#c9986a'],
                ['k' => '1k-2k', 'label' => '₹1k – 2k',  'tint' => '#b8895a'],
                ['k' => '2k-3k', 'label' => '₹2k – 3k',  'tint' => '#a07748'],
                ['k' => '3k-5k', 'label' => '₹3k – 5k',  'tint' => '#8c5c34'],
                ['k' => '5k-7k', 'label' => '₹5k – 7k',  'tint' => '#6e4527'],
                ['k' => '7k+',   'label' => '₹7k+',      'tint' => '#4a2d1a'],
            ];
            $kkShades = [
                ['k' => 'cream',    'label' => 'Cream',    'hex' => '#efe2cb'],
                ['k' => 'sand',     'label' => 'Sand',     'hex' => '#d4b896'],
                ['k' => 'tan',      'label' => 'Tan',      'hex' => '#b8895a'],
                ['k' => 'cinnamon', 'label' => 'Cinnamon', 'hex' => '#8c5c34'],
                ['k' => 'cocoa',    'label' => 'Cocoa',    'hex' => '#5a3a22'],
                ['k' => 'espresso', 'label' => 'Espresso', 'hex' => '#2d1810'],
            ];
            $kkSizes = ['S','M','L','XL','XXL','3XL'];
        @endphp
        <section class="kk-shop-your-way"
                 x-data="{ tab: 'size', size: 'M', price: '1k-2k', shade: 'cream' }">
            <div class="container mx-auto px-4 text-center">
                <span class="kk-eyebrow">Shop It Your</span>
                <h2 class="kk-section-title kk-section-title--lg" style="margin-top:6px;">Shop It Your <em class="kk-display" style="font-style:italic; color:var(--kk-tan-dark);">Way</em></h2>
                <p style="color:var(--kk-text-muted); font-size:13px; margin:14px auto 26px; max-width:480px;">Pick a way that suits you — every cut is tailored for a flattering shape.</p>

                <div class="kk-tab-row">
                    <button class="kk-tab" :class="tab==='size' ? 'is-active' : ''" @click="tab='size'">Size</button>
                    <button class="kk-tab" :class="tab==='price' ? 'is-active' : ''" @click="tab='price'">Price</button>
                    <button class="kk-tab" :class="tab==='shade' ? 'is-active' : ''" @click="tab='shade'">Shade</button>
                </div>

                <div class="kk-syw-stage">

                    {{-- ---------- SIZE: t-shirts ---------- --}}
                    <div class="kk-syw-panel" :data-on="tab==='size'"
                         x-show="tab==='size'"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-3"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <div class="kk-syw-grid">
                            @foreach($kkSizes as $i => $sz)
                                <div class="kk-syw-cell" :class="size==='{{ $sz }}' ? 'is-active' : ''" style="--d: {{ $i * 70 }}ms;">
                                    <div class="kk-shirt" aria-hidden="true">
                                        <svg viewBox="0 0 120 120" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M44 18 L24 26 L8 44 L20 60 L32 52 L32 104 L88 104 L88 52 L100 60 L112 44 L96 26 L76 18 C74 30 67 38 60 38 C53 38 46 30 44 18 Z"/>
                                        </svg>
                                    </div>
                                    <button class="kk-size-pill" :class="size==='{{ $sz }}' ? 'is-active' : ''" @click="size='{{ $sz }}'">
                                        {{ $sz }}<small>FIT</small>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ---------- PRICE: bundle of notes ---------- --}}
                    <div class="kk-syw-panel" :data-on="tab==='price'"
                         x-show="tab==='price'"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-3"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <div class="kk-syw-grid">
                            @foreach($kkPrices as $i => $p)
                                <div class="kk-syw-cell" :class="price==='{{ $p['k'] }}' ? 'is-active' : ''" style="--d: {{ $i * 70 }}ms;">
                                    <div class="kk-notes" aria-hidden="true">
                                        <div class="kk-note" style="--c: {{ $p['tint'] }};"></div>
                                        <div class="kk-note" style="--c: {{ $p['tint'] }};"></div>
                                        <div class="kk-note" style="--c: {{ $p['tint'] }};"></div>
                                    </div>
                                    <button class="kk-size-pill" :class="price==='{{ $p['k'] }}' ? 'is-active' : ''" @click="price='{{ $p['k'] }}'">
                                        {{ $p['label'] }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ---------- SHADE: animated color palette ---------- --}}
                    <div class="kk-syw-panel" :data-on="tab==='shade'"
                         x-show="tab==='shade'"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-3"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <div class="kk-syw-grid">
                            @foreach($kkShades as $i => $s)
                                <div class="kk-syw-cell" :class="shade==='{{ $s['k'] }}' ? 'is-active' : ''" style="--d: {{ $i * 70 }}ms;">
                                    <div class="kk-swatch-wrap" aria-hidden="true">
                                        <div class="kk-swatch" style="--c: {{ $s['hex'] }}; background: {{ $s['hex'] }};"></div>
                                    </div>
                                    <button class="kk-size-pill" :class="shade==='{{ $s['k'] }}' ? 'is-active' : ''" @click="shade='{{ $s['k'] }}'">
                                        {{ $s['label'] }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Live selection echo + CTA --}}
                <div class="kk-syw-result">
                    <span class="chip">Size <b x-text="size">M</b></span>
                    <span class="chip">Price <b x-text="price">1k-2k</b></span>
                    <span class="chip">Shade <b x-text="shade">cream</b></span>
                    <a :href="`{{ route('products.index') }}?size=${size}&price=${price}&shade=${shade}`" class="kk-btn-brown">
                        Show me these <span aria-hidden="true">&rarr;</span>
                    </a>
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
                <div class="kk-qualities-grid">
                    @foreach($qualities as $q)
                        <div class="kk-quality">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7L9 18l-5-5"/></svg>
                            <h4>{{ $q['t'] }}</h4>
                            <p>{{ $q['d'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================================
             JOIN THE FAMILY (newsletter)
             ============================================ --}}
        <section class="kk-newsletter">
            <div class="container mx-auto px-4">
                <span class="kk-eyebrow" style="color: var(--kk-tan);">Join The Family</span>
                <h2>Join The Family</h2>
                <p>Sign up for 10% off your first order + early access to drops.</p>
                <form class="kk-newsletter-form"
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
                          }).catch(() => { message = 'Something went wrong.'; loading = false; })
                      ">
                    <input type="email" x-model="email" required placeholder="Enter your email">
                    <button type="submit" :disabled="loading">
                        <span x-text="loading ? 'Submitting...' : 'Sign Up Now'">Sign Up Now</span>
                    </button>
                </form>
                <template x-if="false"></template>
            </div>
        </section>

        {{-- Free shipping strip above footer --}}
        <div style="background: var(--kk-cream); border-top: 1px solid var(--kk-cream-dark); padding: 14px 0; text-align: center;">
            <p style="font-size: 11px; letter-spacing: 0.24em; text-transform: uppercase; color: var(--kk-brown); font-weight: 600; margin: 0;">
                Free Shipping On Orders Above Rs. 1499 &nbsp;|&nbsp; Easy Returns
            </p>
        </div>
    </div>

</x-layouts.app>
