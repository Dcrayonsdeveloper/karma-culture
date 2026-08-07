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
            /* Consecutive sections stack their paddings, so each side stays
               small — the visible gap between two sections is roughly double
               these values. */
            .kk-section { padding: 24px 0; }
            .kk-section--tight { padding: 16px 0; }
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
            .kk-hero-slide { position: relative; width: 100%; aspect-ratio: 16 / 9; overflow: hidden; }
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

            /* ===== Category grid — uniform equal-size cards (Men's) ===== */
            .kk-catgrid { position: relative; }
            .kk-catgrid__track {
                display: flex;
                gap: 18px;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                padding: 8px 2px 14px;                 /* room so hover-lift/shadow isn't clipped */
                scrollbar-width: none;                 /* hide bar — navigate via arrows / swipe */
            }
            .kk-catgrid__track::-webkit-scrollbar { display: none; }
            .kk-catgrid .kk-tile {
                flex: 0 0 calc((100% - 3 * 18px) / 4); /* 4 cards per view on desktop */
                scroll-snap-align: start;
                aspect-ratio: 4 / 5;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(45, 24, 16, 0.08);
                transition: transform .35s cubic-bezier(.2,.7,.2,1), box-shadow .35s ease;
            }
            @media (max-width: 1024px) { .kk-catgrid .kk-tile { flex-basis: calc((100% - 2 * 18px) / 3); } }  /* 3 per view */
            @media (max-width: 767px)  { .kk-catgrid__track { gap: 12px; } .kk-catgrid .kk-tile { flex-basis: calc((100% - 12px) / 2); } }  /* 2 per view */

            /* Prev / next arrows */
            .kk-catgrid__nav {
                position: absolute; top: 50%; transform: translateY(-50%);
                width: 44px; height: 44px; border-radius: 50%;
                background: var(--kk-cream-lighter); border: 1px solid var(--kk-cream-dark);
                color: var(--kk-brown); display: flex; align-items: center; justify-content: center;
                cursor: pointer; z-index: 3; box-shadow: 0 4px 14px rgba(45, 24, 16, 0.16);
                transition: background .2s ease, color .2s ease, opacity .2s ease;
            }
            .kk-catgrid__nav:hover { background: var(--kk-brown); color: var(--kk-cream); }
            .kk-catgrid__nav--prev { left: -10px; }
            .kk-catgrid__nav--next { right: -10px; }
            .kk-catgrid__nav.is-disabled { opacity: 0; pointer-events: none; }
            .kk-catgrid__nav svg { width: 18px; height: 18px; }
            @media (max-width: 767px) { .kk-catgrid__nav { display: none; } }   /* mobile: swipe */
            .kk-catgrid .kk-tile:hover {
                transform: translateY(-5px);
                box-shadow: 0 18px 38px rgba(45, 24, 16, 0.20);
            }
            .kk-catgrid .kk-tile video {
                width: 100%; height: 100%;
                object-fit: cover; display: block;
                transition: transform .5s ease;
            }
            .kk-catgrid .kk-tile:hover video { transform: scale(1.04); }
            .kk-catgrid .kk-tile-label { bottom: 16px; }
            .kk-catgrid .kk-tile-label .pill {
                background: rgba(31, 17, 9, 0.78);
                backdrop-filter: blur(4px);
                transition: background .25s ease, transform .25s ease;
            }
            .kk-catgrid .kk-tile:hover .kk-tile-label .pill {
                background: var(--kk-brown);
                transform: translateY(-2px);
            }
            @media (max-width: 767px) { .kk-catgrid .kk-tile { margin-right: 12px; } }


            /* ===== Shop It Your Way — Rail of hangers ===== */
            .kk-shop-your-way { background: var(--kk-cream-light); padding: 24px 0 28px; }
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
            /* Per-tab active background: 1) brown (default) 2) teal 3) green */
            .kk-syw-tab.is-active--price { background: #14B8A6; }
            .kk-syw-tab.is-active--shade { background: #2B4A2A; }
            .kk-syw-tab.is-active--price small,
            .kk-syw-tab.is-active--shade small { color: var(--kk-cream); opacity: 0.85; }
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
                .kk-shop-your-way { padding: 16px 0 20px; }
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

            /* Product cards — compact 4-up grid */
            .kk-product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
            @media (max-width: 1024px) { .kk-product-grid { grid-template-columns: repeat(3, 1fr); } }
            @media (max-width: 640px)  { .kk-product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
            .kk-product { background: var(--kk-cream-lighter); border-radius: 6px; overflow: hidden; display: flex; flex-direction: column; }
            .kk-product__media { position: relative; aspect-ratio: 4/5; overflow: hidden; background: var(--kk-cream-dark); }
            .kk-product__media img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s; }
            .kk-product:hover .kk-product__media img { transform: scale(1.03); }
            .kk-product__tag { position: absolute; top: 9px; left: 9px; background: var(--kk-brown-dark); color: var(--kk-cream); padding: 3px 8px; border-radius: 999px; font-size: 8px; letter-spacing: 0.16em; text-transform: uppercase; font-weight: 700; }
            .kk-product__discount { position: absolute; top: 9px; right: 9px; background: var(--kk-tan-dark); color: var(--kk-cream); padding: 3px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; letter-spacing: 0.04em; }
            .kk-product__body { padding: 12px 12px 14px; display: flex; flex-direction: column; gap: 4px; flex: 1; }
            .kk-product__label { font-size: 9px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--kk-text-muted); font-weight: 600; }
            .kk-product__name { font-family: var(--kk-display); font-size: 14px; color: var(--kk-text); line-height: 1.25; margin: 0; }
            .kk-product__price { font-size: 13px; color: var(--kk-text); font-weight: 600; }
            .kk-product__price del { color: var(--kk-text-muted); font-weight: 400; margin-right: 6px; font-size: 12px; }
            .kk-product__cta { margin-top: 8px; }
            .kk-product__cta .kk-btn-brown { padding: 8px 14px; font-size: 10.5px; letter-spacing: 0.1em; }

            /* About Us — video-led, minimal copy */
            .kk-about { background: var(--kk-cream); padding: 40px 0; text-align: center; }
            .kk-about p.intro { max-width: 480px; margin: 14px auto 0; color: var(--kk-text-muted); font-size: 15px; line-height: 1.65; }
            /* Three reel-style (9:16) videos, Instagram-reels grid */
            .kk-about-reels {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                width: 100%;
                max-width: 900px;
                margin: 40px auto 0;
            }
            .kk-about-reel {
                position: relative;
                aspect-ratio: 9 / 16;
                border-radius: 14px;
                overflow: hidden;
                background: var(--kk-brown-darker);
                box-shadow: 0 24px 60px rgba(45, 24, 16, 0.20);
            }
            .kk-about-reel video,
            .kk-about-reel img {
                width: 100%; height: 100%;
                object-fit: cover;
                display: block;
            }
            .kk-about-reel::after {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(0,0,0,0) 55%, rgba(45,24,16,0.28) 100%);
                pointer-events: none;
            }
            .kk-about-cta { margin-top: 36px; }
            @media (max-width: 640px) {
                .kk-about { padding: 28px 0; }
                .kk-about-reels { margin-top: 28px; gap: 10px; }
                .kk-about-reel { border-radius: 8px; }
            }

            /* Qualities (dark) — video-background cards */
            .kk-qualities { background: var(--kk-brown-dark); color: var(--kk-cream); padding: 28px 0; text-align: center; }
            .kk-qualities h2 { font-family: var(--kk-display); font-size: 32px; color: var(--kk-cream); margin: 10px 0 8px; }
            .kk-qualities p.sub { color: rgba(239,226,203,.7); font-size: 13px; max-width: 520px; margin: 0 auto; }

            /* Our Qualities — horizontal autoplay slider (Task 4) */
            .kk-qslider { position: relative; margin-top: 28px; }
            .kk-qslider__track {
                display: flex; gap: 16px; overflow-x: auto;
                scroll-snap-type: x mandatory; scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                padding: 6px 2px 12px; scrollbar-width: none;
            }
            .kk-qslider__track::-webkit-scrollbar { display: none; }
            .kk-qslider .kk-quality {
                flex: 0 0 calc((100% - 2 * 16px) / 3);   /* 3 per view (desktop) */
                scroll-snap-align: start;
                opacity: 1; transform: none; transition: none;   /* slider: no reveal offset */
            }
            @media (max-width: 1024px) { .kk-qslider .kk-quality { flex-basis: calc((100% - 16px) / 2); } }  /* 2 per view */
            @media (max-width: 640px)  { .kk-qslider .kk-quality { flex-basis: 80%; } }                       /* ~1 per view */

            .kk-quality {
                position: relative;
                aspect-ratio: 3 / 4;
                border-radius: 10px;
                overflow: hidden;
                background: var(--kk-brown-darker);
                text-align: left;
                display: block;
                text-decoration: none;
                opacity: 0;
                transform: translateY(28px);
                transition: opacity 650ms ease-out, transform 650ms cubic-bezier(0.19, 1, 0.22, 1);
                transition-delay: var(--reveal-delay, 0ms);
            }
            .kk-qualities-grid.is-revealed .kk-quality { opacity: 1; transform: translateY(0); }

            .kk-quality__video {
                position: absolute; inset: 0;
                width: 100%; height: 100%;
                object-fit: cover;
                z-index: 0;
                transition: transform 0.6s ease;
            }
            .kk-quality:hover .kk-quality__video { transform: scale(1.06); }

            .kk-quality__overlay {
                position: absolute; inset: 0; z-index: 1;
                background: linear-gradient(to top,
                    rgba(31,17,9,0.94) 0%,
                    rgba(45,24,16,0.58) 45%,
                    rgba(45,24,16,0.22) 100%);
                transition: background 0.35s ease;
            }
            .kk-quality:hover .kk-quality__overlay {
                background: linear-gradient(to top,
                    rgba(31,17,9,0.86) 0%,
                    rgba(45,24,16,0.40) 45%,
                    rgba(45,24,16,0.08) 100%);
            }

            .kk-quality__content {
                position: absolute; left: 0; right: 0; bottom: 0; z-index: 2;
                padding: 26px 24px;
            }
            .kk-quality__icon {
                width: 36px; height: 36px;
                border-radius: 50%;
                background: rgba(239,226,203,0.15);
                border: 1px solid rgba(239,226,203,0.45);
                display: flex; align-items: center; justify-content: center;
                margin-bottom: 14px;
            }
            .kk-quality__icon svg { width: 18px; height: 18px; color: var(--kk-cream); }
            .kk-quality__content h4 { font-family: var(--kk-display); font-size: 19px; color: var(--kk-cream); margin: 0 0 8px; }
            .kk-quality__content p { font-size: 12.5px; color: rgba(239,226,203,0.82); margin: 0; line-height: 1.65; }

            @media (prefers-reduced-motion: reduce) {
                .kk-quality { opacity: 1; transform: none; transition: none; }
                .kk-quality__video { display: none; }
            }

            /* Newsletter */
            .kk-newsletter { background: var(--kk-cream-light); color: var(--kk-text); padding: 36px 0; text-align: center; }
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
                .kk-section { padding: 20px 0; }
                .kk-section-title { font-size: 22px; }
                .kk-section-title--lg { font-size: 28px; }
                .kk-about, .kk-qualities { padding: 24px 0; }
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
                    <a href="{{ route('home') }}?flash_sale={{ $flashSale->slug }}" @click="dismiss()" class="kk-btn-brown w-full">
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

    {{-- Offer popup (Task 1): shown once per visitor, captures name/email/mobile --}}
    @include('partials.offer-popup')

    <div class="kk-home">

        {{-- ============================================
             HERO BACKGROUND VIDEO
             ============================================ --}}
        @php
            // The hero used to hard-code a video file, so changing it meant editing
            // this template. It now renders the first active hero banner from the
            // admin panel, which may carry a video or an image. The hard-coded clip
            // stays as the fallback for when no hero banner has been added yet.
            $heroBanner = ($banners ?? collect())->first();
            $heroName = $siteSettings['site_name'] ?? 'Karmaa Kulture';
        @endphp
        <section class="kk-hero">
            @if($heroBanner && $heroBanner->has_video)
                <div class="kk-hero-slide kk-hero-slide--video">
                    <video class="kk-hero-video"
                           src="{{ $heroBanner->video }}"
                           @if($heroBanner->image_url) poster="{{ $heroBanner->image }}" @endif
                           autoplay
                           muted
                           loop
                           playsinline
                           preload="auto"
                           aria-label="{{ $heroBanner->title ?: $heroName }} hero video">
                    </video>
                </div>
            @elseif($heroBanner && $heroBanner->image_url)
                <div class="kk-hero-slide">
                    @if($heroBanner->link)
                        <a href="{{ $heroBanner->link }}" aria-label="{{ $heroBanner->title ?: $heroName }}">
                            <img src="{{ $heroBanner->image }}" alt="{{ $heroBanner->title ?: $heroName }}" fetchpriority="high">
                        </a>
                    @else
                        <img src="{{ $heroBanner->image }}" alt="{{ $heroBanner->title ?: $heroName }}" fetchpriority="high">
                    @endif
                </div>
            @else
                <div class="kk-hero-slide kk-hero-slide--video">
                    <video class="kk-hero-video"
                           src="{{ asset('images/karmaa-kulture-web-banner-v3.mp4') }}"
                           autoplay
                           muted
                           loop
                           playsinline
                           preload="auto"
                           aria-label="{{ $heroName }} hero video">
                    </video>
                </div>
            @endif
        </section>

        <style>
            /* Full-bleed hero — span the entire viewport width regardless of
               any parent container, and clip any margin baked into the video. */
            .kk-hero {
                width: 100vw;
                max-width: 100vw;
                margin-left: calc(50% - 50vw);
                margin-right: calc(50% - 50vw);
                overflow: hidden;
            }
            .kk-hero-slide--video {
                aspect-ratio: auto;
                background: var(--kk-brown-dark);
                overflow: hidden;
            }
            .kk-hero-video {
                width: 100%;
                height: auto;
                display: block;
            }
        </style>

        {{-- ============================================
             SHOP BY CATEGORY — bento mosaics per gender
             ============================================ --}}
        @php
            // Pull Men's and Women's roots directly from the DB so the section
            // is independent of the controller's $categories pagination/limit
            // and survives admins adding new top-level categories.
            $mensRoot = \App\Models\Category::whereNull('parent_id')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('slug', 'mens')->orWhere('slug', 'men')
                      ->orWhere('name', "Men's")->orWhere('name', 'Men');
                })
                ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('position')])
                ->first();

            $womensRoot = \App\Models\Category::whereNull('parent_id')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('slug', 'womens')->orWhere('slug', 'women')
                      ->orWhere('name', "Women's")->orWhere('name', 'Women');
                })
                ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('position')])
                ->first();

            $mensKids   = $mensRoot   ? $mensRoot->children->take(12)  : collect();
            $womensKids = $womensRoot
                ? $womensRoot->children
                    ->reject(fn ($c) => \Illuminate\Support\Str::contains(strtolower($c->name), ['t-shirt', 'tshirt', 't shirt']))
                    ->take(12)->values()
                : collect();

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
                <div class="kk-catgrid kk-catgrid--mens" x-data="kkCarousel">
                    <button type="button" class="kk-catgrid__nav kk-catgrid__nav--prev" :class="{ 'is-disabled': atStart }" @click="prev()" aria-label="Previous">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="kk-catgrid__track" x-ref="track" @scroll.debounce.80ms="update()">
                    @foreach($mensKids as $i => $child)
                        <a href="{{ route('category.show', $child) }}" class="kk-tile">
                            @if($child->video_url)
                                <video autoplay muted loop playsinline preload="metadata"
                                       src="{{ str_starts_with($child->video_url, 'http') ? $child->video_url : asset($child->video_url) }}"
                                       style="width:100%; height:100%; object-fit:cover; display:block;"></video>
                            @elseif($child->image_url)
                                <img src="{{ asset('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
                            @else
                                <div class="w-full h-full" style="background: linear-gradient(135deg, {{ $mensTints[$i % count($mensTints)] }} 0%, var(--kk-brown-dark) 100%);"></div>
                            @endif
                            <div class="kk-tile-overlay"></div>
                            <div class="kk-tile-label"><span class="pill">{{ Str::upper($child->name) }}</span></div>
                        </a>
                    @endforeach
                    </div>
                    <button type="button" class="kk-catgrid__nav kk-catgrid__nav--next" :class="{ 'is-disabled': atEnd }" @click="next()" aria-label="Next">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
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
                <div class="kk-catgrid kk-catgrid--womens" x-data="kkCarousel">
                    <button type="button" class="kk-catgrid__nav kk-catgrid__nav--prev" :class="{ 'is-disabled': atStart }" @click="prev()" aria-label="Previous">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="kk-catgrid__track" x-ref="track" @scroll.debounce.80ms="update()">
                    @foreach($womensKids as $i => $child)
                        <a href="{{ route('category.show', $child) }}" class="kk-tile">
                            @if($child->video_url)
                                <video autoplay muted loop playsinline preload="metadata"
                                       src="{{ str_starts_with($child->video_url, 'http') ? $child->video_url : asset($child->video_url) }}"
                                       style="width:100%; height:100%; object-fit:cover; display:block;"></video>
                            @elseif($child->image_url)
                                <img src="{{ asset('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
                            @else
                                <div class="w-full h-full" style="background: linear-gradient(135deg, {{ $womensTints[$i % count($womensTints)] }} 0%, var(--kk-brown-dark) 100%);"></div>
                            @endif
                            <div class="kk-tile-overlay"></div>
                            <div class="kk-tile-label"><span class="pill">{{ Str::upper($child->name) }}</span></div>
                        </a>
                    @endforeach
                    </div>
                    <button type="button" class="kk-catgrid__nav kk-catgrid__nav--next" :class="{ 'is-disabled': atEnd }" @click="next()" aria-label="Next">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </section>
        @endif

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('kkCarousel', (opts = {}) => ({
                    atStart: true,
                    atEnd: false,
                    autoplay: opts.autoplay || false,
                    interval: opts.interval || 3500,
                    _timer: null,
                    init() {
                        this.$nextTick(() => this.update());
                        window.addEventListener('resize', () => this.update());
                        if (this.autoplay) this.start();
                    },
                    start() {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        this.stop();
                        this._timer = window.setInterval(() => this.auto(), this.interval);
                    },
                    stop() { if (this._timer) { window.clearInterval(this._timer); this._timer = null; } },
                    auto() {
                        const t = this.$refs.track;
                        if (!t) return;
                        if (Math.ceil(t.scrollLeft + t.clientWidth) >= t.scrollWidth - 2) {
                            t.scrollTo({ left: 0, behavior: 'smooth' });   // loop back to start
                        } else {
                            t.scrollBy({ left: this.step(), behavior: 'smooth' });
                        }
                    },
                    update() {
                        const t = this.$refs.track;
                        if (!t) return;
                        this.atStart = t.scrollLeft <= 2;
                        this.atEnd = Math.ceil(t.scrollLeft + t.clientWidth) >= t.scrollWidth - 2;
                    },
                    step() {
                        const t = this.$refs.track;
                        return Math.max(t.clientWidth * 0.9, 200);   // page by ~one viewport
                    },
                    prev() { this.$refs.track.scrollBy({ left: -this.step(), behavior: 'smooth' }); },
                    next() { this.$refs.track.scrollBy({ left:  this.step(), behavior: 'smooth' }); },
                }));
            });
        </script>

        {{-- ============================================
             NEW ARRIVALS
             ============================================ --}}
        @php $arrivals = ($newArrivals ?? collect())->merge($featuredProducts ?? collect())->unique('id')->take(4); @endphp
        @if($arrivals->count())
        <section class="kk-section">
            <div class="container mx-auto px-4">
                <div class="kk-section-header">
                    <h2 class="kk-section-title">New Arrivals</h2>
                    <a href="{{ route('new-arrivals') }}" class="kk-view-all">View All <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="kk-product-grid">
                    @foreach($arrivals as $product)
                        <x-product-card :product="$product" :show-quick-view="false" />
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ============================================
             BESTSELLERS
             ============================================ --}}
        @php $bs = ($bestsellers ?? collect())->take(4); @endphp
        @if($bs->count())
        <section class="kk-section" style="background: var(--kk-cream-light);">
            <div class="container mx-auto px-4">
                <div class="kk-section-header">
                    <h2 class="kk-section-title">Bestsellers</h2>
                    <a href="{{ route('bestsellers') }}" class="kk-view-all">View All <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="kk-product-grid">
                    @foreach($bs as $product)
                        <x-product-card :product="$product" :show-quick-view="false" />
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ============================================
             ABOUT US — video-led, minimal text
             ============================================ --}}
        @php
            $aboutTitle = ($sections['about_us']->title ?? null) ?: 'Crafted to Last';
            $aboutText  = ($sections['about_us']->content ?? null) ?: 'A closer look at the cloth, cut and craft.';
            $aboutLink  = ($sections['about_us']->button_link ?? null);
            $aboutLink  = ($aboutLink && $aboutLink !== '#') ? $aboutLink : route('about');
            // Three reel-style videos, each admin-configurable (Site Settings).
            // Falls back to numbered default paths so the section works pre-config.
            $aboutVideoKeys     = ['about_us_video_url', 'about_us_video_url_2', 'about_us_video_url_3'];
            $aboutVideoDefaults = ['videos/karmaa-about.mp4', 'videos/karmaa-about-2.mp4', 'videos/karmaa-about-3.mp4'];
            $aboutVideos = [];
            foreach ($aboutVideoKeys as $ai => $ak) {
                $val = \App\Models\Setting::get($ak, '');
                $aboutVideos[] = $val
                    ? (str_starts_with($val, 'http') ? $val : asset($val))
                    : asset($aboutVideoDefaults[$ai]);
            }
        @endphp
        <section class="kk-about">
            <div class="container mx-auto px-4">
                <span class="kk-eyebrow">About Us</span>
                <h2 class="kk-section-title kk-section-title--lg" style="margin-top:8px;">{{ $aboutTitle }}</h2>
                <p class="intro">{{ is_string($aboutText) ? $aboutText : '' }}</p>

                <div class="kk-about-reels">
                    @foreach($aboutVideos as $aboutVideo)
                        <div class="kk-about-reel">
                            <video autoplay muted loop playsinline preload="metadata">
                                <source src="{{ $aboutVideo }}" type="video/mp4">
                            </video>
                        </div>
                    @endforeach
                </div>

                <div class="kk-about-cta">
                    <a href="{{ $aboutLink }}" class="kk-btn-brown">Our Story</a>
                </div>
            </div>
        </section>

        {{-- ============================================
             SHOP IT YOUR WAY — Rail of hangers per tab
             ============================================ --}}
        @php
            // Filter items come from admin (ShopFilterItem model). Normalise each
            // group into the {label, shade, count, q} shape the markup expects.
            $shopFilters = $shopFilters ?? collect();
            $kkTabs = [
                'size'  => ['eyebrow' => 'Find Your Fit',       'title' => 'Size',  'items' => []],
                'price' => ['eyebrow' => 'Perfectly Portioned', 'title' => 'Price', 'items' => []],
                'shade' => ['eyebrow' => 'The Dye Lab',         'title' => 'Shade', 'items' => []],
            ];
            foreach ($kkTabs as $key => $_) {
                foreach (($shopFilters[$key] ?? collect()) as $row) {
                    $kkTabs[$key]['items'][] = [
                        'label' => $row->label,
                        'shade' => $row->shade_hex ?: '#8c5c34',
                        'count' => $row->sub_label ?: '',
                        'q'     => $row->query_string ?: '',
                    ];
                }
            }
        @endphp
        @if(collect($kkTabs)->contains(fn($t) => count($t['items']) > 0))
        <section class="kk-shop-your-way" x-data="{ tab: 'size' }">
            <div class="container mx-auto px-4 text-center">
                <span class="kk-eyebrow">Curate The Edit</span>
                <h2 class="kk-syw-heading">Shop It Your <em>Way</em></h2>
                <p class="kk-syw-sub">Pick a size off the rail — every cut is tailored for a flattering drape.</p>

                <div class="kk-syw-tabs">
                    @foreach($kkTabs as $tabKey => $tabCfg)
                        <button class="kk-syw-tab"
                                :class="tab==='{{ $tabKey }}' ? 'is-active is-active--{{ $tabKey }}' : ''"
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
                                        <a href="{{ route('home') }}?{{ $item['q'] }}"
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
        @endif

        {{-- ============================================
             OUR QUALITIES (dark)
             ============================================ --}}
        <section class="kk-qualities">
            <div class="container mx-auto px-4">
                <span class="kk-eyebrow" style="color: var(--kk-tan);">What Sets Us Apart</span>
                <h2>Our Qualities</h2>
                <p class="sub">Six pillars every piece is measured against — no shortcuts, no exceptions.</p>

                {{-- Cards come from admin: Online Store > Our Qualities. Autoplay slider (Task 4). --}}
                @php $qualities = $qualities ?? collect(); @endphp
                @if($qualities->count())
                <div class="kk-qslider"
                     x-data="kkCarousel({ autoplay: true, interval: 3800 })"
                     @mouseenter="stop()" @mouseleave="autoplay && start()">
                    <div class="kk-qslider__track" x-ref="track" @scroll.debounce.100ms="update()" tabindex="0" aria-label="Our qualities">
                        @foreach($qualities as $q)
                            <div class="kk-quality">
                                <div class="kk-quality__overlay"></div>
                                <div class="kk-quality__content">
                                    <span class="kk-quality__icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7L9 18l-5-5"/></svg>
                                    </span>
                                    <h4>{{ $q->title }}</h4>
                                    <p>{{ $q->description }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($qualities->count() > 3)
                    <button type="button" class="kk-catgrid__nav kk-catgrid__nav--prev" :class="atStart && 'is-disabled'" @click="prev()" aria-label="Previous">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="kk-catgrid__nav kk-catgrid__nav--next" :class="atEnd && 'is-disabled'" @click="next()" aria-label="Next">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    @endif
                </div>
                @endif
            </div>
        </section>

    </div>

</x-layouts.app>
