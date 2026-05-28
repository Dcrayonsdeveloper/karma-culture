<x-layouts.app>
    <x-slot name="title">{{ $siteSettings['site_name'] }} - {{ $siteSettings['site_tagline'] }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $siteSettings['site_tagline'] }} - Shop curated fashion at {{ $siteSettings['site_name'] }}.">
        <link rel="canonical" href="{{ url('/') }}">
        <meta property="og:title" content="{{ $siteSettings['site_name'] }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
    @endpush

    <x-slot name="styles">
        <style>
            :root {
                --bh-ink: #1a1a1a;
                --bh-sub: #595959;
                --bh-line: #e8e5e0;
                --bh-bg: #ffffff;
                --bh-muted: #C9A27B;
                --bh-soft: #F2E4D2;
                --bh-accent: #3E2A1F;
            }

            .bh { font-family: 'Bricolage Grotesque', 'Inter', 'Poppins', sans-serif; color: var(--bh-ink); }
            .bh h1, .bh h2, .bh h3, .bh h4, .bh h5, .bh h6,
            .bh-hero-title, .bh-sec-title, .bh-craft-title,
            .bh-pour-price, .bh-feature-title, .bh-newsletter h2,
            .bh-about-title, .bh-app-title, .bh-quality-title {
                font-family: 'Bricolage Grotesque', 'DM Sans', sans-serif !important;
                text-transform: capitalize !important;
            }

            /* AAA focus rings for all interactive elements inside home */
            .bh a:focus-visible,
            .bh button:focus-visible,
            .bh input:focus-visible {
                outline: 3px solid #3E2A1F;
                outline-offset: 2px;
            }
            .bh-hero a:focus-visible,
            .bh-hero button:focus-visible,
            .bh-qualities a:focus-visible {
                outline: 3px solid #FAF5EF;
                outline-offset: 3px;
            }
            .sr-only {
                position: absolute; width: 1px; height: 1px;
                padding: 0; margin: -1px; overflow: hidden;
                clip: rect(0,0,0,0); white-space: nowrap; border: 0;
            }

            @media (prefers-reduced-motion: reduce) {
                .bh-marquee-track, .bh-hanger, .bh-cup-steam, .bh-dye-circle,
                .bh-cup-liquid, .bh-rail-item {
                    animation: none !important;
                }
            }

            /* ==== announcement ==== */
            .bh-announce {
                background: var(--bh-ink); color: #fff;
                text-align: center; padding: 10px 16px;
                font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase;
            }
            .bh-announce a { color: var(--bh-accent); text-decoration: underline; font-weight: 600; }

            /* ==== hero ==== */
            .bh-hero { position: relative; width: 100%; overflow: hidden; background: #F2E4D2; }
            .bh-hero-slides {
                position: relative;
                width: 100%;
                aspect-ratio: 1920 / 766;
            }
            .bh-hero-slide {
                position: absolute; inset: 0; opacity: 0;
                transition: opacity 0.8s ease;
            }
            .bh-hero-slide.active { opacity: 1; }
            .bh-hero-slide img {
                width: 100%; height: 100%;
                object-fit: contain;
                object-position: center;
                display: block;
            }
            .bh-hero-overlay {
                position: absolute; inset: 0;
                display: flex; align-items: center;
                padding: 0 8vw;
            }
            .bh-hero-copy { max-width: 520px; color: #fff; }
            .bh-hero-eyebrow {
                font-size: 12px; letter-spacing: 0.25em;
                text-transform: uppercase; margin-bottom: 18px;
                font-weight: 600;
            }
            .bh-hero-title {
                font-size: clamp(32px, 4.5vw, 64px);
                font-weight: 800; line-height: 1.02; letter-spacing: -0.02em;
                margin: 0 0 26px;
            }
            .bh-hero-cta {
                display: inline-flex; align-items: center; gap: 10px;
                padding: 14px 30px; background: #fff; color: var(--bh-ink);
                font-size: 12px; letter-spacing: 0.2em; text-transform: uppercase;
                font-weight: 700; text-decoration: none;
                transition: background 0.25s, color 0.25s;
            }
            .bh-hero-cta:hover { background: var(--bh-accent); color: #fff; }
            .bh-hero-dots {
                position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%);
                display: flex; gap: 8px; z-index: 5;
            }
            .bh-hero-dot {
                width: 32px; height: 3px; background: rgba(255,255,255,0.4);
                border: none; cursor: pointer; transition: background 0.3s;
            }
            .bh-hero-dot.active { background: #fff; }
            .bh-hero-arrow {
                position: absolute; top: 50%; transform: translateY(-50%); z-index: 5;
                width: 44px; height: 44px; border-radius: 50%;
                background: rgba(255,255,255,0.85); border: none; cursor: pointer;
                display: flex; align-items: center; justify-content: center;
                transition: background 0.25s;
            }
            .bh-hero-arrow:hover { background: #fff; }
            .bh-hero-arrow--prev { left: 24px; }
            .bh-hero-arrow--next { right: 24px; }

            /* ==== section ==== */
            .bh-section { padding: 40px 0; }
            .bh-container { max-width: 100%; margin: 0 auto; padding: 0 16px; }
            .bh-sec-head {
                display: flex; justify-content: space-between; align-items: flex-end;
                margin-bottom: 36px; gap: 20px;
            }
            .bh-sec-title {
                font-size: clamp(22px, 2.4vw, 32px); font-weight: 700;
                letter-spacing: 0.02em; text-transform: uppercase;
                margin: 0; color: var(--bh-ink);
            }
            .bh-sec-viewall {
                font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase;
                font-weight: 600; color: var(--bh-ink); text-decoration: none;
                border-bottom: 1px solid var(--bh-ink); padding-bottom: 3px;
            }
            .bh-sec-viewall:hover { color: var(--bh-accent); border-color: var(--bh-accent); }

            /* ==== category tiles ==== */
            .bh-tile-row {
                display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px;
            }
            .bh-tile {
                text-decoration: none; color: var(--bh-ink); text-align: center;
            }
            .bh-tile-img {
                aspect-ratio: 3/4; overflow: hidden; background: var(--bh-muted);
                margin-bottom: 10px;
            }
            .bh-tile-img img {
                width: 100%; height: 100%; object-fit: cover;
                transition: transform 0.5s;
            }
            .bh-tile:hover .bh-tile-img img { transform: scale(1.06); }
            .bh-tile-label {
                font-size: 12px; font-weight: 700; letter-spacing: 0.12em;
                text-transform: uppercase;
            }
            @media (max-width: 1100px) { .bh-tile-row { grid-template-columns: repeat(4, 1fr); } }
            @media (max-width: 640px) { .bh-tile-row { grid-template-columns: repeat(3, 1fr); } }

            /* ==== bottom wear tiles (6-col) ==== */
            .bh-tile-row-6 {
                display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px;
            }
            @media (max-width: 1100px) { .bh-tile-row-6 { grid-template-columns: repeat(3, 1fr); } }
            @media (max-width: 640px) { .bh-tile-row-6 { grid-template-columns: repeat(2, 1fr); } }

            /* ==== new arrivals grid (clean centered cards) ==== */
            .bh-na-grid {
                display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px;
            }
            .bh-na-card { text-decoration: none; display: block; }
            .bh-na-img {
                aspect-ratio: 3/4; overflow: hidden;
                background: #F2E4D2; margin-bottom: 16px;
            }
            .bh-na-img img {
                width: 100%; height: 100%; object-fit: cover; display: block;
                transition: transform 0.6s cubic-bezier(0.2,0.7,0.2,1);
            }
            .bh-na-card:hover .bh-na-img img { transform: scale(1.05); }
            .bh-na-rating {
                display: flex; align-items: center; justify-content: center;
                gap: 7px; margin-bottom: 9px; min-height: 18px;
            }
            .bh-na-stars { display: inline-flex; gap: 2px; }
            .bh-na-stars svg { width: 14px; height: 14px; }
            .bh-na-reviews { font-size: 13px; color: #8a8a8a; }
            .bh-na-name {
                text-align: center; font-size: 15.5px; line-height: 1.45;
                color: #3E2A1F; margin: 0 0 8px;
                display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;
                overflow: hidden;
                transition: color 0.2s;
            }
            .bh-na-card:hover .bh-na-name { color: #1a1a1a; }
            .bh-na-price { text-align: center; font-size: 14.5px; color: #8a8a8a; }
            .bh-na-cart {
                display: block; width: 100%; margin-top: 14px;
                padding: 12px 14px; background: #3E2A1F; color: #fff;
                border: none; cursor: pointer; border-radius: 0;
                font-family: inherit; font-size: 12px; font-weight: 700;
                letter-spacing: 0.16em; text-transform: uppercase;
                transition: background 0.2s;
            }
            .bh-na-cart:hover { background: #1a1a1a; }
            @media (max-width: 900px) { .bh-na-grid { grid-template-columns: repeat(2, 1fr); gap: 18px; } }
            @media (max-width: 460px) { .bh-na-grid { grid-template-columns: repeat(2, 1fr); } }

            /* ==== product slider ==== */
            .bh-slider-wrap { position: relative; }
            .bh-slider {
                display: flex; gap: 16px; overflow-x: auto;
                scroll-snap-type: x mandatory; scrollbar-width: none;
                -ms-overflow-style: none; scroll-behavior: smooth;
                padding-bottom: 4px;
            }
            .bh-slider::-webkit-scrollbar { display: none; }
            .bh-slider > * {
                flex: 0 0 calc((100% - 64px) / 5); scroll-snap-align: start;
            }
            .bh-arrow {
                position: absolute; top: 40%; transform: translateY(-50%); z-index: 3;
                width: 44px; height: 44px; border-radius: 50%;
                background: #fff; border: 1px solid var(--bh-line);
                display: flex; align-items: center; justify-content: center;
                cursor: pointer; color: var(--bh-ink);
                box-shadow: 0 4px 14px rgba(0,0,0,0.08);
                transition: all 0.2s;
            }
            .bh-arrow:hover { background: var(--bh-ink); color: #fff; }
            .bh-arrow--prev { left: -18px; }
            .bh-arrow--next { right: -18px; }
            .bh-arrow[disabled] { opacity: 0.3; pointer-events: none; }
            @media (max-width: 1100px) { .bh-slider > * { flex: 0 0 calc((100% - 32px) / 3); } }
            @media (max-width: 680px) { .bh-slider > * { flex: 0 0 calc((100% - 16px) / 2); } }

            /* ==== filter pills ==== */
            .bh-filter-row { margin-bottom: 28px; }
            .bh-filter-label {
                font-size: 13px; font-weight: 700; letter-spacing: 0.14em;
                text-transform: uppercase; margin-bottom: 12px; color: var(--bh-ink);
            }
            .bh-filter-pills { display: flex; flex-wrap: wrap; gap: 10px; }
            .bh-pill {
                padding: 10px 20px; border: 1px solid var(--bh-line);
                font-size: 12px; letter-spacing: 0.1em; text-transform: uppercase;
                font-weight: 600; background: #fff; color: var(--bh-ink);
                text-decoration: none; transition: all 0.2s;
                display: inline-flex; align-items: center; gap: 8px;
            }
            .bh-pill:hover { background: var(--bh-ink); color: #fff; border-color: var(--bh-ink); }
            .bh-swatch {
                display: inline-block; width: 14px; height: 14px; border-radius: 50%;
                border: 1px solid rgba(0,0,0,0.1);
            }

            /* ==== aesthetics/occasion tiles ==== */
            .bh-aesthetic-grid {
                display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
            }
            .bh-aesthetic-card {
                position: relative; overflow: hidden; aspect-ratio: 16/10;
                display: block; text-decoration: none; color: #fff;
            }
            .bh-aesthetic-card img {
                width: 100%; height: 100%; object-fit: cover;
                transition: transform 0.7s ease;
            }
            .bh-aesthetic-card:hover img { transform: scale(1.06); }
            .bh-aesthetic-card::after {
                content: ''; position: absolute; inset: 0;
                background: linear-gradient(to top, rgba(0,0,0,0.55), transparent 60%);
            }
            .bh-aesthetic-label {
                position: absolute; bottom: 24px; left: 24px; z-index: 2;
                font-size: 18px; font-weight: 700; letter-spacing: 0.12em;
                text-transform: uppercase;
            }
            @media (max-width: 900px) { .bh-aesthetic-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 560px) { .bh-aesthetic-grid { grid-template-columns: 1fr; } }

            .bh-occasion-grid {
                display: grid; grid-template-columns: 2fr 1fr 1fr;
                grid-template-rows: repeat(3, 200px); gap: 10px;
            }
            .bh-occasion-card {
                position: relative; overflow: hidden; display: block;
                text-decoration: none; color: #fff;
            }
            .bh-occasion-card img {
                width: 100%; height: 100%; object-fit: cover;
                transition: transform 0.7s ease;
            }
            .bh-occasion-card:hover img { transform: scale(1.06); }
            .bh-occasion-card::after {
                content: ''; position: absolute; inset: 0;
                background: linear-gradient(to top, rgba(0,0,0,0.5), transparent 55%);
            }
            .bh-occasion-label {
                position: absolute; bottom: 20px; left: 20px; z-index: 2;
                font-size: 15px; font-weight: 700; letter-spacing: 0.12em;
                text-transform: uppercase;
            }
            .bh-occasion-card:nth-child(1) { grid-row: span 2; }
            @media (max-width: 900px) {
                .bh-occasion-grid {
                    grid-template-columns: 1fr 1fr;
                    grid-template-rows: repeat(4, 180px);
                }
                .bh-occasion-card:nth-child(1) { grid-column: span 2; grid-row: span 1; }
            }

            /* Flat 4-column grid variant (used for Shop by Category — all parents) */
            .bh-occasion-grid--flat {
                grid-template-columns: repeat(4, 1fr);
                grid-template-rows: 280px;
                grid-auto-rows: 280px;
            }
            .bh-occasion-grid--flat .bh-occasion-card:nth-child(1) { grid-row: auto; }
            .bh-occasion-grid--flat .bh-occasion-card:nth-child(n+2) { grid-row: auto; }
            @media (max-width: 1024px) {
                .bh-occasion-grid--flat { grid-template-columns: repeat(3, 1fr); }
            }
            @media (max-width: 720px) {
                .bh-occasion-grid--flat {
                    grid-template-columns: repeat(2, 1fr);
                    grid-template-rows: 220px;
                    grid-auto-rows: 220px;
                }
            }
            @media (max-width: 420px) {
                .bh-occasion-grid--flat { grid-template-columns: 1fr; }
            }

            /* ===== MIXED variant — 2 horizontal stacked (left) + 2 vertical tall (right) =====
               Layout:
                 +-----------------+----+----+
                 |   1  Horizontal | 2  | 3  |
                 +-----------------+ V  | V  |
                 |   4  Horizontal | e  | e  |
                 +-----------------+----+----+
            */
            .bh-occasion-grid--mixed {
                grid-template-columns: 2fr 1fr 1fr;
                grid-template-rows: 240px 240px;
                gap: 12px;
            }
            .bh-occasion-grid--mixed > :nth-child(1) { grid-column: 1; grid-row: 1; }
            .bh-occasion-grid--mixed > :nth-child(2) { grid-column: 2; grid-row: 1 / 3; }
            .bh-occasion-grid--mixed > :nth-child(3) { grid-column: 3; grid-row: 1 / 3; }
            .bh-occasion-grid--mixed > :nth-child(4) { grid-column: 1; grid-row: 2; }
            @media (max-width: 900px) {
                .bh-occasion-grid--mixed {
                    grid-template-columns: 1fr 1fr;
                    grid-template-rows: 200px 200px 240px;
                }
                .bh-occasion-grid--mixed > :nth-child(1) { grid-column: 1 / 3; grid-row: 1; }
                .bh-occasion-grid--mixed > :nth-child(2) { grid-column: 1; grid-row: 2 / 4; }
                .bh-occasion-grid--mixed > :nth-child(3) { grid-column: 2; grid-row: 2 / 4; }
                .bh-occasion-grid--mixed > :nth-child(4) { grid-column: 1 / 3; grid-row: 4; }
                .bh-occasion-grid--mixed { grid-template-rows: 180px 180px 180px 180px; }
            }
            @media (max-width: 540px) {
                .bh-occasion-grid--mixed {
                    grid-template-columns: 1fr;
                    grid-template-rows: 200px 220px 220px 200px;
                }
                .bh-occasion-grid--mixed > :nth-child(1) { grid-column: 1; grid-row: 1; }
                .bh-occasion-grid--mixed > :nth-child(2) { grid-column: 1; grid-row: 2; }
                .bh-occasion-grid--mixed > :nth-child(3) { grid-column: 1; grid-row: 3; }
                .bh-occasion-grid--mixed > :nth-child(4) { grid-column: 1; grid-row: 4; }
            }

            /* ===== ZIGZAG variant — staggered alternating offsets ===== */
            .bh-occasion-grid--zigzag {
                grid-template-columns: repeat(4, 1fr);
                grid-auto-rows: 320px;
                row-gap: 24px;
                padding: 60px 0;
            }
            .bh-occasion-grid--zigzag .bh-occasion-card {
                transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.3, 1);
                border-radius: 6px;
            }
            .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(odd)  { transform: translateY(-32px); }
            .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(even) { transform: translateY(32px); }
            .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(odd):hover  { transform: translateY(-44px) scale(1.02); }
            .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(even):hover { transform: translateY(20px)  scale(1.02); }
            .bh-occasion-grid--zigzag .bh-occasion-card img { transition: transform 0.7s ease; }
            .bh-occasion-grid--zigzag .bh-occasion-card:hover img { transform: scale(1.08); }

            @media (max-width: 1024px) {
                .bh-occasion-grid--zigzag { grid-template-columns: repeat(3, 1fr); grid-auto-rows: 280px; }
            }
            @media (max-width: 720px) {
                .bh-occasion-grid--zigzag {
                    grid-template-columns: repeat(2, 1fr);
                    grid-auto-rows: 240px; row-gap: 16px;
                }
                .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(odd)  { transform: translateY(-16px); }
                .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(even) { transform: translateY(16px); }
                .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(odd):hover  { transform: translateY(-24px) scale(1.02); }
                .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(even):hover { transform: translateY(8px)   scale(1.02); }
            }
            @media (max-width: 420px) {
                .bh-occasion-grid--zigzag { grid-template-columns: 1fr; }
                .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(odd),
                .bh-occasion-grid--zigzag .bh-occasion-card:nth-child(even) { transform: none; }
            }

            /* Hero-split variant — 1 big + 2 stacked (used for Shop by Category) */
            .bh-occasion-grid--hero {
                grid-template-columns: 2fr 1fr;
                grid-template-rows: repeat(2, 240px);
            }
            .bh-occasion-grid--hero .bh-occasion-card:nth-child(1) { grid-row: 1 / span 2; }
            .bh-occasion-grid--hero .bh-occasion-card:nth-child(n+2) { grid-row: auto; }
            @media (max-width: 900px) {
                .bh-occasion-grid--hero {
                    grid-template-columns: 1fr;
                    grid-template-rows: 320px repeat(2, 200px);
                }
                .bh-occasion-grid--hero .bh-occasion-card:nth-child(1) { grid-column: auto; grid-row: auto; }
            }

            /* ==== about us ==== */
            .bh-about { background: #F3E9D7; }
            .bh-about-grid {
                display: flex; flex-direction: column;
                align-items: center; text-align: center;
                gap: 56px;
            }
            .bh-about-intro { max-width: 1100px; }
            .bh-about-title {
                font-size: clamp(28px, 3.6vw, 48px); font-weight: 800;
                letter-spacing: -0.02em; margin: 0 0 24px;
            }
            .bh-about-text {
                font-size: 18px; line-height: 1.85;
                font-weight: 500; color: var(--bh-ink);
                margin: 0 auto 32px;
                max-width: 980px;
            }
            .bh-about-grid .bh-feature-grid {
                grid-template-columns: repeat(4, 1fr);
                width: 100%;
                max-width: 1100px;
            }
            @media (max-width: 900px) {
                .bh-about-grid .bh-feature-grid { grid-template-columns: repeat(2, 1fr); }
            }
            .bh-feature-grid {
                display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;
            }
            .bh-feature-item {
                background: #fff; padding: 22px; text-align: center;
                border: 1px solid var(--bh-line);
            }
            .bh-feature-icon {
                width: 40px; height: 40px; margin: 0 auto 12px;
                color: var(--bh-accent);
            }
            .bh-feature-item h4 {
                font-size: 13px; font-weight: 700; letter-spacing: 0.08em;
                text-transform: uppercase; margin: 0;
            }
            @media (max-width: 900px) { .bh-about-grid { gap: 36px; } }

            /* ==== stores ==== */
            .bh-stores-grid {
                display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
            }
            .bh-store-card {
                background: #fff; border: 1px solid var(--bh-line);
                overflow: hidden; transition: box-shadow 0.25s;
            }
            .bh-store-card:hover { box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
            .bh-store-img {
                aspect-ratio: 4/3; overflow: hidden; background: var(--bh-muted);
            }
            .bh-store-img img { width: 100%; height: 100%; object-fit: cover; }
            .bh-store-body { padding: 18px; }
            .bh-store-name {
                font-size: 14px; font-weight: 700; letter-spacing: 0.06em;
                text-transform: uppercase; margin: 0 0 6px;
            }
            .bh-store-addr { font-size: 12px; color: var(--bh-sub); margin: 0 0 14px; line-height: 1.6; }
            .bh-store-links {
                display: flex; gap: 12px; font-size: 11px; letter-spacing: 0.12em;
                text-transform: uppercase; font-weight: 600;
            }
            .bh-store-links a { color: var(--bh-accent); text-decoration: none; }
            @media (max-width: 1100px) { .bh-stores-grid { grid-template-columns: repeat(3, 1fr); } }
            @media (max-width: 780px) { .bh-stores-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 520px) { .bh-stores-grid { grid-template-columns: 1fr; } }

            /* ==== our qualities ==== */
            .bh-qualities {
                background: var(--bh-ink); color: #fff;
                padding: 52px 0;
            }
            .bh-qualities .bh-craft-head { margin-bottom: 56px; }
            .bh-quality-grid {
                display: grid; grid-template-columns: repeat(3, 1fr);
                gap: 2px;
                list-style: none; padding: 0; margin: 0;
                background: rgba(255,255,255,0.12);
                border: 1px solid rgba(255,255,255,0.12);
            }
            .bh-quality-card {
                position: relative; background: var(--bh-ink);
                padding: 44px 32px 40px;
                transition: background 0.35s ease;
            }
            .bh-quality-card:hover,
            .bh-quality-card:focus-within {
                background: #2B1D15;
            }
            .bh-quality-card::before {
                content: ''; position: absolute; left: 0; top: 0; height: 3px;
                width: 0; background: var(--bh-muted);
                transition: width 0.45s cubic-bezier(0.2,0.8,0.3,1);
            }
            .bh-quality-card:hover::before,
            .bh-quality-card:focus-within::before { width: 100%; }
            .bh-quality-num {
                font-size: 14px; font-weight: 700; letter-spacing: 0.3em;
                color: var(--bh-muted); margin-bottom: 22px;
            }
            .bh-quality-icon {
                width: 48px; height: 48px; color: #fff;
                margin-bottom: 20px;
            }
            .bh-quality-icon svg { width: 100%; height: 100%; }
            .bh-quality-title {
                font-size: 22px; font-weight: 700; letter-spacing: -0.01em;
                color: #fff; margin: 0 0 14px;
            }
            .bh-quality-desc {
                font-size: 16px; line-height: 1.75;
                color: #eae3d8; margin: 0;
            }
            @media (max-width: 900px) {
                .bh-quality-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 560px) {
                .bh-quality-grid { grid-template-columns: 1fr; }
            }

            /* ==== newsletter ==== */
            .bh-newsletter {
                padding: 40px 16px; text-align: center;
                background: #F3E9D7;
            }
            .bh-newsletter h2 {
                font-size: clamp(26px, 3.2vw, 40px); font-weight: 800;
                letter-spacing: -0.02em; margin: 0 0 12px;
            }
            .bh-newsletter p { font-size: 14px; color: var(--bh-sub); margin: 0 0 28px; }
            .bh-newsletter-form {
                display: flex; max-width: 480px; margin: 0 auto;
                border: 1px solid var(--bh-ink); background: #fff;
            }
            .bh-newsletter-form input {
                flex: 1; padding: 14px 18px; border: none; outline: none;
                font-size: 14px; background: transparent;
            }
            .bh-newsletter-form button {
                padding: 14px 26px; background: var(--bh-ink); color: #fff;
                border: none; cursor: pointer; font-size: 12px; font-weight: 700;
                letter-spacing: 0.18em; text-transform: uppercase;
            }
            .bh-newsletter-form button:hover { background: var(--bh-accent); }

            /* ====================================================
               CREATIVE FILTER SECTIONS (Size / Price / Color)
               ==================================================== */
            .bh-craft-head { text-align: center; margin-bottom: 56px; }
            .bh-craft-eyebrow {
                font-size: 14px; letter-spacing: 0.32em; text-transform: uppercase;
                color: var(--bh-muted); font-weight: 700; margin-bottom: 16px;
            }
            .bh-craft-title {
                font-size: clamp(34px, 4.2vw, 56px); font-weight: 800;
                letter-spacing: -0.02em; margin: 0;
            }
            .bh-craft-title em {
                font-family: 'Rustic Roadway', 'Rye', 'Bricolage Grotesque', serif;
                font-style: normal; color: var(--bh-accent); font-weight: 400;
                letter-spacing: 0;
            }
            .bh-craft-sub {
                font-size: 17px; color: var(--bh-sub); margin: 16px auto 0;
                max-width: 640px; line-height: 1.7;
            }

            /* ========== Curate the Edit — Tabbed Wrapper ========== */
            .bh-edit {
                background: linear-gradient(180deg, #FAF5EF 0%, #F2E4D2 100%);
                padding: 48px 0 56px;
                position: relative;
                overflow: hidden;
            }
            .bh-edit::before,
            .bh-edit::after {
                content: ''; position: absolute;
                width: 320px; height: 320px; border-radius: 50%;
                background: radial-gradient(circle, rgba(201,162,123,0.22) 0%, transparent 65%);
                filter: blur(20px); pointer-events: none;
            }
            .bh-edit::before { top: -80px; left: -80px; }
            .bh-edit::after  { bottom: -100px; right: -60px; }
            .bh-edit > .bh-container { position: relative; z-index: 1; }

            .bh-edit-tabs {
                display: inline-flex; margin: 0 auto 48px;
                background: rgba(255,255,255,0.6);
                border: 1px solid var(--bh-line);
                border-radius: 999px;
                padding: 6px;
                backdrop-filter: blur(10px);
                box-shadow: 0 6px 20px rgba(62,42,31,0.06);
                position: relative; left: 50%; transform: translateX(-50%);
                gap: 4px;
            }
            .bh-edit-tab {
                position: relative;
                display: inline-flex; flex-direction: column; align-items: center;
                gap: 1px; padding: 12px 32px;
                background: transparent; border: none; cursor: pointer;
                border-radius: 999px;
                color: var(--bh-sub); font-family: inherit;
                transition: color 0.35s, background 0.35s;
                min-width: 120px;
            }
            .bh-edit-tab:hover { color: var(--bh-ink); }
            .bh-edit-tab-sub {
                font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase;
                font-weight: 500; opacity: 0.75;
                transition: opacity 0.3s;
            }
            .bh-edit-tab-label {
                font-size: 15px; font-weight: 700; letter-spacing: 0.04em;
            }
            .bh-edit-tab.is-active {
                background: var(--bh-accent); color: #fff;
                box-shadow: 0 10px 24px rgba(62,42,31,0.25);
            }
            .bh-edit-tab.is-active .bh-edit-tab-sub { opacity: 0.85; }

            .bh-edit-panel {
                position: relative;
            }
            .bh-fade-enter { transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.2,0.8,0.3,1); }
            .bh-fade-start { opacity: 0; transform: translateY(14px); }
            .bh-fade-end   { opacity: 1; transform: translateY(0); }

            @media (max-width: 640px) {
                .bh-edit-tabs {
                    display: flex; width: calc(100% - 32px);
                    margin-bottom: 32px;
                }
                .bh-edit-tab {
                    min-width: 0; flex: 1 1 0;
                    padding: 12px 6px; gap: 0;
                    justify-content: center;
                }
                /* hide the small caption on mobile — keep just Size / Price / Shade */
                .bh-edit-tab-sub { display: none; }
                .bh-edit-tab-label { font-size: 14px; letter-spacing: 0.06em; }
            }

            /* ========== Shop by Size — The Wardrobe Rail ========== */
            .bh-rail-section {
                background: linear-gradient(180deg, #FAF5EF 0%, #F2E4D2 100%);
                padding: 52px 0 40px;
                overflow: hidden;
            }
            .bh-rail {
                position: relative; max-width: 1200px; margin: 0 auto;
                padding: 0 32px 32px;
            }
            .bh-rail-bar {
                position: absolute; top: 70px; left: 5%; right: 5%; height: 6px;
                background: linear-gradient(180deg, #5E3A26 0%, #3E2A1F 100%);
                border-radius: 3px;
                box-shadow: 0 3px 8px rgba(62,42,31,0.25);
            }
            .bh-rail-bar::before, .bh-rail-bar::after {
                content: ''; position: absolute; top: -12px;
                width: 18px; height: 30px;
                background: #3E2A1F; border-radius: 4px;
            }
            .bh-rail-bar::before { left: -14px; }
            .bh-rail-bar::after { right: -14px; }
            .bh-rail-grid {
                display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px;
                padding-top: 30px;
            }
            .bh-rail-item {
                position: relative; display: flex; flex-direction: column; align-items: center;
                text-decoration: none; color: var(--bh-ink);
                transform: translateY(-20px); opacity: 0;
                animation: bhDrop 0.7s cubic-bezier(0.2,0.8,0.3,1.2) forwards;
                cursor: pointer;
            }
            .bh-rail-item:nth-child(1) { animation-delay: 0.05s; }
            .bh-rail-item:nth-child(2) { animation-delay: 0.15s; }
            .bh-rail-item:nth-child(3) { animation-delay: 0.25s; }
            .bh-rail-item:nth-child(4) { animation-delay: 0.35s; }
            .bh-rail-item:nth-child(5) { animation-delay: 0.45s; }
            .bh-rail-item:nth-child(6) { animation-delay: 0.55s; }
            @keyframes bhDrop {
                0% { transform: translateY(-60px); opacity: 0; }
                60% { transform: translateY(6px); opacity: 1; }
                100% { transform: translateY(0); opacity: 1; }
            }
            .bh-hanger {
                width: 100%; display: flex; justify-content: center;
                transform-origin: 50% 10px;
                animation: bhSway 4s ease-in-out infinite;
            }
            .bh-rail-item:nth-child(even) .bh-hanger { animation-delay: 2s; }
            @keyframes bhSway {
                0%, 100% { transform: rotate(-1.5deg); }
                50% { transform: rotate(1.5deg); }
            }
            .bh-rail-item svg { width: 100%; max-width: 140px; transition: transform 0.3s; }
            .bh-rail-item:hover .bh-hanger {
                animation: bhSwing 0.6s ease-in-out;
            }
            @keyframes bhSwing {
                0% { transform: rotate(0); }
                25% { transform: rotate(-8deg); }
                50% { transform: rotate(6deg); }
                75% { transform: rotate(-3deg); }
                100% { transform: rotate(0); }
            }
            .bh-rail-item:hover svg { transform: translateY(-4px) scale(1.03); }
            .bh-rail-item:hover .bh-rail-tee { filter: drop-shadow(0 8px 20px rgba(62,42,31,0.25)); }
            .bh-rail-tee { transition: filter 0.3s; }
            .bh-size-label {
                margin-top: 10px;
                font-size: clamp(15px, 1.4vw, 22px);
                font-weight: 800; letter-spacing: 0.08em;
                color: var(--bh-accent);
            }
            .bh-size-count {
                font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase;
                color: var(--bh-sub); margin-top: 2px;
            }
            @media (max-width: 780px) {
                .bh-rail-grid { grid-template-columns: repeat(3, 1fr); gap: 20px; }
                .bh-rail-bar { display: none; }
            }

            /* ========== Shop by Price — Perfectly Portioned ========== */
            .bh-pour-section {
                padding: 52px 0;
                background: #fff;
                position: relative;
            }
            .bh-pour-grid {
                display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
                max-width: 1100px; margin: 0 auto; padding: 0 32px;
            }
            .bh-pour-card {
                position: relative;
                background: linear-gradient(180deg, #FAF5EF 0%, #F2E4D2 100%);
                border: 1px solid var(--bh-line);
                padding: 48px 32px 40px;
                text-decoration: none; color: var(--bh-ink);
                text-align: center;
                overflow: hidden;
                transition: transform 0.4s cubic-bezier(0.2,0.8,0.3,1.2), box-shadow 0.4s;
            }
            .bh-pour-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 22px 50px rgba(62,42,31,0.18);
            }
            .bh-pour-card::after {
                content: ''; position: absolute; inset: 0;
                background: radial-gradient(circle at 50% 120%, rgba(62,42,31,0.08), transparent 50%);
                pointer-events: none;
            }
            .bh-cup {
                position: relative;
                width: 110px; height: 120px; margin: 0 auto 24px;
            }
            .bh-cup-steam {
                position: absolute; left: 50%; top: -40px;
                width: 3px; height: 36px; background: rgba(62,42,31,0.25);
                border-radius: 3px; filter: blur(2px);
                transform: translateX(-50%);
                animation: bhSteam 3s ease-in-out infinite;
            }
            .bh-cup-steam:nth-child(1) { left: 42%; animation-delay: 0s; }
            .bh-cup-steam:nth-child(2) { left: 50%; animation-delay: 0.6s; height: 44px; top: -46px; }
            .bh-cup-steam:nth-child(3) { left: 58%; animation-delay: 1.2s; }
            @keyframes bhSteam {
                0% { opacity: 0; transform: translate(-50%, 10px) scaleY(0.6); }
                30% { opacity: 0.7; }
                100% { opacity: 0; transform: translate(-50%, -30px) scaleY(1.4) scaleX(1.4); }
            }
            .bh-cup-body {
                position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
                width: 90px; height: 100px;
                background: #fff;
                border: 3px solid var(--bh-accent);
                border-radius: 8px 8px 14px 14px;
                overflow: hidden;
            }
            .bh-cup-handle {
                position: absolute; right: -18px; top: 20px;
                width: 22px; height: 40px;
                border: 3px solid var(--bh-accent);
                border-left: none;
                border-radius: 0 24px 24px 0;
            }
            .bh-cup-liquid {
                position: absolute; bottom: 0; left: 0; right: 0;
                height: 0;
                background: linear-gradient(180deg, #5E3A26 0%, #3E2A1F 100%);
                animation: bhPour 1.8s cubic-bezier(0.3,0.8,0.3,1) 0.4s forwards;
            }
            .bh-cup-liquid::before {
                content: ''; position: absolute; top: -4px; left: 0; right: 0; height: 8px;
                background: var(--bh-muted);
                opacity: 0.85;
            }
            @keyframes bhPour {
                0% { height: 0; }
                100% { height: var(--fill, 50%); }
            }
            .bh-pour-card:nth-child(1) .bh-cup-liquid { --fill: 35%; }
            .bh-pour-card:nth-child(2) .bh-cup-liquid { --fill: 60%; }
            .bh-pour-card:nth-child(3) .bh-cup-liquid { --fill: 85%; }
            .bh-pour-tier {
                font-size: 10px; letter-spacing: 0.3em; text-transform: uppercase;
                color: var(--bh-muted); font-weight: 700; margin-bottom: 6px;
            }
            .bh-pour-price {
                font-size: clamp(24px, 2.6vw, 34px); font-weight: 800;
                letter-spacing: -0.02em; margin: 0 0 8px;
            }
            .bh-pour-price span { color: var(--bh-muted); font-weight: 500; }
            .bh-pour-desc {
                font-size: 13px; color: var(--bh-sub); margin: 0 0 20px;
                line-height: 1.6;
            }
            .bh-pour-cta {
                display: inline-flex; align-items: center; gap: 8px;
                font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase;
                font-weight: 700; color: var(--bh-accent);
                border-bottom: 1.5px solid var(--bh-accent); padding-bottom: 3px;
                transition: gap 0.3s;
            }
            .bh-pour-card:hover .bh-pour-cta { gap: 14px; }
            @media (max-width: 780px) { .bh-pour-grid { grid-template-columns: 1fr; } }

            /* ========== Shop by Color — The Dye Lab ========== */
            .bh-dye-section {
                background: linear-gradient(180deg, #F2E4D2 0%, #FAF5EF 100%);
                padding: 52px 0 64px;
                position: relative;
                overflow: hidden;
            }
            .bh-dye-grid {
                display: grid; grid-template-columns: repeat(5, 1fr);
                gap: 40px 28px;
                max-width: 1200px; margin: 0 auto; padding: 0 32px;
            }
            .bh-dye-chip {
                position: relative; display: flex; flex-direction: column;
                align-items: center; text-decoration: none; color: var(--bh-ink);
                cursor: pointer;
            }
            .bh-dye-circle {
                position: relative;
                width: 120px; height: 120px; border-radius: 50%;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(62,42,31,0.15);
                transition: transform 0.5s cubic-bezier(0.2,0.8,0.3,1.2), box-shadow 0.4s;
                animation: bhBreathe 5s ease-in-out infinite;
            }
            .bh-dye-chip:nth-child(odd) .bh-dye-circle { animation-delay: 1s; }
            .bh-dye-chip:nth-child(3n) .bh-dye-circle { animation-delay: 2s; }
            @keyframes bhBreathe {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.04); }
            }
            .bh-dye-chip:hover .bh-dye-circle {
                transform: scale(1.12);
                box-shadow: 0 20px 46px rgba(62,42,31,0.28);
                animation-play-state: paused;
            }
            .bh-dye-circle::before {
                content: ''; position: absolute; inset: 0; border-radius: 50%;
                background: inherit;
                mix-blend-mode: multiply;
            }
            .bh-dye-circle::after {
                content: ''; position: absolute; inset: -4px; border-radius: 50%;
                border: 1px solid rgba(255,255,255,0.25);
                background: radial-gradient(circle at 30% 25%, rgba(255,255,255,0.25), transparent 40%);
                opacity: 0; transition: opacity 0.4s;
            }
            .bh-dye-chip:hover .bh-dye-circle::after { opacity: 1; }
            .bh-dye-ripple {
                position: absolute; inset: 0; border-radius: 50%;
                border: 2px solid rgba(255,255,255,0.6);
                transform: scale(0.8); opacity: 0;
                pointer-events: none;
            }
            .bh-dye-chip:hover .bh-dye-ripple {
                animation: bhRipple 1.4s ease-out infinite;
            }
            @keyframes bhRipple {
                0% { transform: scale(0.8); opacity: 0.7; }
                100% { transform: scale(1.45); opacity: 0; }
            }
            .bh-dye-fabric {
                position: absolute; inset: 0;
                background-size: cover; background-position: center;
                opacity: 0.35;
                mix-blend-mode: overlay;
            }
            .bh-dye-name {
                margin-top: 18px;
                font-size: 13px; font-weight: 700; letter-spacing: 0.12em;
                text-transform: uppercase;
                position: relative;
            }
            .bh-dye-name::after {
                content: ''; position: absolute; left: 50%; bottom: -6px;
                width: 0; height: 1.5px; background: var(--bh-accent);
                transform: translateX(-50%); transition: width 0.35s cubic-bezier(0.2,0.8,0.3,1);
            }
            .bh-dye-chip:hover .bh-dye-name::after { width: 34px; }
            .bh-dye-alias {
                font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase;
                color: var(--bh-sub); margin-top: 16px;
                opacity: 0; transform: translateY(4px);
                transition: opacity 0.3s, transform 0.3s;
            }
            .bh-dye-chip:hover .bh-dye-alias {
                opacity: 1; transform: translateY(0);
            }
            @media (max-width: 900px) { .bh-dye-grid { grid-template-columns: repeat(3, 1fr); } }
            @media (max-width: 560px) {
                .bh-dye-grid { grid-template-columns: repeat(2, 1fr); gap: 28px 20px; }
                .bh-dye-circle { width: 90px; height: 90px; }
            }
        </style>
    </x-slot>

    <div class="bh">

        {{-- ==================== HERO ==================== --}}
        @php
            $heroSlides = collect([
                (object)[
                    'eyebrow' => '',
                    'title' => '',
                    'cta' => 'Shop Now',
                    'url' => route('new-arrivals'),
                    'image' => asset('images/Web_banner_1920_x_766.webp'),
                ],
            ]);
        @endphp

        <section class="bh-hero" x-data="bhHero({{ $heroSlides->count() }})" x-init="init()" aria-label="Featured collections">
            <h1 class="sr-only">{{ $siteSettings['site_name'] }} — {{ $siteSettings['site_tagline'] }}</h1>
            <div class="bh-hero-slides">
                @foreach($heroSlides as $i => $slide)
                    <div class="bh-hero-slide" :class="active === {{ $i }} ? 'active' : ''" @if($i===0) style="opacity:1" @endif>
                        <img src="{{ $slide->image }}" alt="{{ $slide->title ?: 'Banner' }}" loading="{{ $i===0?'eager':'lazy' }}">
                        @if($slide->title || $slide->eyebrow)
                            <div class="bh-hero-overlay">
                                <div class="bh-hero-copy">
                                    @if($slide->eyebrow)
                                        <div class="bh-hero-eyebrow">{{ $slide->eyebrow }}</div>
                                    @endif
                                    @if($slide->title)
                                        <h2 class="bh-hero-title">{{ $slide->title }}</h2>
                                    @endif
                                    <a href="{{ $slide->url }}" class="bh-hero-cta">
                                        {{ $slide->cta }}
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        @else
                            <a href="{{ $slide->url }}" class="bh-hero-link-full" aria-label="Shop now" style="position:absolute;inset:0;"></a>
                        @endif
                    </div>
                @endforeach
            </div>
            @if($heroSlides->count() > 1)
                <button type="button" class="bh-hero-arrow bh-hero-arrow--prev" @click="prev()" aria-label="Previous">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button type="button" class="bh-hero-arrow bh-hero-arrow--next" @click="next()" aria-label="Next">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
                <div class="bh-hero-dots">
                    @foreach($heroSlides as $i => $_)
                        <button type="button" class="bh-hero-dot" :class="active === {{ $i }} ? 'active' : ''" @click="go({{ $i }})" aria-label="Slide {{ $i+1 }}"></button>
                    @endforeach
                </div>
            @endif
        </section>


        {{-- ==================== CURATE THE EDIT — tabbed Size / Price / Shade ==================== --}}
        @php
            $sizes = [
                ['label' => 'S',   'w' => 68,  'h' => 88,  'count' => '120 styles'],
                ['label' => 'M',   'w' => 78,  'h' => 96,  'count' => '210 styles'],
                ['label' => 'L',   'w' => 88,  'h' => 102, 'count' => '185 styles'],
                ['label' => 'XL',  'w' => 98,  'h' => 108, 'count' => '140 styles'],
                ['label' => 'XXL', 'w' => 108, 'h' => 114, 'count' => '85 styles'],
                ['label' => '3XL', 'w' => 118, 'h' => 118, 'count' => '42 styles'],
            ];
            $tiers = [
                ['tier' => 'Everyday',  'max' => 1499, 'desc' => 'Staples on repeat — tees, polos, daily basics.',       'count' => 120],
                ['tier' => 'Weekender', 'max' => 1999, 'desc' => 'Elevated essentials. Shirts, jeans, the good kind.',   'count' => 180],
                ['tier' => 'Statement', 'max' => 2999, 'desc' => 'Jackets and pieces that anchor a wardrobe.',           'count' => 95],
            ];
            $shades = [
                ['name' => 'Espresso',   'alias' => 'Black',  'hex' => '#1a1410', 'fabric' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=400&auto=format&fit=crop'],
                ['name' => 'Mocha',      'alias' => 'Brown',  'hex' => '#3E2A1F', 'fabric' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&auto=format&fit=crop'],
                ['name' => 'Camel',      'alias' => 'Tan',    'hex' => '#9a6d42', 'fabric' => 'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=400&auto=format&fit=crop'],
                ['name' => 'Cappuccino', 'alias' => 'Beige',  'hex' => '#C9A27B', 'fabric' => 'https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=400&auto=format&fit=crop'],
                ['name' => 'Oat',        'alias' => 'Cream',  'hex' => '#e8d7b8', 'fabric' => 'https://images.unsplash.com/photo-1542060748-10c28b62716f?w=400&auto=format&fit=crop'],
                ['name' => 'Ink',        'alias' => 'Navy',   'hex' => '#1e2a47', 'fabric' => 'https://images.unsplash.com/photo-1572495532854-0a67e8b44ed8?w=400&auto=format&fit=crop'],
                ['name' => 'Sage',       'alias' => 'Green',  'hex' => '#6b7a5b', 'fabric' => 'https://images.unsplash.com/photo-1594938374182-a57061d1d1b5?w=400&auto=format&fit=crop'],
                ['name' => 'Terracotta', 'alias' => 'Rust',   'hex' => '#b06840', 'fabric' => 'https://images.unsplash.com/photo-1520975916090-3105956dac38?w=400&auto=format&fit=crop'],
                ['name' => 'Rosé',       'alias' => 'Pink',   'hex' => '#d8a796', 'fabric' => 'https://images.unsplash.com/photo-1527719327859-c6ce80353573?w=400&auto=format&fit=crop'],
                ['name' => 'Slate',      'alias' => 'Grey',   'hex' => '#7a7a7a', 'fabric' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=400&auto=format&fit=crop'],
            ];
            $edit_tabs = [
                ['key' => 'size',  'label' => 'Size',  'sub' => 'Find your fit',       'blurb' => 'Pick a size off the rail — every cut is tailored for a flattering drape.'],
                ['key' => 'price', 'label' => 'Price', 'sub' => 'Perfectly portioned', 'blurb' => 'Three tiers, no watered-down compromises. Find your pour.'],
                ['key' => 'shade', 'label' => 'Shade', 'sub' => 'The dye lab',         'blurb' => 'Hand-dyed palettes inspired by coffee, clay and forest floor.'],
            ];
        @endphp

        <section class="bh-edit" aria-labelledby="edit-heading"
                 x-data="{ tab: 'size' }"
                 @keydown.arrow-right.prevent="tab = tab === 'size' ? 'price' : (tab === 'price' ? 'shade' : 'size')"
                 @keydown.arrow-left.prevent="tab = tab === 'shade' ? 'price' : (tab === 'price' ? 'size' : 'shade')">
            <div class="bh-container">
                <div class="bh-craft-head">
                    <div class="bh-craft-eyebrow">Curate the edit</div>
                    <h2 id="edit-heading" class="bh-craft-title">Shop it your <em>way</em></h2>
                    <p class="bh-craft-sub" x-text="{
                            size: '{{ $edit_tabs[0]['blurb'] }}',
                            price: '{{ $edit_tabs[1]['blurb'] }}',
                            shade: '{{ $edit_tabs[2]['blurb'] }}'
                        }[tab]"></p>
                </div>

                <div class="bh-edit-tabs" role="tablist" aria-label="Filter by">
                    @foreach($edit_tabs as $t)
                        <button type="button"
                                role="tab"
                                :aria-selected="tab === '{{ $t['key'] }}'"
                                :tabindex="tab === '{{ $t['key'] }}' ? 0 : -1"
                                :class="{ 'is-active': tab === '{{ $t['key'] }}' }"
                                @click="tab = '{{ $t['key'] }}'"
                                class="bh-edit-tab"
                                id="tab-{{ $t['key'] }}"
                                aria-controls="panel-{{ $t['key'] }}">
                            <span class="bh-edit-tab-sub">{{ $t['sub'] }}</span>
                            <span class="bh-edit-tab-label">{{ $t['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- SIZE PANEL --}}
                <div class="bh-edit-panel"
                     id="panel-size"
                     role="tabpanel"
                     aria-labelledby="tab-size"
                     x-show="tab === 'size'"
                     x-transition:enter="bh-fade-enter"
                     x-transition:enter-start="bh-fade-start"
                     x-transition:enter-end="bh-fade-end"
                     x-cloak>
                    <div class="bh-rail">
                        <div class="bh-rail-bar" aria-hidden="true"></div>
                        <div class="bh-rail-grid">
                            @foreach($sizes as $s)
                                <a href="{{ route('products.index') }}?size={{ $s['label'] }}" class="bh-rail-item" aria-label="Shop size {{ $s['label'] }}">
                                    <div class="bh-hanger">
                                        <svg viewBox="0 0 140 180" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M70 6 C 70 -2, 80 -2, 80 8 L 80 18" stroke="#3E2A1F" stroke-width="2.4" fill="none" stroke-linecap="round"/>
                                            <path d="M30 40 L 70 22 L 110 40" stroke="#3E2A1F" stroke-width="2.6" fill="none" stroke-linejoin="round" stroke-linecap="round"/>
                                            @php
                                                $cx = 70; $halfW = $s['w']/2; $shoulderY = 40; $hemY = 40 + $s['h'];
                                                $sleeveX = $halfW + 10; $sleeveY = 70;
                                            @endphp
                                            <path class="bh-rail-tee"
                                                  d="M {{ $cx - $halfW }} {{ $shoulderY }}
                                                     L {{ $cx - $sleeveX }} {{ $sleeveY }}
                                                     L {{ $cx - $halfW + 6 }} {{ $sleeveY + 10 }}
                                                     L {{ $cx - $halfW + 6 }} {{ $hemY }}
                                                     L {{ $cx + $halfW - 6 }} {{ $hemY }}
                                                     L {{ $cx + $halfW - 6 }} {{ $sleeveY + 10 }}
                                                     L {{ $cx + $sleeveX }} {{ $sleeveY }}
                                                     L {{ $cx + $halfW }} {{ $shoulderY }}
                                                     Q {{ $cx }} {{ $shoulderY - 6 }} {{ $cx - $halfW }} {{ $shoulderY }}
                                                     Z"
                                                  fill="{{ ['#C9A27B','#D4B08C','#8B5A3C','#5E3A26','#3E2A1F','#2B1D15'][$loop->index] }}"
                                                  stroke="#3E2A1F" stroke-width="1.2" stroke-linejoin="round"/>
                                            <path d="M {{ $cx - 10 }} {{ $shoulderY + 2 }} Q {{ $cx }} {{ $shoulderY + 8 }} {{ $cx + 10 }} {{ $shoulderY + 2 }}"
                                                  stroke="#FAF5EF" stroke-width="1.4" fill="none" opacity="0.7"/>
                                        </svg>
                                    </div>
                                    <div class="bh-size-label">{{ $s['label'] }}</div>
                                    <div class="bh-size-count">{{ $s['count'] }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- PRICE PANEL --}}
                <div class="bh-edit-panel"
                     id="panel-price"
                     role="tabpanel"
                     aria-labelledby="tab-price"
                     x-show="tab === 'price'"
                     x-transition:enter="bh-fade-enter"
                     x-transition:enter-start="bh-fade-start"
                     x-transition:enter-end="bh-fade-end"
                     x-cloak>
                    <div class="bh-pour-grid">
                        @foreach($tiers as $t)
                            <a href="{{ route('products.index') }}?max_price={{ $t['max'] }}" class="bh-pour-card">
                                <div class="bh-cup">
                                    <span class="bh-cup-steam"></span>
                                    <span class="bh-cup-steam"></span>
                                    <span class="bh-cup-steam"></span>
                                    <div class="bh-cup-body"><div class="bh-cup-liquid"></div></div>
                                    <div class="bh-cup-handle"></div>
                                </div>
                                <div class="bh-pour-tier">{{ $t['tier'] }}</div>
                                <h3 class="bh-pour-price">Under ₹{{ number_format($t['max']) }}<span>.00</span></h3>
                                <p class="bh-pour-desc">{{ $t['desc'] }}</p>
                                <span class="bh-pour-cta">
                                    Shop {{ $t['count'] }} styles
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- SHADE PANEL --}}
                <div class="bh-edit-panel"
                     id="panel-shade"
                     role="tabpanel"
                     aria-labelledby="tab-shade"
                     x-show="tab === 'shade'"
                     x-transition:enter="bh-fade-enter"
                     x-transition:enter-start="bh-fade-start"
                     x-transition:enter-end="bh-fade-end"
                     x-cloak>
                    <div class="bh-dye-grid">
                        @foreach($shades as $c)
                            <a href="{{ route('products.index') }}?color={{ strtolower($c['alias']) }}" class="bh-dye-chip">
                                <div class="bh-dye-circle" style="background: {{ $c['hex'] }};">
                                    <div class="bh-dye-fabric" style="background-image: url('{{ $c['fabric'] }}');"></div>
                                    <div class="bh-dye-ripple"></div>
                                </div>
                                <div class="bh-dye-name">{{ $c['name'] }}</div>
                                <div class="bh-dye-alias">{{ $c['alias'] }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ==================== BOTTOM WEAR (hidden) ==================== --}}
        @if(false)
        <section class="bh-section" style="padding-top: 0;">
            <div class="bh-container">
                <div class="bh-sec-head">
                    <h2 class="bh-sec-title">Bottom Wear</h2>
                </div>
                <div class="bh-tile-row-6">
                    @php
                        $bottomwear = [
                            ['label' => 'Jeans', 'img' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=400&auto=format&fit=crop'],
                            ['label' => 'Trousers', 'img' => 'https://images.unsplash.com/photo-1473966968600-fa801b9114d0?w=400&auto=format&fit=crop'],
                            ['label' => 'Cargos', 'img' => 'https://images.unsplash.com/photo-1584865288642-42078afe6942?w=400&auto=format&fit=crop'],
                            ['label' => 'Shorts', 'img' => 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=400&auto=format&fit=crop'],
                            ['label' => 'Trunks', 'img' => 'https://images.unsplash.com/photo-1622445275649-1c56eb8bb31d?w=400&auto=format&fit=crop'],
                            ['label' => 'Boxers', 'img' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=400&auto=format&fit=crop'],
                        ];
                    @endphp
                    @foreach($bottomwear as $cat)
                        <a href="{{ route('products.index') }}?category={{ strtolower($cat['label']) }}" class="bh-tile">
                            <div class="bh-tile-img"><img src="{{ $cat['img'] }}" alt="{{ $cat['label'] }}" loading="lazy"></div>
                            <div class="bh-tile-label">{{ $cat['label'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ==================== NEW ARRIVALS ==================== --}}
        @if($newArrivals->count())
        <section class="bh-section" style="padding-top: 0;">
            <div class="bh-container">
                <div class="bh-sec-head">
                    <h2 class="bh-sec-title">New Arrivals</h2>
                    <a href="{{ route('new-arrivals') }}" class="bh-sec-viewall">View All</a>
                </div>
                <div class="bh-na-grid">
                    @foreach($newArrivals->take(4) as $product)
                        @php
                            $naRating = (float) ($product->rating ?? 0);
                            $naReviews = (int) ($product->review_count ?? 0);
                        @endphp
                        <a href="{{ route('product.show', $product) }}" class="bh-na-card">
                            <div class="bh-na-img">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" loading="lazy"
                                     onerror="this.src='{{ asset('images/no-product-image.svg') }}'">
                            </div>
                            @if($naReviews > 0)
                                <div class="bh-na-rating">
                                    <span class="bh-na-stars">
                                        @for($s = 1; $s <= 5; $s++)
                                            <svg viewBox="0 0 20 20" fill="{{ $s <= round($naRating) ? '#1a1a1a' : '#d4cfc6' }}">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </span>
                                    <span class="bh-na-reviews">{{ $naReviews }} {{ Str::plural('review', $naReviews) }}</span>
                                </div>
                            @else
                                <div class="bh-na-rating"></div>
                            @endif
                            <div class="bh-na-name">{{ $product->name }}</div>
                            <div class="bh-na-price">@price($product->price)</div>
                            <button type="button" class="bh-na-cart"
                                    @click.prevent.stop="$store.cart.add({{ $product->id }})">
                                Add to Cart
                            </button>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ==================== BESTSELLERS ==================== --}}
        @if($bestsellers->count())
        <section class="bh-section" style="padding-top: 0;">
            <div class="bh-container">
                <div class="bh-sec-head">
                    <h2 class="bh-sec-title">Bestsellers</h2>
                    <a href="{{ route('bestsellers') }}" class="bh-sec-viewall">View All</a>
                </div>
                <div class="bh-na-grid">
                    @foreach($bestsellers->take(4) as $product)
                        @php
                            $bsRating = (float) ($product->rating ?? 0);
                            $bsReviews = (int) ($product->review_count ?? 0);
                        @endphp
                        <a href="{{ route('product.show', $product) }}" class="bh-na-card">
                            <div class="bh-na-img">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" loading="lazy"
                                     onerror="this.src='{{ asset('images/no-product-image.svg') }}'">
                            </div>
                            @if($bsReviews > 0)
                                <div class="bh-na-rating">
                                    <span class="bh-na-stars">
                                        @for($s = 1; $s <= 5; $s++)
                                            <svg viewBox="0 0 20 20" fill="{{ $s <= round($bsRating) ? '#1a1a1a' : '#d4cfc6' }}">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </span>
                                    <span class="bh-na-reviews">{{ $bsReviews }} {{ Str::plural('review', $bsReviews) }}</span>
                                </div>
                            @else
                                <div class="bh-na-rating"></div>
                            @endif
                            <div class="bh-na-name">{{ $product->name }}</div>
                            <div class="bh-na-price">@price($product->price)</div>
                            <button type="button" class="bh-na-cart"
                                    @click.prevent.stop="$store.cart.add({{ $product->id }})">
                                Add to Cart
                            </button>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ==================== CLASSICS (hidden) ==================== --}}
        @if(false && $featuredProducts->count())
        <section class="bh-section" style="padding-top: 0;">
            <div class="bh-container">
                <div class="bh-sec-head">
                    <h2 class="bh-sec-title">Classics</h2>
                    <a href="{{ route('products.index') }}" class="bh-sec-viewall">View All</a>
                </div>
                <div class="bh-slider-wrap" x-data="bhSlider()">
                    <button type="button" class="bh-arrow bh-arrow--prev" :disabled="atStart" @click="prev()" aria-label="Previous">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="bh-slider" x-ref="slider" @scroll.throttle.100ms="update()">
                        @foreach($featuredProducts as $product)
                            <x-product-card :product="$product" :compact="true" />
                        @endforeach
                    </div>
                    <button type="button" class="bh-arrow bh-arrow--next" :disabled="atEnd" @click="next()" aria-label="Next">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
        </section>
        @endif


        {{-- ==================== SHOP WOMEN — Women's parent categories ==================== --}}
        <section class="bh-section" style="padding-top: 0;">
            <div class="bh-container">
                <div class="bh-sec-head">
                    <div>
                        <div class="bh-eyebrow" style="color: #5E3A26;">The Women's Edit</div>
                        <h2 class="bh-sec-title">Shop Women</h2>
                    </div>
                    <a href="{{ route('category.show', 'womens') }}" class="bh-sec-viewall">View all →</a>
                </div>
                <div class="bh-occasion-grid bh-occasion-grid--mixed">
                    @php
                        $womensTop = [
                            ['label' => 'Tops',         'slug' => 'womens-tops',         'img' => 'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=900&auto=format&fit=crop'],
                            ['label' => 'Co-ord Sets',  'slug' => 'womens-co-ord-sets',  'img' => 'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=600&auto=format&fit=crop'],
                            ['label' => 'Jumpsuits',    'slug' => 'womens-jumpsuits',    'img' => 'https://images.unsplash.com/photo-1583744946564-b52ac1c389c8?w=600&auto=format&fit=crop'],
                            ['label' => 'One Piece', 'slug' => 'one-piece', 'img' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=900&auto=format&fit=crop'],
                        ];
                    @endphp
                    @foreach($womensTop as $w)
                        <a href="{{ route('category.show', $w['slug']) }}" class="bh-occasion-card">
                            <img src="{{ $w['img'] }}" alt="{{ $w['label'] }}" loading="lazy">
                            <div class="bh-occasion-label">{{ $w['label'] }}</div>
                        </a>
                    @endforeach
                </div>

                {{-- Remaining 3 women's categories in flat 3-col row --}}
                <div class="bh-occasion-grid bh-occasion-grid--flat" style="margin-top: 12px; grid-template-columns: repeat(3, 1fr); grid-template-rows: 240px; grid-auto-rows: 240px;">
                    @php
                        $womensBottom = [
                            ['label' => 'T-Shirts', 'slug' => 'womens-t-shirts', 'img' => 'https://images.unsplash.com/photo-1554568218-0f1715e72254?w=600&auto=format&fit=crop'],
                            ['label' => 'Kurtas',   'slug' => 'womens-kurtas',   'img' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=600&auto=format&fit=crop'],
                            ['label' => 'Trousers', 'slug' => 'womens-trousers', 'img' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=600&auto=format&fit=crop'],
                        ];
                    @endphp
                    @foreach($womensBottom as $w)
                        <a href="{{ route('category.show', $w['slug']) }}" class="bh-occasion-card">
                            <img src="{{ $w['img'] }}" alt="{{ $w['label'] }}" loading="lazy">
                            <div class="bh-occasion-label">{{ $w['label'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ==================== SHOP MEN'S — categories + sub-categories ==================== --}}
        <section class="bh-section" style="padding-top: 0;">
            <div class="bh-container">
                <div class="bh-sec-head">
                    <div>
                        <div class="bh-eyebrow" style="color: #5E3A26;">The Men's Edit</div>
                        <h2 class="bh-sec-title">Shop Men</h2>
                    </div>
                    <a href="{{ route('category.show', 'mens') }}" class="bh-sec-viewall">View all →</a>
                </div>
                <div class="bh-occasion-grid bh-occasion-grid--mixed">
                    @php
                        $mensCats = [
                            ['label' => 'T-Shirts', 'slug' => 'mens-t-shirts', 'img' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=900&auto=format&fit=crop'],
                            ['label' => 'Shirts',   'slug' => 'mens-shirts',   'img' => 'https://images.unsplash.com/photo-1603252109303-2751441dd157?w=600&auto=format&fit=crop'],
                            ['label' => 'Kurtas',   'slug' => 'mens-kurtas',   'img' => 'https://images.unsplash.com/photo-1592878904946-b3cd8ae243d0?w=600&auto=format&fit=crop'],
                            ['label' => 'Trousers', 'slug' => 'mens-trousers', 'img' => 'https://images.unsplash.com/photo-1473966968600-fa801b9114d0?w=600&auto=format&fit=crop'],
                        ];
                    @endphp
                    @foreach($mensCats as $cat)
                        <a href="{{ route('category.show', $cat['slug']) }}" class="bh-occasion-card">
                            <img src="{{ $cat['img'] }}" alt="{{ $cat['label'] }}" loading="lazy">
                            <div class="bh-occasion-label">{{ $cat['label'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ==================== ABOUT US ==================== --}}
        <section class="bh-about bh-section">
            <div class="bh-container">
                <div class="bh-about-grid">
                    <div class="bh-about-intro">
                        <h2 class="bh-about-title">About Us</h2>
                        <p class="bh-about-text">
                            Trusted by 1M+ consumers across India, we've created clothing that blends modern tailoring,
                            premium fabrics, and thoughtful detail. Every piece is designed to move with you —
                            from desk to dinner, travel to weekend downtime.
                        </p>
                        <a href="{{ route('about') }}" class="bh-sec-viewall">Read More</a>
                    </div>
                    <div class="bh-feature-grid">
                        <div class="bh-feature-item">
                            {{-- Ruler / tailor's measure — precision tailoring --}}
                            <svg class="bh-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="8" width="20" height="8" rx="1"/>
                                <path d="M6 8v3M10 8v4M14 8v3M18 8v4"/>
                            </svg>
                            <h4>Precision-tailored fits</h4>
                        </div>
                        <div class="bh-feature-item">
                            {{-- Needle & thread — functional detailing --}}
                            <svg class="bh-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 21L14 10"/>
                                <circle cx="15.5" cy="8.5" r="1.2"/>
                                <path d="M16.5 9.5l4 4"/>
                                <path d="M17 4c.8 2 2 3.2 4 4-2 .8-3.2 2-4 4-.8-2-2-3.2-4-4 2-.8 3.2-2 4-4z"/>
                            </svg>
                            <h4>Functional detailing</h4>
                        </div>
                        <div class="bh-feature-item">
                            {{-- Wind / breeze — breathable fabrics --}}
                            <svg class="bh-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 9h11a3 3 0 103-3"/>
                                <path d="M3 13h16a3 3 0 113 3"/>
                                <path d="M5 17h8a2.5 2.5 0 112.5 2.5"/>
                            </svg>
                            <h4>Breathable fabrics</h4>
                        </div>
                        <div class="bh-feature-item">
                            {{-- Shield with check — durable construction --}}
                            <svg class="bh-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3l8 3v6c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V6l8-3z"/>
                                <path d="M9 12l2 2 4-4"/>
                            </svg>
                            <h4>Durable construction</h4>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==================== OUR STORES (hidden) ==================== --}}
        @if(false)
        <section class="bh-section">
            <div class="bh-container">
                <div class="bh-sec-head">
                    <h2 class="bh-sec-title">Our Stores</h2>
                    <a href="{{ route('contact') }}" class="bh-sec-viewall">Find a Store</a>
                </div>
                <div class="bh-stores-grid" style="grid-template-columns: 1fr; max-width: 520px; margin: 0 auto;">
                    @php
                        $stores = [
                            ['name' => 'Bhartiya City', 'addr' => 'Bhartiya City Mall, Thanisandra Main Rd, Bengaluru', 'phone' => '+91 88844 77728', 'img' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800&auto=format&fit=crop'],
                        ];
                    @endphp
                    @foreach($stores as $s)
                        <div class="bh-store-card">
                            <div class="bh-store-img"><img src="{{ $s['img'] }}" alt="{{ $s['name'] }}" loading="lazy"></div>
                            <div class="bh-store-body">
                                <h3 class="bh-store-name">{{ $s['name'] }}</h3>
                                <p class="bh-store-addr">{{ $s['addr'] }}</p>
                                <div class="bh-store-links">
                                    <a href="https://maps.google.com/?q={{ urlencode($s['name']) }}" target="_blank">Directions</a>
                                    <a href="tel:{{ str_replace(' ','',$s['phone']) }}">Call</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- ==================== OUR QUALITIES ==================== --}}
        <section class="bh-qualities" aria-labelledby="our-qualities-heading">
            <div class="bh-container">
                <div class="bh-craft-head">
                    <div class="bh-craft-eyebrow">What sets us apart</div>
                    <h2 id="our-qualities-heading" class="bh-craft-title" style="color:#fff;">Our <em style="color:var(--bh-muted);">qualities</em></h2>
                    <p class="bh-craft-sub" style="color: rgba(255,255,255,0.82);">Six pillars every piece is measured against — no shortcuts, no exceptions.</p>
                </div>
                <ul class="bh-quality-grid" role="list">
                    @php
                        $qualities = [
                            [
                                'num' => '01',
                                'title' => 'Premium Fabrics',
                                'desc' => 'BCI-certified cotton, Tencel blends and long-staple linens — sourced from mills we know by name.',
                                'svg' => '<path d="M4 8c4-4 12-4 16 0v8c-4 4-12 4-16 0V8z"/><path d="M4 12c4-4 12-4 16 0"/><path d="M4 16c4-4 12-4 16 0"/>',
                            ],
                            [
                                'num' => '02',
                                'title' => 'Hand-Finished Detailing',
                                'desc' => 'Seams, buttonholes and hems hand-inspected. If it doesn\'t pass our table, it doesn\'t ship.',
                                'svg' => '<path d="M12 2l2 6h6l-5 4 2 7-5-4-5 4 2-7-5-4h6z"/>',
                            ],
                            [
                                'num' => '03',
                                'title' => 'Precision Tailoring',
                                'desc' => 'Pattern-graded across six sizes so the drape holds true — from the shoulder line to the hem break.',
                                'svg' => '<path d="M3 3l18 18M6 3v18M18 3v18M3 6h18M3 18h18"/>',
                            ],
                            [
                                'num' => '04',
                                'title' => 'Ethical Production',
                                'desc' => 'Fair-wage partners, regular audits, and transparency reports published twice a year.',
                                'svg' => '<path d="M12 2a10 10 0 100 20 10 10 0 000-20z"/><path d="M8 12l3 3 5-6"/>',
                            ],
                            [
                                'num' => '05',
                                'title' => 'Wash-Tested for Life',
                                'desc' => 'Every fabric survives 50+ wash cycles in our lab before it makes the cut — colour-fast, shape-holding.',
                                'svg' => '<path d="M5 7h14l-1 12H6L5 7z"/><path d="M9 7V4h6v3"/><path d="M12 11v6M9 14h6"/>',
                            ],
                            [
                                'num' => '06',
                                'title' => 'Lifetime Mend Promise',
                                'desc' => 'Broken stitch? Lost button? We\'ll fix it on us. Because good clothes deserve long lives.',
                                'svg' => '<path d="M20.84 4.6a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.07a5.5 5.5 0 00-7.78 7.78L12 21.23l8.84-8.85a5.5 5.5 0 000-7.78z"/>',
                            ],
                        ];
                    @endphp
                    @foreach($qualities as $q)
                        <li class="bh-quality-card">
                            <div class="bh-quality-num" aria-hidden="true">{{ $q['num'] }}</div>
                            <div class="bh-quality-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    {!! $q['svg'] !!}
                                </svg>
                            </div>
                            <h3 class="bh-quality-title">{{ $q['title'] }}</h3>
                            <p class="bh-quality-desc">{{ $q['desc'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ==================== NEWSLETTER ==================== --}}
        <section class="bh-newsletter">
            <h2>Join the Family</h2>
            <p>Sign up for 10% off your first order + early access to new drops.</p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="bh-newsletter-form">
                @csrf
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Sign Up Now</button>
            </form>
        </section>

    </div>

    <x-slot name="scripts">
        <script>
            function bhHero(count) {
                return {
                    active: 0, count: count, timer: null,
                    init() {
                        if (this.count > 1) {
                            this.timer = setInterval(() => { this.active = (this.active + 1) % this.count; }, 5500);
                        }
                    },
                    go(i) { this.active = i; this.reset(); },
                    prev() { this.active = (this.active - 1 + this.count) % this.count; this.reset(); },
                    next() { this.active = (this.active + 1) % this.count; this.reset(); },
                    reset() {
                        if (this.timer) { clearInterval(this.timer); this.init(); }
                    }
                };
            }

            function bhSlider() {
                return {
                    atStart: true, atEnd: false,
                    init() { this.$nextTick(() => this.update()); },
                    update() {
                        const el = this.$refs.slider;
                        if (!el) return;
                        this.atStart = el.scrollLeft <= 4;
                        this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
                    },
                    prev() { this.$refs.slider.scrollBy({ left: -this.$refs.slider.clientWidth * 0.8, behavior: 'smooth' }); },
                    next() { this.$refs.slider.scrollBy({ left: this.$refs.slider.clientWidth * 0.8, behavior: 'smooth' }); }
                };
            }
        </script>
    </x-slot>
</x-layouts.app>
