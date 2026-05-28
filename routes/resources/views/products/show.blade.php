<x-layouts.app>
    <x-slot name="title">{{ $product->name }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ Str::limit(strip_tags($product->description), 160) }}">
        <link rel="canonical" href="{{ route('product.show', $product) }}">
        <meta property="og:title" content="{{ $product->name }}">
        <meta property="og:description" content="{{ Str::limit(strip_tags($product->description), 160) }}">
        <meta property="og:image" content="{{ $product->primary_image_url }}">
        <meta property="og:type" content="product">
        <meta property="og:url" content="{{ route('product.show', $product) }}">
        <meta property="product:price:amount" content="{{ $product->price }}">
        <meta property="product:price:currency" content="INR">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $product->name }}">
        <meta name="twitter:description" content="{{ Str::limit(strip_tags($product->description), 160) }}">
        <meta name="twitter:image" content="{{ $product->primary_image_url }}">
        <x-product-schema :productSchema="$productSchema ?? null" :faqSchema="$faqSchema ?? null" />
    @endpush

    @php
        $images = $product->images->pluck('url')->map(function($url) {
            if ($url && !str_starts_with($url, 'http') && !str_starts_with($url, '/')) {
                return asset('storage/' . $url);
            }
            return $url ?: asset('images/no-product-image.svg');
        })->values()->toArray();
        if (empty($images)) {
            $images = [$product->primary_image_url];
        }

        $variantData = $product->variants->map(function($v) {
            return [
                'id' => $v->id,
                'price' => (float) ($v->price ?? 0),
                'mrp' => (float) ($v->mrp ?? 0),
                'stock' => (int) ($v->stock_quantity ?? 0),
                'sku' => $v->sku ?? '',
                'attributes' => $v->attributeValues->map(fn($av) => [
                    'name' => $av->attribute->name,
                    'value' => $av->value,
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        $variantGroups = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->attributeValues as $av) {
                $attrName = $av->attribute->name;
                if (!isset($variantGroups[$attrName])) {
                    $variantGroups[$attrName] = [];
                }
                if (!in_array($av->value, $variantGroups[$attrName])) {
                    $variantGroups[$attrName][] = $av->value;
                }
            }
        }

        $discountPct = $product->discount_percentage;
        $savings = $product->mrp > $product->price ? $product->mrp - $product->price : 0;

        $ratingDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $totalReviews = $product->review_count ?: 0;
        $dbDist = \App\Models\Review::where('product_id', $product->id)
            ->where('is_approved', true)
            ->selectRaw('rating, count(*) as cnt')
            ->groupBy('rating')
            ->pluck('cnt', 'rating')
            ->toArray();
        foreach ($dbDist as $star => $cnt) {
            if (isset($ratingDist[$star])) $ratingDist[$star] = $cnt;
        }
        if ($totalReviews > 0 && array_sum($ratingDist) === 0) {
            $avg = $product->rating ?: 4;
            $ratingDist[5] = (int) round($totalReviews * max(0, ($avg - 3)) / 3);
            $ratingDist[4] = (int) round($totalReviews * 0.3);
            $ratingDist[3] = (int) round($totalReviews * 0.1);
            $ratingDist[2] = (int) round($totalReviews * 0.03);
            $ratingDist[1] = $totalReviews - $ratingDist[5] - $ratingDist[4] - $ratingDist[3] - $ratingDist[2];
            if ($ratingDist[1] < 0) $ratingDist[1] = 0;
        }
        $rating = (float) ($product->rating ?? 0);
    @endphp

    <x-slot name="styles">
        <style>
            .pd {
                --pd-ink: #1a1a1a;
                --pd-sub: #595959;
                --pd-accent: #3E2A1F;
                --pd-mid: #5E3A26;
                --pd-muted: #C9A27B;
                --pd-cream: #F2E4D2;
                --pd-bg: #FAF5EF;
                --pd-line: #e8e3da;
                font-family: 'Inter','DM Sans',sans-serif;
                color: var(--pd-ink);
                background: var(--pd-bg);
            }

            /* breadcrumb */
            .pd-crumb {
                background: var(--pd-bg);
                border-bottom: 1px solid var(--pd-line);
            }

            /* ===== main 2-col ===== */
            .pd-main {
                max-width: 1320px; margin: 0 auto;
                padding: 18px 14px 36px;
                display: grid; grid-template-columns: 1.05fr 0.95fr;
                gap: 56px;
                align-items: start;
            }
            @media (max-width: 980px) {
                .pd-main { grid-template-columns: 1fr; gap: 32px; }
            }

            /* ===== gallery — image grid (scrolls naturally) ===== */
            .pd-gallery { position: relative; }

            /* ===== info column — sticky while gallery scrolls ===== */
            .pd-info {
                position: sticky;
                top: 150px;
                align-self: start;
            }
            @media (max-width: 980px) {
                .pd-info { position: static; top: auto; }
            }
            .pd-grid-imgs {
                display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
            }
            .pd-grid-img {
                position: relative;
                aspect-ratio: 3/4;
                background: var(--pd-cream);
                overflow: hidden;
                cursor: zoom-in;
            }
            /* first image spans full width when there's an odd count up top */
            .pd-grid-img img {
                width: 100%; height: 100%; object-fit: cover; display: block;
                transition: transform 0.5s ease;
            }
            .pd-grid-img:hover img { transform: scale(1.04); }
            .pd-grid-badge {
                position: absolute; top: 14px; left: 14px; z-index: 2;
                background: var(--pd-accent); color: #fff;
                font-size: 11px; font-weight: 700; letter-spacing: 0.1em;
                text-transform: uppercase; padding: 6px 12px; border-radius: 3px;
            }
            /* swipe indicator — hidden on desktop */
            .pd-swipe { display: none; }

            /* Mobile: turn the image grid into a swipeable carousel */
            @media (max-width: 768px) {
                .pd-grid-imgs {
                    display: flex;
                    grid-template-columns: none;
                    overflow-x: auto;
                    scroll-snap-type: x mandatory;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                    gap: 0;
                }
                .pd-grid-imgs::-webkit-scrollbar { display: none; }
                .pd-grid-img {
                    flex: 0 0 100%;
                    scroll-snap-align: start;
                }
                .pd-swipe {
                    display: flex; flex-direction: column; align-items: center;
                    gap: 10px; margin-top: 14px;
                }
                .pd-swipe-hint {
                    display: inline-flex; align-items: center; gap: 6px;
                    font-size: 11px; font-weight: 600; letter-spacing: 0.16em;
                    text-transform: uppercase; color: var(--pd-sub);
                }
                .pd-swipe-hint svg { color: var(--pd-mid); }
                .pd-dots { display: flex; gap: 7px; }
                .pd-dot {
                    width: 7px; height: 7px; border-radius: 50%;
                    border: none; padding: 0; cursor: pointer;
                    background: var(--pd-line);
                    transition: background 0.2s, width 0.25s;
                }
                .pd-dot.is-on {
                    background: var(--pd-accent);
                    width: 20px; border-radius: 4px;
                }
            }
            /* lightbox */
            .pd-lightbox {
                position: fixed; inset: 0; z-index: 80;
                background: rgba(20,14,10,0.92);
                display: flex; align-items: center; justify-content: center;
                padding: 40px;
            }
            .pd-lightbox img {
                max-width: 90vw; max-height: 90vh; object-fit: contain;
                border-radius: 6px;
            }
            .pd-lightbox-close {
                position: absolute; top: 24px; right: 28px;
                width: 44px; height: 44px; border-radius: 50%;
                background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
                color: #fff; cursor: pointer; font-size: 22px;
                display: flex; align-items: center; justify-content: center;
            }

            /* ===== info column ===== */
            .pd-eyebrow {
                font-size: 12px; font-weight: 600; letter-spacing: 0.18em;
                text-transform: uppercase; color: var(--pd-mid); margin-bottom: 10px;
            }
            .pd-title {
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: clamp(26px, 3vw, 38px); font-weight: 700;
                line-height: 1.18; letter-spacing: -0.02em;
                margin: 0 0 14px; color: var(--pd-ink);
            }
            .pd-rating-row {
                display: flex; align-items: center; gap: 10px;
                margin-bottom: 18px;
            }
            .pd-stars { display: inline-flex; gap: 2px; }
            .pd-star { width: 16px; height: 16px; }
            .pd-rating-text { font-size: 15px; color: var(--pd-sub); }
            .pd-rating-text a { color: var(--pd-mid); text-decoration: underline; }

            .pd-price-row {
                display: flex; align-items: baseline; flex-wrap: wrap; gap: 12px;
                margin-bottom: 6px;
            }
            .pd-price {
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: 30px; font-weight: 700; color: var(--pd-ink);
                letter-spacing: -0.02em;
            }
            .pd-mrp {
                font-size: 18px; color: var(--pd-sub);
                text-decoration: line-through;
            }
            .pd-disc {
                font-size: 14px; font-weight: 700; color: var(--pd-accent);
            }
            .pd-tax { font-size: 14px; color: var(--pd-sub); margin-bottom: 22px; }

            /* offers banner */
            .pd-offer {
                background: linear-gradient(135deg, var(--pd-cream) 0%, #e6d2b6 100%);
                border: 1px dashed var(--pd-muted);
                border-radius: 8px; padding: 14px 16px; margin-bottom: 24px;
            }
            .pd-offer-title {
                font-size: 11px; font-weight: 700; letter-spacing: 0.16em;
                text-transform: uppercase; color: var(--pd-accent); margin-bottom: 8px;
            }
            .pd-offer-item {
                display: flex; align-items: center; gap: 8px;
                font-size: 15px; color: var(--pd-ink); margin-bottom: 6px;
            }
            .pd-offer-item:last-child { margin-bottom: 0; }
            .pd-offer-code {
                font-weight: 700; color: var(--pd-accent);
                background: #fff; border: 1px solid var(--pd-muted);
                padding: 1px 7px; border-radius: 3px; font-size: 12px;
            }

            /* selectors */
            .pd-selector { margin-bottom: 22px; }
            .pd-selector-label {
                font-size: 15px; font-weight: 700; letter-spacing: 0.04em;
                text-transform: uppercase; margin-bottom: 10px;
            }
            .pd-selector-label span { font-weight: 400; color: var(--pd-sub); text-transform: none; }
            .pd-opts { display: flex; flex-wrap: wrap; gap: 8px; }
            .pd-opt {
                min-width: 48px; padding: 11px 16px;
                border: 1px solid var(--pd-line); background: #fff;
                font-size: 15px; font-weight: 600; cursor: pointer;
                border-radius: 6px; transition: all 0.18s;
                color: var(--pd-ink); text-align: center;
            }
            .pd-opt:hover { border-color: var(--pd-mid); }
            .pd-opt.is-sel {
                background: var(--pd-accent); color: #fff; border-color: var(--pd-accent);
            }

            /* qty */
            .pd-qty {
                display: inline-flex; align-items: center;
                border: 1px solid var(--pd-line); border-radius: 6px;
                overflow: hidden; background: #fff;
            }
            .pd-qty button {
                width: 42px; height: 44px; border: none; background: transparent;
                font-size: 18px; cursor: pointer; color: var(--pd-ink);
            }
            .pd-qty button:hover { background: var(--pd-cream); }
            .pd-qty input {
                width: 48px; height: 44px; text-align: center; border: none;
                font-size: 15px; font-weight: 600; outline: none; background: transparent;
                border-left: 1px solid var(--pd-line); border-right: 1px solid var(--pd-line);
            }

            /* action buttons */
            .pd-actions { display: flex; flex-direction: column; gap: 12px; margin: 24px 0; }
            .pd-btn-row { display: flex; gap: 14px; }
            .pd-btn {
                flex: 1; min-height: 42px; padding: 8px 14px;
                display: inline-flex; align-items: center; justify-content: center; gap: 10px;
                border-radius: 2px; cursor: pointer;
                font-family: inherit; font-size: 13px; font-weight: 700;
                letter-spacing: 0.14em; text-transform: uppercase;
                background: var(--pd-accent); color: #fff;
                border: 1.5px solid var(--pd-accent);
                transition: background 0.2s, transform 0.15s;
            }
            .pd-btn:hover { background: #1a1a1a; border-color: #1a1a1a; }
            .pd-btn:active { transform: scale(0.99); }
            .pd-btn[disabled] { opacity: 0.5; cursor: not-allowed; }
            .pd-wish {
                width: 100%; min-height: 50px;
                border: 1.5px solid var(--pd-line); border-radius: 2px;
                background: #fff; cursor: pointer;
                display: flex; align-items: center; justify-content: center; gap: 10px;
                color: var(--pd-ink); transition: all 0.2s;
                font-family: inherit; font-size: 13px; font-weight: 600;
                letter-spacing: 0.12em; text-transform: uppercase;
            }
            .pd-wish:hover { border-color: var(--pd-accent); color: var(--pd-accent); }

            /* trust badges */
            .pd-trust {
                display: grid; grid-template-columns: repeat(3, 1fr);
                gap: 8px; margin-top: 22px;
                border-top: 1px solid var(--pd-line);
                border-bottom: 1px solid var(--pd-line);
                padding: 18px 0;
            }
            .pd-trust-item {
                text-align: center; display: flex; flex-direction: column;
                align-items: center; gap: 8px;
            }
            .pd-trust-item svg { width: 26px; height: 26px; color: var(--pd-mid); }
            .pd-trust-item span {
                font-size: 13px; font-weight: 600; color: var(--pd-sub);
                line-height: 1.4;
            }

            /* meta line */
            .pd-meta {
                font-size: 15px; color: var(--pd-sub); margin-top: 16px;
                line-height: 1.9;
            }
            .pd-meta b { color: var(--pd-ink); font-weight: 600; }
            .pd-stock-in { color: #1a7a2e; font-weight: 600; }
            .pd-stock-out { color: #c0392b; font-weight: 600; }

            /* ===== below-fold sections ===== */
            .pd-lower { max-width: 1100px; margin: 0 auto; padding: 0 14px 44px; }
            .pd-acc {
                border-bottom: 1px solid var(--pd-line);
            }
            .pd-acc-head {
                width: 100%; display: flex; align-items: center; justify-content: space-between;
                padding: 24px 4px; background: transparent; border: none; cursor: pointer;
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: 23px; font-weight: 700; color: var(--pd-ink);
                text-align: left;
            }
            .pd-acc-head svg { width: 22px; height: 22px; transition: transform 0.3s; flex-shrink: 0; }
            .pd-acc-head.is-open svg { transform: rotate(45deg); }
            .pd-acc-body {
                padding: 0 4px 28px;
                font-size: 17px; line-height: 1.85; color: var(--pd-sub);
            }
            .pd-acc-body p { margin-bottom: 12px; }
            .pd-acc-body ul { margin: 8px 0 12px 18px; list-style: disc; }
            .pd-acc-body li { margin-bottom: 6px; }
            .pd-spec-table { width: 100%; border-collapse: collapse; font-size: 16px; }
            .pd-spec-table td { padding: 11px 14px; border-bottom: 1px solid var(--pd-line); }
            .pd-spec-table td:first-child {
                font-weight: 600; color: var(--pd-ink); width: 36%;
                text-transform: capitalize;
            }
            .pd-spec-table td:last-child { color: var(--pd-sub); }

            /* reviews */
            .pd-section-title {
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: clamp(24px, 2.8vw, 34px); font-weight: 700;
                letter-spacing: -0.02em; margin: 0 0 28px;
            }
            .pd-reviews-top {
                display: grid; grid-template-columns: 240px 1fr;
                gap: 40px; margin-bottom: 36px; align-items: center;
            }
            @media (max-width: 680px) { .pd-reviews-top { grid-template-columns: 1fr; gap: 22px; } }
            .pd-rating-big {
                text-align: center; background: var(--pd-cream);
                border-radius: 12px; padding: 28px 20px;
            }
            .pd-rating-num {
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: 52px; font-weight: 800; line-height: 1; color: var(--pd-accent);
            }
            .pd-rating-count { font-size: 12px; color: var(--pd-sub); margin-top: 8px; }
            .pd-bar-row {
                display: flex; align-items: center; gap: 10px; margin-bottom: 8px;
                font-size: 12px; color: var(--pd-sub);
            }
            .pd-bar-track {
                flex: 1; height: 8px; background: var(--pd-line);
                border-radius: 4px; overflow: hidden;
            }
            .pd-bar-fill { height: 100%; background: var(--pd-accent); border-radius: 4px; }
            .pd-review-card {
                border: 1px solid var(--pd-line); border-radius: 10px;
                padding: 22px; margin-bottom: 14px; background: #fff;
            }
            .pd-review-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
            .pd-review-avatar {
                width: 42px; height: 42px; border-radius: 50%;
                background: var(--pd-accent); color: #fff;
                display: flex; align-items: center; justify-content: center;
                font-weight: 700; font-size: 16px;
            }
            .pd-review-name { font-size: 16px; font-weight: 700; }
            .pd-review-date { font-size: 12px; color: var(--pd-sub); }
            .pd-review-title { font-size: 17px; font-weight: 700; margin: 8px 0 4px; }
            .pd-review-body { font-size: 16px; line-height: 1.75; color: var(--pd-sub); }

            /* related */
            .pd-related { background: var(--pd-bg); padding: 36px 0; }
            .pd-related-grid {
                max-width: 1320px; margin: 0 auto; padding: 0 24px;
                display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px;
            }
            @media (max-width: 900px) { .pd-related-grid { grid-template-columns: repeat(2, 1fr); } }
        </style>
    </x-slot>

    <div class="pd">
        {{-- ===== Breadcrumb ===== --}}
        <div class="pd-crumb">
            <div class="container mx-auto px-4" style="padding-top:0.6rem;padding-bottom:0.6rem;">
                <x-breadcrumb :items="$breadcrumbs" />
            </div>
        </div>

        <div x-data="productPage()">
            {{-- ============ MAIN 2-COLUMN ============ --}}
            <div class="pd-main">

                {{-- ===== LEFT: GALLERY (image grid) ===== --}}
                <div class="pd-gallery" x-data="{ slide: 0 }">
                    <div class="pd-grid-imgs" x-ref="galleryStrip"
                         @scroll.throttle.120ms="slide = Math.round($refs.galleryStrip.scrollLeft / $refs.galleryStrip.clientWidth)">
                        @foreach($images as $i => $img)
                            <div class="pd-grid-img" @click="lightbox = {{ $i }}">
                                @if($discountPct > 0 && $i === 0)
                                    <span class="pd-grid-badge">{{ $discountPct }}% Off</span>
                                @endif
                                <img src="{{ $img }}" alt="{{ $product->name }} — view {{ $i + 1 }}" loading="{{ $i < 2 ? 'eager' : 'lazy' }}">
                            </div>
                        @endforeach
                    </div>

                    {{-- Mobile swipe indicator --}}
                    @if(count($images) > 1)
                        <div class="pd-swipe">
                            <div class="pd-swipe-hint">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                                <span>Swipe</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                            </div>
                            <div class="pd-dots">
                                @foreach($images as $i => $img)
                                    <button type="button" class="pd-dot" :class="slide === {{ $i }} ? 'is-on' : ''"
                                            @click="$refs.galleryStrip.scrollTo({ left: $refs.galleryStrip.clientWidth * {{ $i }}, behavior: 'smooth' })"
                                            aria-label="Go to image {{ $i + 1 }}"></button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Lightbox --}}
                    <div class="pd-lightbox" x-show="lightbox !== null" x-cloak
                         @click="lightbox = null"
                         @keydown.escape.window="lightbox = null"
                         x-transition.opacity>
                        <button type="button" class="pd-lightbox-close" @click.stop="lightbox = null" aria-label="Close">&times;</button>
                        <template x-for="(img, i) in images" :key="i">
                            <img x-show="lightbox === i" :src="img" alt="{{ $product->name }}" @click.stop>
                        </template>
                    </div>
                </div>

                {{-- ===== RIGHT: INFO ===== --}}
                <div class="pd-info">
                    @if($product->brand)
                        <div class="pd-eyebrow">{{ $product->brand->name }}</div>
                    @elseif($product->category)
                        <div class="pd-eyebrow">{{ $product->category->name }}</div>
                    @endif

                    <h1 class="pd-title">{{ $product->name }}</h1>

                    {{-- Rating --}}
                    <div class="pd-rating-row">
                        <span class="pd-stars" aria-hidden="true">
                            @for($s = 1; $s <= 5; $s++)
                                <svg class="pd-star" viewBox="0 0 20 20" fill="{{ $s <= round($rating) ? '#3E2A1F' : '#ddd' }}">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </span>
                        <span class="pd-rating-text">
                            @if($totalReviews > 0)
                                {{ number_format($rating, 1) }} · <a href="#pd-reviews">{{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}</a>
                            @else
                                No reviews yet
                            @endif
                        </span>
                    </div>

                    {{-- Price --}}
                    <div class="pd-price-row">
                        <span class="pd-price" x-text="'₹' + (unitPrice * quantity).toLocaleString('en-IN')">₹{{ number_format($product->price) }}</span>
                        @if($savings > 0)
                            <span class="pd-mrp" x-text="'₹' + (unitMrp * quantity).toLocaleString('en-IN')">₹{{ number_format($product->mrp) }}</span>
                            <span class="pd-disc">{{ $discountPct }}% Off</span>
                        @endif
                    </div>
                    <div class="pd-tax">Inclusive of all taxes</div>

                    {{-- Offers --}}
                    @if($activeCoupons->count())
                        <div class="pd-offer" x-data="{ copied: '' }">
                            <div class="pd-offer-title">★ Available Offers</div>
                            @foreach($activeCoupons as $coupon)
                                <div class="pd-offer-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3E2A1F" stroke-width="2"><path d="M20 12l-8 8H4v-8l8-8h8z"/><circle cx="15.5" cy="8.5" r="1.2" fill="#3E2A1F"/></svg>
                                    <span>
                                        {{ $coupon->type === 'percentage' ? $coupon->value.'% off' : '₹'.number_format($coupon->value).' off' }}
                                        with code
                                        <button type="button" class="pd-offer-code"
                                                @click="navigator.clipboard.writeText('{{ $coupon->code }}'); copied='{{ $coupon->code }}'; $store.toast.success('Code copied!')">
                                            <span x-text="copied === '{{ $coupon->code }}' ? 'Copied ✓' : '{{ $coupon->code }}'">{{ $coupon->code }}</span>
                                        </button>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Variant selectors --}}
                    @foreach($variantGroups as $attrName => $values)
                        <div class="pd-selector">
                            <div class="pd-selector-label">
                                {{ $attrName }}
                                <span x-text="selectedAttributes['{{ $attrName }}'] ? '— ' + selectedAttributes['{{ $attrName }}'] : ''"></span>
                            </div>
                            <div class="pd-opts">
                                @foreach($values as $val)
                                    <button type="button" class="pd-opt"
                                            :class="selectedAttributes['{{ $attrName }}'] === '{{ $val }}' ? 'is-sel' : ''"
                                            @click="selectAttribute('{{ $attrName }}', '{{ $val }}')">
                                        {{ $val }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Quantity --}}
                    <div class="pd-selector">
                        <div class="pd-selector-label">Quantity</div>
                        <div class="pd-qty">
                            <button type="button" @click="quantity = Math.max(1, quantity - 1)" aria-label="Decrease">−</button>
                            <input type="text" x-model.number="quantity" readonly aria-label="Quantity">
                            <button type="button" @click="quantity = quantity + 1" aria-label="Increase">+</button>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pd-actions">
                        <div class="pd-btn-row">
                            <button type="button" class="pd-btn"
                                    @click="addToCart()" :disabled="$store.cart.isLoading || !inStock">
                                <span x-show="inStock">Add to Cart</span>
                                <span x-show="!inStock" x-cloak>Out of Stock</span>
                            </button>
                            <button type="button" class="pd-btn"
                                    @click="buyNow()" :disabled="$store.cart.isLoading || !inStock">
                                Buy Now
                            </button>
                        </div>
                        <button type="button" class="pd-wish"
                                @click="$store.wishlist.toggle({{ $product->id }})"
                                :style="$store.wishlist.has({{ $product->id }}) ? 'color:#c0392b;border-color:#c0392b' : ''">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                 :fill="$store.wishlist.has({{ $product->id }}) ? 'currentColor' : 'none'">
                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span x-text="$store.wishlist.has({{ $product->id }}) ? 'Wishlisted' : 'Add to Wishlist'">Add to Wishlist</span>
                        </button>
                    </div>

                    {{-- Trust badges --}}
                    <div class="pd-trust">
                        <div class="pd-trust-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.6a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.07a5.5 5.5 0 00-7.78 7.78L12 21.23l8.84-8.85a5.5 5.5 0 000-7.78z"/></svg>
                            <span>Loved by 30,000+<br>Happy Customers</span>
                        </div>
                        <div class="pd-trust-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            <span>Express COD<br>Shipping</span>
                        </div>
                        <div class="pd-trust-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0115-6.7L21 8M21 3v5h-5M21 12a9 9 0 01-15 6.7L3 16M3 21v-5h5"/></svg>
                            <span>Easy Returns &<br>Exchange</span>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="pd-meta">
                        <div>
                            Availability:
                            @if($product->isInStock())
                                <span class="pd-stock-in">In Stock</span>
                            @else
                                <span class="pd-stock-out">Out of Stock</span>
                            @endif
                        </div>
                        @if($product->sku)<div><b>SKU:</b> {{ $product->sku }}</div>@endif
                        @if($product->category)<div><b>Category:</b> {{ $product->category->name }}</div>@endif
                    </div>

                    {{-- ===== Accordions (right column) ===== --}}
                    <div style="margin-top:28px;">
                        {{-- Description --}}
                        @if($product->description)
                            <div class="pd-acc" x-data="{ open: true }">
                                <button type="button" class="pd-acc-head" :class="open ? 'is-open' : ''" @click="open = !open">
                                    Description
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                                <div class="pd-acc-body" x-show="open" x-collapse>
                                    {!! $product->description !!}
                                </div>
                            </div>
                        @endif

                        {{-- Key Details / Specs --}}
                        @if(($product->specifications && count($product->specifications) > 0) || ($product->attributes && count($product->attributes) > 0) || $product->brand || $product->sku)
                            <div class="pd-acc" x-data="{ open: false }">
                                <button type="button" class="pd-acc-head" :class="open ? 'is-open' : ''" @click="open = !open">
                                    Key Details
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                                <div class="pd-acc-body" x-show="open" x-collapse>
                                    <table class="pd-spec-table">
                                        @if($product->brand)<tr><td>Brand</td><td>{{ $product->brand->name }}</td></tr>@endif
                                        @if($product->category)<tr><td>Category</td><td>{{ $product->category->name }}</td></tr>@endif
                                        @if($product->sku)<tr><td>SKU</td><td>{{ $product->sku }}</td></tr>@endif
                                        @if($product->attributes && count($product->attributes))
                                            @foreach($product->attributes as $key => $value)
                                                <tr><td>{{ $key }}</td><td>{{ is_array($value) ? implode(', ', $value) : $value }}</td></tr>
                                            @endforeach
                                        @endif
                                        @if($product->specifications && count($product->specifications))
                                            @foreach($product->specifications as $key => $value)
                                                <tr><td>{{ $key }}</td><td>{{ is_array($value) ? implode(', ', $value) : $value }}</td></tr>
                                            @endforeach
                                        @endif
                                        <tr><td>Made In</td><td>India</td></tr>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Shipping & Returns --}}
                        <div class="pd-acc" x-data="{ open: false }">
                            <button type="button" class="pd-acc-head" :class="open ? 'is-open' : ''" @click="open = !open">
                                Shipping & Returns
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                            <div class="pd-acc-body" x-show="open" x-collapse>
                                <ul>
                                    <li>Free express shipping on orders above ₹999.</li>
                                    <li>Cash on Delivery available across India.</li>
                                    <li>Easy 7-day return &amp; exchange — no questions asked.</li>
                                    <li>Orders are dispatched within 1–2 business days.</li>
                                    <li>Returns are free; refunds processed within 5–7 days of pickup.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ BELOW THE FOLD ============ --}}
            <div class="pd-lower">

                {{-- ===== REVIEWS ===== --}}
                <div id="pd-reviews" style="margin-top:56px;">
                    <h2 class="pd-section-title">Customer Reviews</h2>

                    <div class="pd-reviews-top">
                        <div class="pd-rating-big">
                            <div class="pd-rating-num">{{ number_format($rating, 1) }}</div>
                            <div class="pd-stars" style="justify-content:center;margin-top:8px;">
                                @for($s = 1; $s <= 5; $s++)
                                    <svg class="pd-star" viewBox="0 0 20 20" fill="{{ $s <= round($rating) ? '#3E2A1F' : '#ddd' }}">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <div class="pd-rating-count">Based on {{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}</div>
                        </div>
                        <div>
                            @foreach([5,4,3,2,1] as $star)
                                @php $pct = $totalReviews > 0 ? round(($ratingDist[$star] / max(1,array_sum($ratingDist))) * 100) : 0; @endphp
                                <div class="pd-bar-row">
                                    <span style="width:42px;">{{ $star }} star</span>
                                    <div class="pd-bar-track"><div class="pd-bar-fill" style="width: {{ $pct }}%;"></div></div>
                                    <span style="width:38px;text-align:right;">{{ $pct }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($product->reviews->count())
                        @foreach($product->reviews as $review)
                            <div class="pd-review-card">
                                <div class="pd-review-head">
                                    <div class="pd-review-avatar">{{ strtoupper(substr($review->user->first_name ?? $review->guest_name ?? 'A', 0, 1)) }}</div>
                                    <div>
                                        <div class="pd-review-name">{{ $review->user->first_name ?? $review->guest_name ?? 'Anonymous' }}{{ $review->is_verified_purchase ? ' · Verified' : '' }}</div>
                                        <div class="pd-review-date">{{ $review->created_at->format('d M Y') }}</div>
                                    </div>
                                </div>
                                <span class="pd-stars">
                                    @for($s = 1; $s <= 5; $s++)
                                        <svg class="pd-star" viewBox="0 0 20 20" fill="{{ $s <= $review->rating ? '#3E2A1F' : '#ddd' }}">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </span>
                                @if($review->title)<div class="pd-review-title">{{ $review->title }}</div>@endif
                                @if($review->content)<div class="pd-review-body">{{ $review->content }}</div>@endif
                            </div>
                        @endforeach
                    @else
                        <p style="font-size:14px;color:var(--pd-sub);">No reviews yet — be the first to share your thoughts.</p>
                    @endif
                </div>
            </div>

            {{-- ===== RELATED PRODUCTS ===== --}}
            @if($relatedProducts->count())
                <div class="pd-related">
                    <div style="max-width:1320px;margin:0 auto 28px;padding:0 24px;">
                        <h2 class="pd-section-title" style="margin:0;">You May Also Like</h2>
                    </div>
                    <div class="pd-related-grid">
                        @foreach($relatedProducts->take(4) as $rp)
                            <x-product-card :product="$rp" :compact="true" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
    function productPage() {
        return {
            currentImage: 0,
            lightbox: null,
            quantity: 1,
            selectedVariant: null,
            selectedAttributes: {},
            variants: @json($variantData),
            images: @json($images),
            basePrice: {{ (float) $product->price }},
            baseMrp: {{ (float) $product->mrp }},
            inStock: {{ $product->isInStock() ? 'true' : 'false' }},

            get unitPrice() {
                if (this.selectedVariant) {
                    const v = this.variants.find(v => v.id === this.selectedVariant);
                    return v && v.price > 0 ? v.price : this.basePrice;
                }
                return this.basePrice;
            },

            get unitMrp() {
                if (this.selectedVariant) {
                    const v = this.variants.find(v => v.id === this.selectedVariant);
                    return v && v.mrp > 0 ? v.mrp : this.baseMrp;
                }
                return this.baseMrp;
            },

            init() {
                this.$el.addEventListener('mobile-add-to-cart', () => this.addToCart());
                this.$el.addEventListener('mobile-buy-now', () => this.buyNow());
            },

            selectAttribute(attrName, value) {
                this.selectedAttributes[attrName] = value;
                this.findMatchingVariant();
            },

            findMatchingVariant() {
                const selectedKeys = Object.keys(this.selectedAttributes);
                if (selectedKeys.length === 0) { this.selectedVariant = null; return; }
                const match = this.variants.find(v => {
                    return v.attributes.every(attr => {
                        if (this.selectedAttributes[attr.name] === undefined) return true;
                        return this.selectedAttributes[attr.name] === attr.value;
                    }) && selectedKeys.every(key => {
                        return v.attributes.some(attr => attr.name === key && attr.value === this.selectedAttributes[key]);
                    });
                });
                this.selectedVariant = match ? match.id : null;
                if (match) { this.inStock = match.stock > 0; }
            },

            async addToCart() {
                await Alpine.store('cart').add({{ $product->id }}, this.quantity, this.selectedVariant);
            },

            async buyNow() {
                await Alpine.store('cart').add({{ $product->id }}, this.quantity, this.selectedVariant);
                Alpine.store('cart').close();
                window.location.href = '{{ route("checkout.index") }}';
            },
        };
    }
    </script>
</x-layouts.app>
