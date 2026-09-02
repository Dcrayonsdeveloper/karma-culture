<x-layouts.app>
@push('meta')
    @php
        // Organization and WebSite belong on the home page: they tell Google the
        // brand behind the shop and expose the site search, which is what earns
        // the search box under a listing. Social links are only included when
        // they are actually configured - an empty sameAs is worse than none.
        $kkSameAs = collect([
            \App\Models\Setting::get('social_instagram'),
            \App\Models\Setting::get('social_facebook'),
            \App\Models\Setting::get('social_twitter'),
            \App\Models\Setting::get('social_linkedin'),
        ])->filter()->values();

        $kkLogo = \App\Models\Setting::get('site_logo')
            ? asset_v('storage/' . \App\Models\Setting::get('site_logo'))
            : asset_v('images/karmaa-kulture-logo.png');

        $kkOrg = array_filter([
            '@type' => 'Organization',
            '@id' => url('/') . '#organization',
            'name' => \App\Models\Setting::get('site_name', config('app.name')),
            'url' => url('/'),
            'logo' => $kkLogo,
            'sameAs' => $kkSameAs->isNotEmpty() ? $kkSameAs->all() : null,
        ]);

        $kkSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $kkOrg,
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'url' => url('/'),
                    'name' => \App\Models\Setting::get('site_name', config('app.name')),
                    'publisher' => ['@id' => url('/') . '#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('search') . '?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($kkSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
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
               small - the visible gap between two sections is roughly double
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

            /* ===== Category grid - uniform equal-size cards (Men's) ===== */
            .kk-catgrid { position: relative; }
            .kk-catgrid__track {
                display: flex;
                gap: 18px;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                padding: 8px 2px 14px;                 /* room so hover-lift/shadow isn't clipped */
                scrollbar-width: none;                 /* hide bar - navigate via arrows / swipe */
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


            /* ===== Shop It Your Way - Rail of hangers ===== */
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

            /* Stage + panel.
               The panels are stacked in a single grid cell rather than absolutely
               positioned in a min-height box. Stacking still crossfades one tab
               into the next, but the stage now takes its height from whichever
               panel is showing - the old fixed 420px could not contain a rail
               that wrapped to a second row, so the overflow painted straight
               over the section below. */
            /* minmax(0, 1fr), not the implicit `auto` track: an auto column is
               sized by its max-content, so the rail could make the stage wider
               than the container and the body's overflow-x-clip would quietly
               cut the right-hand hangers off instead of scrolling to them. */
            .kk-syw-stage { display: grid; grid-template-columns: minmax(0, 1fr); margin-top: 64px; }
            .kk-syw-panel { grid-area: 1 / 1; display: flex; align-items: flex-start; justify-content: center; }
            .kk-syw-panel[data-on="true"] .kk-rail-cell {
                animation: kk-rise .55s var(--d, 0ms) cubic-bezier(.22,1,.36,1) both;
            }
            @keyframes kk-rise {
                from { opacity: 0; transform: translateY(24px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            /* The visible rail.

               One rail, one row, at every count and every width. The rail used
               to be a grid whose column count came from the item list, so the
               moment an admin added a seventh size the extra hanger dropped
               onto a second row that the rail bar never reached and that
               painted straight over the section below. Now the hangers size
               themselves down to fit the count and the rail scrolls sideways
               once they hit their floor, so the shape holds for 3 items or 30. */
            .kk-rail-wrap {
                position: relative;
                width: 100%;
                /* The rail itself is still 980px; the extra is the scroller's
                   own gutter, so the hangers keep the width they always had. */
                --kk-rail-pad: 16px;
                max-width: calc(980px + 2 * var(--kk-rail-pad));
                margin: 0 auto;
            }
            .kk-rail-scroll {
                overflow-x: auto;
                overflow-y: hidden;
                overscroll-behavior-x: contain;
                -webkit-overflow-scrolling: touch;
                scroll-snap-type: x proximity;
                /* Room for the bar's end caps and the hangers' drop shadows. */
                padding: 16px var(--kk-rail-pad) 6px;
                scrollbar-width: thin;
                scrollbar-color: rgba(45,24,16,.22) transparent;
            }
            .kk-rail-scroll::-webkit-scrollbar { height: 6px; }
            .kk-rail-scroll::-webkit-scrollbar-track { background: transparent; }
            .kk-rail-scroll::-webkit-scrollbar-thumb { background: rgba(45,24,16,.20); border-radius: 999px; }
            .kk-rail-scroll:hover::-webkit-scrollbar-thumb { background: rgba(45,24,16,.34); }

            /* Cells along the rail */
            .kk-rail-cells {
                /* max-content keeps the row (and the bar spanning it) intact
                   when the hangers overflow; min-width:100% lets a short rail
                   still spread across the full width the way the grid did. */
                display: flex;
                justify-content: space-around;
                align-items: flex-start;
                width: max-content;
                min-width: 100%;
                gap: var(--kk-rail-gap);
                position: relative;
                z-index: 1;

                /* --kk-rail-count is set per tab from the admin row count. */
                --kk-rail-gap: clamp(6px, 1.2vw, 16px);
                /* Fallback only. Tailwind's .container steps its max-width by
                   breakpoint, so the rail's real width is nothing like a slice
                   of the viewport - at 993px it gets 732px, not 913px - and a
                   vw guess made six hangers scroll when they comfortably fit.
                   The @supports block below measures the box instead. */
                --kk-rail-avail: min(980px, calc(100vw - 48px - 2 * var(--kk-rail-pad)));
                /* Shrink each hanger to fit the count, down to a floor - past
                   that the rail scrolls rather than shaving them to slivers. */
                --kk-rail-cell: clamp(
                    84px,
                    calc((var(--kk-rail-avail) - (var(--kk-rail-count, 6) - 1) * var(--kk-rail-gap)) / var(--kk-rail-count, 6)),
                    150px
                );
            }

            /* 100cqw is the scroller's own content width, so the hangers are
               sized against the space they actually have rather than a guess
               made from the viewport. */
            @supports (container-type: inline-size) {
                .kk-rail-scroll { container-type: inline-size; }
                .kk-rail-cells { --kk-rail-avail: 100cqw; }
            }
            .kk-rail-bar {
                position: absolute;
                /* Pinned to a fraction of the hanger width instead of a fixed
                   38px: the hook sits at the same point on every hanger, so a
                   fixed offset slid off them as soon as the hangers shrank. */
                top: calc(var(--kk-rail-cell) * 0.16);
                left: 12px;
                right: 12px;
                height: clamp(3px, calc(var(--kk-rail-cell) * 0.033), 5px);
                background: linear-gradient(to bottom, #2d1810, #1f1109);
                border-radius: 4px;
                box-shadow: 0 3px 6px rgba(45,24,16,.30);
                z-index: 0;
            }
            .kk-rail-bar::before, .kk-rail-bar::after {
                content: '';
                position: absolute;
                width: clamp(9px, calc(var(--kk-rail-cell) * 0.093), 14px);
                height: clamp(9px, calc(var(--kk-rail-cell) * 0.093), 14px);
                background: #2d1810;
                border-radius: 50%;
                top: 50%;
                transform: translateY(-50%);
                box-shadow: 0 2px 4px rgba(0,0,0,.2);
            }
            .kk-rail-bar::before { left: -10px; }
            .kk-rail-bar::after  { right: -10px; }

            .kk-rail-cell {
                flex: 0 0 auto;
                width: var(--kk-rail-cell);
                scroll-snap-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
                text-decoration: none;
                color: inherit;
            }
            .kk-shirt-hanger {
                width: 100%;
                transform-origin: top center;
                transition: transform .4s cubic-bezier(.22,1,.36,1), filter .35s;
            }
            .kk-shirt-hanger svg { width: 100%; height: auto; display: block; filter: drop-shadow(0 8px 14px rgba(45,24,16,.18)); }
            .kk-rail-cell:hover .kk-shirt-hanger {
                transform: rotate(-3deg);
                filter: drop-shadow(0 12px 20px rgba(45,24,16,.28));
            }

            /* Labels under each hanger - sized off the hanger so they stay in
               proportion (and inside the cell) as the rail tightens up. */
            .kk-rail-label {
                font-family: var(--kk-display);
                font-size: clamp(15px, calc(var(--kk-rail-cell) * 0.145), 22px);
                font-weight: 600;
                color: var(--kk-text);
                letter-spacing: 0.04em;
                line-height: 1.2;
                margin-top: 6px;
                overflow-wrap: anywhere;
                transition: color .3s;
            }
            .kk-rail-cell:hover .kk-rail-label { color: var(--kk-tan-dark); }
            .kk-rail-count {
                font-size: clamp(8px, calc(var(--kk-rail-cell) * 0.067), 10px);
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: var(--kk-text-muted);
                font-weight: 500;
                line-height: 1.3;
                margin-top: -2px;
                overflow-wrap: anywhere;
            }

            @media (max-width: 1024px) {
                .kk-syw-heading { font-size: 36px; }
            }
            @media (max-width: 767px) {
                .kk-shop-your-way { padding: 16px 0 20px; }
                .kk-syw-heading { font-size: 28px; }
                .kk-syw-tabs { padding: 4px; gap: 2px; margin-top: 24px; max-width: 100%; }
                .kk-syw-tab { padding: 10px 16px; min-width: 0; flex: 1 1 0; }
                .kk-syw-tab small { font-size: 8px; letter-spacing: 0.22em; }
                .kk-syw-tab span { font-size: 14px; }
                .kk-syw-stage { margin-top: 36px; }
                .kk-rail-wrap { --kk-rail-pad: 12px; }
            }
            @media (max-width: 520px) {
                /* Three eyebrows at ~150px each ran the tab row past the
                   viewport and gave the whole page a sideways scroll. */
                .kk-syw-tab small { display: none; }
                .kk-syw-tab { padding: 11px 14px; }
            }

            /* Product cards - compact 4-up grid */
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
            /* Card text uses the body font (not the display serif) so home-page
               cards match the listing-page cards, and label/name reserve fixed
               heights so brand, name and price line up across every card even
               when a product has no brand. */
            .kk-product__label { font-family: var(--kk-body); font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--kk-text-muted); font-weight: 500; display: block; min-height: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .kk-product__name { font-family: var(--kk-body); font-size: 13px; font-weight: 600; color: var(--kk-text); line-height: 1.35; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 35px; }
            .kk-product__price { font-size: 13px; color: var(--kk-text); font-weight: 700; }
            .kk-product__price del { color: var(--kk-text-muted); font-weight: 400; margin: 0 4px 0 6px; font-size: 12px; }
            .kk-product__off { font-size: 11px; font-weight: 600; color: var(--kk-tan-dark); }
            .kk-product__cta { margin-top: auto; padding-top: 8px; }
            .kk-product__cta .kk-btn-brown { padding: 8px 14px; font-size: 10.5px; letter-spacing: 0.1em; }

            /* About Us - video-led, minimal copy */
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

            /* Qualities (dark) - video-background cards */
            .kk-qualities { background: var(--kk-brown-dark); color: var(--kk-cream); padding: 28px 0; text-align: center; }
            .kk-qualities h2 { font-family: var(--kk-display); font-size: 32px; color: var(--kk-cream); margin: 10px 0 8px; }
            .kk-qualities p.sub { color: rgba(239,226,203,.7); font-size: 13px; max-width: 520px; margin: 0 auto; }

            /* Our Qualities - horizontal autoplay slider (Task 4) */
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
                aspect-ratio: 4 / 5;
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

            /* Still-image background, uploaded per card in admin. Deliberately not
               reusing __video: the reduced-motion rule below hides that layer, and a
               still photo has no motion to reduce — it should stay visible. */
            .kk-quality__media {
                position: absolute; inset: 0;
                width: 100%; height: 100%;
                object-fit: cover;
                z-index: 0;
                transition: transform 0.6s ease;
            }
            .kk-quality:hover .kk-quality__media { transform: scale(1.06); }

            /* No image on the card. The tile exists to hold a picture, so without one
               it would be a flat empty rectangle — and in a row that mixes photo and
               text cards, flexbox stretches it to the photo's height, making the void
               worse. These get a designed treatment instead of a blank panel: a warm
               diagonal wash, a hairline edge, and a large index numeral so the space
               above the text reads as deliberate. */
            .kk-quality--plain {
                aspect-ratio: auto;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                min-height: 230px;
                border: 1px solid rgba(239, 226, 203, 0.13);
                background:
                    radial-gradient(115% 85% at 86% 6%, rgba(184, 137, 90, 0.26) 0%, rgba(184, 137, 90, 0) 60%),
                    linear-gradient(155deg, #351a0f 0%, #26120a 55%, #1b0e07 100%);
            }
            .kk-quality--plain .kk-quality__overlay { display: none; }
            .kk-quality--plain .kk-quality__content { position: static; padding: 24px 22px; }
            .kk-quality--plain:hover { border-color: rgba(184, 137, 90, 0.45); }

            .kk-quality__num {
                position: absolute; top: 12px; right: 20px; z-index: 1;
                font-family: var(--kk-display);
                font-size: 62px; line-height: 1; font-weight: 700;
                /* 0.09 alpha put this at roughly 1.1:1 against the card, and the
                   tan glow behind the top-right corner is exactly where it sits,
                   so the digits read as a smudge rather than a number. */
                color: rgba(239, 226, 203, 0.42);
                letter-spacing: -0.02em;
                pointer-events: none;
                user-select: none;
            }

            .kk-quality__overlay {
                position: absolute; inset: 0; z-index: 1;
                background: linear-gradient(to top,
                    rgba(20,10,5,0.97) 0%,
                    rgba(25,13,7,0.88) 26%,
                    rgba(31,17,9,0.55) 56%,
                    rgba(31,17,9,0.18) 100%);
                transition: background 0.35s ease;
            }
            .kk-quality:hover .kk-quality__overlay {
                background: linear-gradient(to top,
                    rgba(20,10,5,0.94) 0%,
                    rgba(25,13,7,0.78) 30%,
                    rgba(31,17,9,0.38) 62%,
                    rgba(31,17,9,0.06) 100%);
            }

            .kk-quality__content {
                position: absolute; left: 0; right: 0; bottom: 0; z-index: 2;
                padding: 26px 24px;
            }
            .kk-quality__icon {
                width: 38px; height: 38px;
                border-radius: 50%;
                background: rgba(184,137,90,0.24);
                border: 1px solid rgba(184,137,90,0.60);
                display: flex; align-items: center; justify-content: center;
                margin-bottom: 14px;
                transition: background .3s ease, border-color .3s ease;
            }
            .kk-quality:hover .kk-quality__icon { background: rgba(184,137,90,0.42); border-color: var(--kk-tan); }
            .kk-quality__icon svg { width: 18px; height: 18px; color: var(--kk-cream); }
            /* The shadows keep both lines legible when they fall over a bright photo. */
            .kk-quality__content h4 { font-family: var(--kk-display); font-size: 19px; color: var(--kk-cream); margin: 0 0 8px; text-shadow: 0 1px 14px rgba(0,0,0,0.6); }
            .kk-quality__content p { font-size: 12.5px; color: rgba(239,226,203,0.88); margin: 0; line-height: 1.65; text-shadow: 0 1px 10px rgba(0,0,0,0.55); }

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
                    <a href="{{ route('flash-sale.show', $flashSale->slug) }}" @click="dismiss()" class="kk-btn-brown w-full">
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
             HERO - every active banner, in admin order
             ============================================ --}}
        @php
            // This used to render `$banners->first()` and stop, so the second and
            // third banners an admin added were invisible and the reorder controls
            // in the admin panel only ever decided which single banner survived.
            // All of them are rendered now, as slides; the hard-coded clip is still
            // the fallback for a store that has not added a banner yet.
            $heroBanners = ($banners ?? collect())->values();
            $heroName = $siteSettings['site_name'] ?? 'Karmaa Kulture';
            $heroCount = $heroBanners->count();
        @endphp
        <section class="kk-hero"
                 @if($heroCount > 1)
                     x-data="kkHero({{ $heroCount }})"
                     x-init="start()"
                     @mouseenter="stop()" @mouseleave="start()"
                     role="region" aria-roledescription="carousel" aria-label="Highlights"
                 @endif>
            @if($heroCount)
                <div class="kk-hero-viewport">
                    @foreach($heroBanners as $i => $banner)
                        @php
                            // A banner may carry a video, an image or both; the image
                            // doubles as the poster frame when a video is present.
                            $hasOverlayText = $banner->title || $banner->subtitle || $banner->button_text;
                        @endphp
                        <div class="kk-hero-slide {{ $banner->has_video ? 'kk-hero-slide--video' : '' }}"
                             @if($heroCount > 1)
                                 x-show="current === {{ $i }}"
                                 x-transition:enter="kk-fade-enter" x-transition:enter-start="kk-fade-start"
                                 :aria-hidden="current !== {{ $i }}"
                             @endif
                             role="group" aria-roledescription="slide"
                             aria-label="{{ $i + 1 }} of {{ $heroCount }}">

                            {{-- The link used to be honoured for image banners only, so a
                                 video banner's Link URL was collected, stored and ignored.
                                 Both shapes are wrapped the same way now. --}}
                            @if($banner->link)
                                <a href="{{ $banner->link }}" class="kk-hero-link" aria-label="{{ $banner->title ?: $heroName }}">
                            @endif

                            @if($banner->has_video)
                                <video class="kk-hero-video"
                                       src="{{ $banner->video }}"
                                       @if($banner->image_url) poster="{{ $banner->image }}" @endif
                                       autoplay muted loop playsinline preload="{{ $i === 0 ? 'auto' : 'metadata' }}"
                                       aria-label="{{ $banner->title ?: $heroName }} hero video"></video>
                            @else
                                <img src="{{ $banner->image }}" alt="{{ $banner->title ?: $heroName }}"
                                     @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                            @endif

                            {{-- Heading, subtitle and button were editable in the admin
                                 and stored, but no template ever printed them, and the
                                 Overlay Style selector fed an accessor nothing called. --}}
                            @if($hasOverlayText)
                                <div class="kk-hero-overlay {{ $banner->overlay_css }}"></div>
                                <div class="kk-hero-caption kk-hero-caption--{{ $banner->overlay_style ?: 'left-dark' }}">
                                    @if($banner->title)
                                        <h2 class="kk-hero-title">{{ $banner->title }}</h2>
                                    @endif
                                    @if($banner->subtitle)
                                        <p class="kk-hero-sub">{{ $banner->subtitle }}</p>
                                    @endif
                                    @if($banner->button_text && $banner->link)
                                        <span class="kk-btn-cream kk-hero-btn">{{ $banner->button_text }}</span>
                                    @endif
                                </div>
                            @endif

                            @if($banner->link)
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($heroCount > 1)
                    <button type="button" class="kk-hero-nav kk-hero-nav--prev" @click="prev()" aria-label="Previous slide">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 16l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="kk-hero-nav kk-hero-nav--next" @click="next()" aria-label="Next slide">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M8 4l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="kk-hero-dots" role="tablist" aria-label="Choose slide">
                        @foreach($heroBanners as $i => $banner)
                            <button type="button" class="kk-hero-dot" :class="current === {{ $i }} && 'is-active'"
                                    @click="go({{ $i }})" role="tab" :aria-selected="current === {{ $i }}"
                                    aria-label="Slide {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="kk-hero-viewport">
                    <div class="kk-hero-slide kk-hero-slide--video">
                        <video class="kk-hero-video"
                               src="{{ asset_v('images/karmaa-kulture-web-banner-v3.mp4') }}"
                               autoplay muted loop playsinline preload="auto"
                               aria-label="{{ $heroName }} hero video"></video>
                    </div>
                </div>
            @endif
        </section>

        <style>
            /* Full-bleed hero - span the entire viewport width regardless of
               any parent container, and clip any margin baked into the video. */
            .kk-hero {
                position: relative;
                width: 100vw;
                max-width: 100vw;
                margin-left: calc(50% - 50vw);
                margin-right: calc(50% - 50vw);
                overflow: hidden;
            }
            .kk-hero-viewport { position: relative; }
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
            .kk-hero-link { display: block; color: inherit; text-decoration: none; }

            /* Caption. Only drawn when the admin filled in a heading, subtitle
               or button, so a plain image banner stays a plain image banner. */
            .kk-hero-overlay { position: absolute; inset: 0; pointer-events: none; }
            .kk-hero-caption {
                position: absolute; inset: 0; display: flex; flex-direction: column;
                justify-content: center; gap: 12px; padding: 0 8vw; pointer-events: none;
            }
            .kk-hero-caption--right-dark { align-items: flex-end; text-align: right; }
            .kk-hero-caption--center-vignette, .kk-hero-caption--full-dark { align-items: center; text-align: center; }
            .kk-hero-title {
                font-family: var(--kk-display); font-weight: 600; color: #fff; margin: 0;
                font-size: clamp(22px, 4.2vw, 52px); line-height: 1.08;
                text-shadow: 0 2px 18px rgba(0,0,0,.35);
            }
            .kk-hero-sub {
                color: rgba(255,255,255,.92); margin: 0; max-width: 46ch;
                font-size: clamp(12px, 1.5vw, 17px); line-height: 1.5;
                text-shadow: 0 1px 12px rgba(0,0,0,.35);
            }
            .kk-hero-btn { margin-top: 4px; align-self: flex-start; }
            .kk-hero-caption--right-dark .kk-hero-btn { align-self: flex-end; }
            .kk-hero-caption--center-vignette .kk-hero-btn,
            .kk-hero-caption--full-dark .kk-hero-btn { align-self: center; }
            @media (max-width: 640px) {
                .kk-hero-caption { padding: 0 6vw; gap: 8px; }
                .kk-hero-sub { display: none; }
            }

            /* Slider chrome - only rendered when there is more than one banner. */
            .kk-hero-nav {
                position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
                width: 38px; height: 38px; border-radius: 999px; border: none; cursor: pointer;
                display: flex; align-items: center; justify-content: center;
                background: rgba(255,255,255,.82); color: var(--kk-brown);
                transition: background .2s;
            }
            .kk-hero-nav:hover { background: #fff; }
            .kk-hero-nav--prev { left: 14px; }
            .kk-hero-nav--next { right: 14px; }
            .kk-hero-dots {
                position: absolute; left: 0; right: 0; bottom: 14px; z-index: 2;
                display: flex; justify-content: center; gap: 8px;
            }
            .kk-hero-dot {
                width: 8px; height: 8px; padding: 0; border-radius: 999px; cursor: pointer;
                border: 1px solid rgba(255,255,255,.85); background: rgba(255,255,255,.35);
                transition: background .2s, width .2s;
            }
            .kk-hero-dot.is-active { background: #fff; width: 22px; }
            .kk-fade-start { opacity: 0; }
            .kk-fade-enter { transition: opacity .45s ease; }
            @media (max-width: 640px) {
                .kk-hero-nav { width: 30px; height: 30px; }
                .kk-hero-nav--prev { left: 8px; }
                .kk-hero-nav--next { right: 8px; }
            }
            @media (prefers-reduced-motion: reduce) {
                .kk-fade-enter { transition: none; }
            }
        </style>

        @if($heroCount > 1)
            <script>
                function kkHero(count) {
                    return {
                        current: 0,
                        timer: null,
                        go(i) { this.current = (i + count) % count; },
                        next() { this.go(this.current + 1); },
                        prev() { this.go(this.current - 1); },
                        start() {
                            // Auto-advance is a decorative motion, so visitors who have
                            // asked their OS for less of it get a static first slide.
                            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                            this.stop();
                            this.timer = setInterval(() => this.next(), 6000);
                        },
                        stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
                    };
                }
            </script>
        @endif

        {{-- ============================================
             SHOP BY CATEGORY - bento mosaics per gender
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
                                <img src="{{ asset_v('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
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
                                <img src="{{ asset_v('storage/' . $child->image_url) }}" alt="{{ $child->name }}" loading="lazy">
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
             TRENDING NOW - most viewed in the last 30 days.
             Sits alongside Bestsellers (all-time sales), not instead of it.
             ============================================ --}}
        @php $tr = ($trending ?? collect())->take(4); @endphp
        @if($tr->count())
        <section class="kk-section">
            <div class="container mx-auto px-4">
                <div class="kk-section-header">
                    <h2 class="kk-section-title">Trending Now</h2>
                    <a href="{{ route('new-arrivals') }}" class="kk-view-all">View All <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="kk-product-grid">
                    @foreach($tr as $product)
                        <x-product-card :product="$product" :show-quick-view="false" />
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ============================================
             ABOUT US - video-led, minimal text
             ============================================ --}}
        @php
            $aboutSection = $sections['about_us'] ?? null;
            // The admin's Visible/Hidden switch on this section has to actually
            // hide it. A missing row still renders with the defaults below, so
            // the section works before it has ever been configured; only a row
            // that exists and is switched off suppresses the block.
            $aboutVisible = $aboutSection === null || $aboutSection->is_active;
            $aboutTitle = ($aboutSection->title ?? null) ?: 'Crafted to Last';
            // subtitle, not content: content is cast to an array and the admin
            // edits it as a repeater of title/description/icon items, so reading
            // it here produced an array where a sentence was wanted and the
            // admin's own tagline field never reached the page.
            $aboutText  = ($aboutSection->subtitle ?? null) ?: 'A closer look at the cloth, cut and craft.';
            $aboutLink  = ($aboutSection->button_link ?? null);
            $aboutLink  = ($aboutLink && $aboutLink !== '#') ? $aboutLink : route('about');
            $aboutButton = ($aboutSection->button_text ?? null) ?: 'Our Story';
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
        @if($aboutVisible)
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
                    <a href="{{ $aboutLink }}" class="kk-btn-brown">{{ $aboutButton }}</a>
                </div>
            </div>
        </section>
        @endif

        {{-- ============================================
             SHOP IT YOUR WAY - Rail of hangers per tab
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
        @php
            // Opened on Size unconditionally, so a shop that only fills in Price or
            // Shade greeted every visitor with an empty rail.
            $kkFirstTab = collect($kkTabs)->filter(fn ($t) => count($t['items']) > 0)->keys()->first() ?? 'size';
        @endphp
        <section class="kk-shop-your-way" x-data="{ tab: '{{ $kkFirstTab }}' }">
            <div class="container mx-auto px-4 text-center">
                <span class="kk-eyebrow">Curate The Edit</span>
                <h2 class="kk-syw-heading">Shop It Your <em>Way</em></h2>
                <p class="kk-syw-sub">Pick a size off the rail - every cut is tailored for a flattering drape.</p>

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
                        {{-- Every panel but the default one is cloaked. The stage takes its
                             height from its content now, so without this all three render
                             stacked for the instant before Alpine boots and the section
                             visibly collapses once two of them are hidden. --}}
                        <div class="kk-syw-panel"
                             @if($tabKey !== 'size') x-cloak @endif
                             :data-on="tab==='{{ $tabKey }}'"
                             x-show="tab==='{{ $tabKey }}'"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 translate-y-3"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            <div class="kk-rail-wrap">
                                {{-- The scroller is what keeps a long list on one rail: the
                                     hangers size themselves down to the item count and, once
                                     they hit their floor, the rail slides sideways instead of
                                     wrapping onto a second row. --}}
                                <div class="kk-rail-scroll">
                                    <div class="kk-rail-cells" style="--kk-rail-count: {{ max(1, count($tabCfg['items'])) }};">
                                        {{-- Only where there is something to hang off it: an
                                             emptied-out tab otherwise showed a bare rail. --}}
                                        @if(count($tabCfg['items']))
                                            <div class="kk-rail-bar" aria-hidden="true"></div>
                                        @endif
                                        @foreach($tabCfg['items'] as $i => $item)
                                            {{-- Display only. These linked through to the shop with a
                                                 size/price/shade filter, which returned nothing, so every
                                                 hanger was a dead end onto an empty results page. --}}
                                            {{-- The Query String an admin sets against each item
                                                 (size=M, price_min=1000&price_max=2000, shade=Indigo)
                                                 was validated, stored and then never read here, so the
                                                 field did nothing at all. The shop route accepts every
                                                 one of those keys - ProductController@index reads size,
                                                 price_min/max and shade - so they resolve to real
                                                 results. An item with no query stays a plain tile. --}}
                                            <{{ $item['q'] !== '' ? 'a' : 'div' }}
                                                 @if($item['q'] !== '') href="{{ route('shop') }}?{{ $item['q'] }}" @endif
                                                 class="kk-rail-cell @if($item['q'] !== '') kk-rail-cell--link @endif"
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
                                                {{-- Printed as authored. The admin sub-label field already
                                                     carries the noun (its placeholder is "120 Styles"), so
                                                     appending one here read "120 Styles Styles". --}}
                                                @if($item['count'] !== '')
                                                    <div class="kk-rail-count">{{ $item['count'] }}</div>
                                                @endif
                                            </{{ $item['q'] !== '' ? 'a' : 'div' }}>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <style>
            .kk-rail-cell--link { text-decoration: none; color: inherit; cursor: pointer; }
            .kk-rail-cell--link:focus-visible { outline: 2px solid var(--kk-tan-dark); outline-offset: 4px; border-radius: 4px; }
            .kk-rail-cell--link .kk-shirt-hanger { transition: transform .25s ease; }
            .kk-rail-cell--link:hover .kk-shirt-hanger { transform: translateY(3px); }
            @media (prefers-reduced-motion: reduce) {
                .kk-rail-cell--link .kk-shirt-hanger { transition: none; }
            }
        </style>
        @endif

        {{-- ============================================
             OUR QUALITIES (dark)
             ============================================ --}}
        {{-- Cards come from admin: Online Store > Our Qualities. With none active
             this drew a dark band containing a heading and nothing else, so the
             whole section is now gated on there being something to show. The
             subtitle counted "Six pillars" no matter how many cards existed. --}}
        @php $qualities = $qualities ?? collect(); @endphp
        @if($qualities->count())
        <section class="kk-qualities">
            <div class="container mx-auto px-4">
                <span class="kk-eyebrow" style="color: var(--kk-tan);">What Sets Us Apart</span>
                <h2>Our Qualities</h2>
                <p class="sub">{{ $qualities->count() }} {{ \Illuminate\Support\Str::plural('pillar', $qualities->count()) }} every piece is measured against - no shortcuts, no exceptions.</p>

                @if($qualities->count())
                <div class="kk-qslider"
                     x-data="kkCarousel({ autoplay: true, interval: 3800 })"
                     @mouseenter="stop()" @mouseleave="autoplay && start()">
                    <div class="kk-qslider__track" x-ref="track" @scroll.debounce.100ms="update()" tabindex="0" aria-label="Our qualities">
                        @foreach($qualities as $q)
                            <div class="kk-quality @if(! $q->image_url) kk-quality--plain @endif">
                                @if($q->image_url)
                                    <img class="kk-quality__media" src="{{ $q->image }}" alt="{{ $q->title }}" loading="lazy" decoding="async">
                                @else
                                    <span class="kk-quality__num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                @endif
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
        @endif

        {{-- ============================================
             TESTIMONIALS
             The admin has had a full Testimonials manager since the table was
             created, HomeController has always queried six of them, and no
             template ever printed one - so every review an admin wrote went
             nowhere. Rendered here, honouring the same Visible/Hidden switch
             the About Us block uses.
             ============================================ --}}
        @php
            $testimonials = ($testimonials ?? collect())->values();
            $tSection = $sections['testimonials'] ?? null;
            $tVisible = $tSection === null || $tSection->is_active;
            $tTitle = ($tSection->title ?? null) ?: 'What Our Customers Say';
            $tSub   = ($tSection->subtitle ?? null) ?: 'Worn, washed and lived in - in their words.';
        @endphp
        @if($tVisible && $testimonials->count())
        <section class="kk-section kk-testimonials">
            <div class="container mx-auto px-4">
                <div style="text-align:center; margin-bottom:22px;">
                    <span class="kk-eyebrow">Reviews</span>
                    <h2 class="kk-section-title kk-section-title--lg" style="margin-top:8px;">{{ $tTitle }}</h2>
                    <p class="kk-testimonials__sub">{{ is_string($tSub) ? $tSub : '' }}</p>
                </div>

                <div class="kk-treview-grid">
                    @foreach($testimonials as $t)
                        <figure class="kk-treview">
                            <div class="kk-treview__stars" aria-label="{{ $t->rating }} out of 5">
                                @for($star = 1; $star <= 5; $star++)
                                    <svg viewBox="0 0 20 20" aria-hidden="true"
                                         fill="{{ $star <= $t->rating ? 'currentColor' : 'none' }}"
                                         stroke="currentColor" stroke-width="1.4">
                                        <path d="M10 2.5l2.35 4.76 5.25.76-3.8 3.7.9 5.23L10 14.48l-4.7 2.47.9-5.23-3.8-3.7 5.25-.76L10 2.5z"/>
                                    </svg>
                                @endfor
                            </div>
                            <blockquote class="kk-treview__text">{{ $t->content }}</blockquote>
                            <figcaption class="kk-treview__by">
                                <span class="kk-treview__avatar" aria-hidden="true">
                                    @if($t->avatar_url)
                                        <img src="{{ asset_v('storage/' . $t->avatar_url) }}" alt="" loading="lazy" decoding="async">
                                    @else
                                        {{-- mb_substr, not substr: the name validator accepts scripts
                                             whose first character is several bytes wide, and slicing
                                             one byte off those produced a broken glyph. --}}
                                        {{ mb_strtoupper(mb_substr($t->name, 0, 1)) }}
                                    @endif
                                </span>
                                <span>
                                    <span class="kk-treview__name">{{ $t->name }}</span>
                                    @if($t->title || $t->product_name)
                                        <span class="kk-treview__meta">{{ $t->title ?: $t->product_name }}</span>
                                    @endif
                                </span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
        <style>
            .kk-testimonials__sub { color: var(--kk-text-muted); font-size: 14px; margin: 8px 0 0; }
            .kk-treview-grid {
                display: grid; gap: 16px;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            }
            .kk-treview {
                margin: 0; padding: 20px; border-radius: 6px;
                background: var(--kk-cream-lighter); border: 1px solid var(--kk-cream-dark);
                display: flex; flex-direction: column; gap: 12px;
            }
            .kk-treview__stars { display: flex; gap: 2px; color: var(--kk-tan-dark); }
            .kk-treview__stars svg { width: 16px; height: 16px; }
            .kk-treview__text {
                margin: 0; font-size: 14px; line-height: 1.6; color: var(--kk-text);
            }
            .kk-treview__by { display: flex; align-items: center; gap: 10px; margin-top: auto; }
            .kk-treview__avatar {
                width: 34px; height: 34px; flex: 0 0 34px; border-radius: 999px; overflow: hidden;
                display: flex; align-items: center; justify-content: center;
                background: var(--kk-brown); color: var(--kk-cream);
                font-size: 13px; font-weight: 600;
            }
            .kk-treview__avatar img { width: 100%; height: 100%; object-fit: cover; }
            .kk-treview__name { display: block; font-size: 13px; font-weight: 600; color: var(--kk-text); }
            .kk-treview__meta { display: block; font-size: 12px; color: var(--kk-text-muted); }
        </style>
        @endif

        {{-- ============================================
             NEWSLETTER
             The .kk-newsletter styles above were still in the page with no
             markup left to use them, so the only ways onto the list were the
             two popups - and each shows once per browser. A shopper who closed
             one had no way to subscribe at all.
             ============================================ --}}
        @php
            $newsletterSection = $sections['newsletter'] ?? null;
            // Same rule as About Us: a missing row still renders with the
            // defaults, only a row that exists and is switched off hides it.
            $newsletterVisible = $newsletterSection === null || $newsletterSection->is_active;
            $newsletterTitle   = ($newsletterSection->title ?? null) ?: 'Join the Karmaa Kulture List';
            $newsletterText    = ($newsletterSection->subtitle ?? null) ?: 'Early access to new drops, private sales and styling notes.';
            $newsletterButton  = ($newsletterSection->button_text ?? null) ?: 'Subscribe';
        @endphp
        @if($newsletterVisible)
        <section class="kk-newsletter" x-data="newsletterSignup()">
            <div class="container mx-auto px-4">
                <span class="kk-eyebrow">Stay in the Loop</span>
                <h2>{{ $newsletterTitle }}</h2>
                <p>{{ $newsletterText }}</p>

                <form @submit.prevent="submit()" novalidate class="kk-newsletter-form" x-show="!done">
                    <label for="kk-newsletter-email" class="sr-only">Email address</label>
                    <input id="kk-newsletter-email" type="email" x-model="email" required maxlength="255"
                           placeholder="Your email address" autocomplete="email">
                    <button type="submit" :disabled="submitting">
                        <span x-show="!submitting">{{ $newsletterButton }}</span>
                        <span x-show="submitting" x-cloak>Sending</span>
                    </button>
                </form>

                <p x-show="error" x-cloak x-text="error" role="alert"
                   style="margin: 12px 0 0; font-size: 13px; color: #b3261e;"></p>
                <p x-show="done" x-cloak x-text="message" role="status"
                   style="margin: 0; font-size: 14px; color: var(--kk-text);"></p>

                <p style="font-size: 11px; color: var(--kk-text-muted); margin: 14px 0 0;">
                    No spam - unsubscribe anytime.
                </p>
            </div>
        </section>
        @endif

    </div>

</x-layouts.app>
