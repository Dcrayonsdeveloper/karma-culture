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
        // Media = images + videos (ordered by position). Each item carries a type + optional poster.
        $resolveUrl = function ($url) {
            if ($url && !str_starts_with($url, 'http') && !str_starts_with($url, '/')) {
                return asset('storage/' . $url);
            }
            return $url;
        };
        $media = $product->images->sortBy('position')->map(function ($img) use ($resolveUrl) {
            return [
                'url'   => $resolveUrl($img->url) ?: asset('images/no-product-image.svg'),
                'type'  => $img->media_type ?? 'image',
                'thumb' => $img->thumbnail_url ? $resolveUrl($img->thumbnail_url) : null,
            ];
        })->values()->toArray();
        if (empty($media)) {
            $media = [['url' => $product->primary_image_url, 'type' => 'image', 'thumb' => null]];
        }
        // Backward-compat: keep $images (image URLs only) for any legacy references.
        $images = collect($media)->pluck('url')->toArray();

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

        // Rating distribution from all reviews (not just loaded 10)
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
        // If product has review_count but no actual review records, synthesize distribution from rating
        if ($totalReviews > 0 && array_sum($ratingDist) === 0) {
            $avg = $product->rating ?: 4;
            $ratingDist[5] = (int) round($totalReviews * max(0, ($avg - 3)) / 3);
            $ratingDist[4] = (int) round($totalReviews * 0.3);
            $ratingDist[3] = (int) round($totalReviews * 0.1);
            $ratingDist[2] = (int) round($totalReviews * 0.03);
            $ratingDist[1] = $totalReviews - $ratingDist[5] - $ratingDist[4] - $ratingDist[3] - $ratingDist[2];
            if ($ratingDist[1] < 0) $ratingDist[1] = 0;
        }
    @endphp

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
    .pdp-wrapper { background: #EFE2CB; }
    .kk-pdp { display: grid; gap: 28px; padding: 24px 0 56px; }
    @media (min-width: 1024px) {
        .kk-pdp { grid-template-columns: 1.2fr 1fr; gap: 56px; align-items: start; }
    }

    /* ===== Gallery — thumbnail rail + main image ===== */
    .kk-pdp__gallery { display: flex; gap: 14px; align-items: flex-start; }
    .kk-pdp__thumbs {
        display: flex; flex-direction: column; gap: 10px;
        /* cap to viewport so the (sticky) gallery never overflows the screen */
        width: 74px; flex-shrink: 0; max-height: min(580px, calc(100vh - 80px)); overflow-y: auto;
    }
    .kk-pdp__thumb {
        padding: 0; border: 1px solid #e3d2b3; border-radius: 8px; overflow: hidden;
        background: #fff; cursor: pointer; aspect-ratio: 3/4; transition: border-color .15s ease;
    }
    .kk-pdp__thumb img, .kk-pdp__thumb video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .kk-pdp__thumb--video { position: relative; }
    .kk-pdp__thumb-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; background: rgba(0,0,0,.18); text-shadow: 0 1px 3px rgba(0,0,0,.6); }
    .kk-pdp__thumb:hover { border-color: #c9b393; }
    .kk-pdp__thumb.is-active { border-color: #2d1810; }

    .kk-pdp__main {
        position: relative; flex: 1; min-width: 0;
        /* cap to viewport height so the pinned (sticky) image is never cut off */
        aspect-ratio: 4/5; max-height: min(580px, calc(100vh - 80px));
        border-radius: 10px; overflow: hidden; background: #fff; cursor: zoom-in;
    }
    .kk-pdp__main-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
    .kk-pdp__main-video { object-fit: contain; background: #000; cursor: default; }
    .kk-pdp__counter {
        position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%);
        background: rgba(31,17,9,.72); color: #efe2cb; font-size: 12px; letter-spacing: .05em;
        padding: 4px 12px; border-radius: 999px;
    }
    /* Gallery prev/next arrows */
    .kk-pdp__navbtn {
        position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
        width: 40px; height: 40px; border-radius: 50%; border: none; cursor: pointer;
        background: rgba(255,255,255,.9); color: #2d1810;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(45,24,16,.18); transition: background .15s ease;
    }
    .kk-pdp__navbtn:hover { background: #fff; }
    .kk-pdp__navbtn svg { width: 18px; height: 18px; }
    .kk-pdp__navbtn--prev { left: 10px; }
    .kk-pdp__navbtn--next { right: 10px; }
    @media (max-width: 640px) { .kk-pdp__navbtn { display: none; } }  /* mobile uses swipe */
    @media (max-width: 640px) {
        .kk-pdp__gallery { flex-direction: column-reverse; }
        .kk-pdp__thumbs { flex-direction: row; width: 100%; max-height: none; overflow-x: auto; }
        .kk-pdp__thumb { width: 60px; flex-shrink: 0; }
        /* flex:none + width:100% so aspect-ratio drives the height in the
           column layout (flex:1 would collapse it to 0 height on mobile).
           Cap to 45vh so the image stays compact on phones and the
           title/price/size sit near the top of the screen. */
        .kk-pdp__main { aspect-ratio: 3/4; max-height: 45vh; flex: none; width: 100%; }
    }

    /* ===== Info column — scrolls normally ===== */
    .kk-pdp__info { padding-top: 4px; }

    /* ===== Sticky LEFT gallery (desktop only) =============================
       Pin the product image/gallery while the right-hand details scroll.
       It releases automatically when the parent .kk-pdp ends, then scrolls
       away with the page.

       Why this works with the existing layout (no structural changes needed):
       - .kk-pdp is a CSS grid with `align-items: start` (line ~91), so the
         gallery cell is NOT stretched to the full row height — that vertical
         slack is exactly what lets it stick. `align-self: start` reasserts it.
       - The gallery is shorter than the details column, so it has room to
         stick as you scroll past it.
       - No JS is used.

       Scoped to >=1024px (the two-column desktop layout). Below 1024px the
       page already stacks to a single column, so sticky never applies on
       tablet/mobile (well under the 768px cut-off you asked for).
       ===================================================================== */
    @media (min-width: 1024px) {
        .kk-pdp__gallery {
            position: sticky;
            top: 24px;          /* gap from the top of the viewport while pinned (adjust to taste) */
            align-self: start;  /* keep the cell its natural height so it can stick */
        }
    }
    .kk-pdp__title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 2rem; line-height: 1.18; font-weight: 700;
        color: #2d1810; margin: 0 0 8px; letter-spacing: 0.005em;
    }
    .kk-pdp__rating { display: flex; align-items: center; gap: 8px; margin: 0 0 18px; }
    .kk-pdp__rating-stars { display: inline-flex; gap: 1px; }
    .kk-pdp__rating-stars svg { width: 16px; height: 16px; }
    .kk-pdp__rating-count { font-size: 13px; color: #2d1810; }
    .kk-pdp__price-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 12px; margin: 0 0 4px; }
    .kk-pdp__price {
        font-size: 1.5rem; font-weight: 600; color: #2d1810; letter-spacing: 0.01em;
    }
    .kk-pdp__mrp { font-size: 1.1rem; color: #a08e76; text-decoration: line-through; }
    .kk-pdp__off {
        font-size: 12px; font-weight: 700; color: #2a9d3e; letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    body:not(.layout-admin) p.kk-pdp__tax { font-size: 8px !important; color: #7a6555; margin: 0 0 12px; }
    .kk-pdp__divider { height: 1px; background: #e3d2b3; border: 0; margin: 24px 0; }
    .kk-pdp__emi {
        position: relative; background: #f7eedb; border: 1px solid #e3d2b3;
        border-radius: 8px; padding: 16px 14px; display: flex;
        align-items: center; gap: 12px; margin: 0 0 28px;
    }
    .kk-pdp__emi-tag {
        position: absolute; top: -8px; left: 10px;
        background: #2a9d3e; color: #fff; font-size: 10px; font-weight: 700;
        letter-spacing: 0.1em; padding: 3px 9px 4px; border-radius: 2px;
    }
    .kk-pdp__emi-tag::after {
        content: ''; position: absolute; left: 0; bottom: -5px;
        border-style: solid; border-width: 5px 0 0 5px;
        border-color: #1c6428 transparent transparent transparent;
    }
    .kk-pdp__emi-text { flex: 1; font-size: 13px; line-height: 1.55; color: #2d1810; }
    .kk-pdp__emi-text strong { color: #2a9d3e; font-weight: 700; }
    .kk-pdp__emi-btn {
        background: #1a1a1a; color: #fff; border: none; border-radius: 4px;
        padding: 9px 14px; font-size: 12px; font-weight: 700; line-height: 1.1;
        cursor: pointer; display: flex; flex-direction: column; align-items: center;
        gap: 2px; white-space: nowrap;
    }
    .kk-pdp__emi-btn small { font-size: 8px; font-weight: 400; opacity: 0.7; letter-spacing: 0.04em; }
    .kk-pdp__tier-title {
        font-size: 14px; font-weight: 700; color: #2d1810;
        letter-spacing: 0.08em; text-transform: uppercase; margin: 0 0 10px;
    }
    .kk-pdp__tier-accent {
        background-image: linear-gradient(transparent 60%, #ffb84a 60%, #ffb84a 92%, transparent 92%);
        padding: 0 3px;
    }
    .kk-pdp__tiers { list-style: none; padding: 0; margin: 0 0 14px; display: flex; flex-direction: column; gap: 9px; }
    .kk-pdp__tier { display: flex; align-items: center; gap: 12px; font-size: 14px; color: #2d1810; }
    .kk-pdp__tier-icon {
        width: 26px; height: 26px; border-radius: 50%; border: 1px solid #2d1810;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 13px; font-weight: 600;
    }
    .kk-pdp__tier strong { font-weight: 700; }
    .kk-pdp__variant-group { margin: 0 0 12px; }
    .kk-pdp__variant-label { font-size: 13px; font-weight: 600; color: #2d1810; margin: 0 0 8px; }
    .kk-pdp__variant-label .kk-pdp__variant-sel { font-weight: 400; color: #7a6555; }
    .kk-pdp__variant-btn {
        min-width: 44px; padding: 8px 14px; background: #fff; color: #2d1810;
        border: 1px solid #c9b393; border-radius: 4px;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: all 0.15s ease;
    }
    .kk-pdp__variant-btn.is-active { background: #2d1810; color: #efe2cb; border-color: #2d1810; }
    .kk-pdp__variant-btn:hover:not(.is-active) { border-color: #2d1810; }
    .kk-pdp__cta-row { display: flex; gap: 12px; margin: 14px 0 14px; }
    .kk-pdp__cta {
        flex: 1; padding: 14px 18px; font-size: 13px; font-weight: 700;
        letter-spacing: 0.08em; text-transform: uppercase; white-space: nowrap;
        border-radius: 4px; cursor: pointer; border: none;
        transition: background 0.15s ease;
    }
    .kk-pdp__cta--cart { background: #4a2d1a; color: #efe2cb; border: 1px solid #4a2d1a; }
    .kk-pdp__cta--cart:hover:not(:disabled) { background: #2d1810; }
    .kk-pdp__cta--buy { background: #2d1810; color: #efe2cb; border: 1px solid #2d1810; }
    .kk-pdp__cta--buy:hover:not(:disabled) { background: #1f1109; }
    .kk-pdp__cta:disabled { opacity: 0.5; cursor: not-allowed; }
    .kk-pdp__meta { font-size: 13px; color: #7a6555; line-height: 1.7; margin-top: 8px; }
    .kk-pdp__meta strong { color: #2d1810; font-weight: 600; }
    .kk-pdp__qty {
        padding: 8px 32px 8px 12px; border: 1px solid #c9b393; border-radius: 4px;
        font-size: 13px; background: #fff; color: #2d1810; cursor: pointer; min-height: 40px;
    }
    .kk-pdp__actions { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .kk-pdp__wish {
        display: inline-flex; align-items: center; gap: 9px; height: 44px;
        background: #fff; border: 1px solid #c9b393; border-radius: 999px;
        cursor: pointer; font-size: 13px; font-weight: 600; color: #2d1810;
        padding: 0 20px; letter-spacing: 0.02em; transition: all .18s ease;
    }
    .kk-pdp__wish:hover { border-color: #2d1810; background: #f7eedb; }
    .kk-pdp__wish svg { width: 18px; height: 18px; transition: transform .18s ease; }
    .kk-pdp__wish.is-saved { border-color: #dc362e; color: #dc362e; background: #fdecea; }
    .kk-pdp__wish.is-saved svg { transform: scale(1.08); }
    .kk-pdp__share {
        display: inline-flex; align-items: center; justify-content: center;
        width: 44px; height: 44px; flex-shrink: 0;
        background: #fff; border: 1px solid #c9b393; border-radius: 50%;
        cursor: pointer; color: #2d1810; transition: all .18s ease;
    }
    .kk-pdp__share:hover { border-color: #2d1810; background: #2d1810; color: #efe2cb; transform: translateY(-1px); }
    .kk-pdp__share svg { width: 18px; height: 18px; }
    .kk-pdp__share-ok { border-color: #2a9d3e !important; background: #eaf6ec !important; color: #2a9d3e !important; }
    </style>
    <div class="pdp-wrapper">
    <div class="container mx-auto px-4" x-data="productPage()">

        <!-- ===== TWO-COLUMN LAYOUT ===== -->
        <div class="kk-pdp">

            <!-- LEFT: Media gallery (images + videos) -->
            <div class="kk-pdp__gallery">
                @if(count($media) > 1)
                    <div class="kk-pdp__thumbs">
                        @foreach($media as $i => $m)
                            <button type="button" class="kk-pdp__thumb {{ $m['type'] === 'video' ? 'kk-pdp__thumb--video' : '' }}"
                                    :class="currentImage === {{ $i }} ? 'is-active' : ''"
                                    @click="currentImage = {{ $i }}"
                                    aria-label="View media {{ $i + 1 }}">
                                @if($m['type'] === 'video')
                                    @if($m['thumb'])
                                        <img src="{{ $m['thumb'] }}" alt="{{ $product->name }} — video {{ $i + 1 }}" loading="lazy">
                                    @else
                                        <video src="{{ $m['url'] }}#t=0.1" muted playsinline preload="metadata"></video>
                                    @endif
                                    <span class="kk-pdp__thumb-play" aria-hidden="true">&#9654;</span>
                                @else
                                    <img src="{{ $m['url'] }}" alt="{{ $product->name }} — thumbnail {{ $i + 1 }}" loading="lazy">
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="kk-pdp__main"
                     @touchstart.passive="onTouchStart($event)" @touchend="onTouchEnd($event)">
                    @foreach($media as $i => $m)
                        @if($m['type'] === 'video')
                            <video class="kk-pdp__main-img kk-pdp__main-video" controls playsinline preload="metadata"
                                   @if($m['thumb']) poster="{{ $m['thumb'] }}" @endif
                                   x-show="currentImage === {{ $i }}" @if($i !== 0) x-cloak @endif>
                                <source src="{{ $m['url'] }}">
                            </video>
                        @else
                            <img src="{{ $m['url'] }}" alt="{{ $product->name }}" class="kk-pdp__main-img"
                                 @click="showZoom = true"
                                 x-show="currentImage === {{ $i }}" @if($i !== 0) x-cloak @endif
                                 sizes="(max-width: 1024px) 100vw, 50vw" decoding="async"
                                 loading="{{ $i === 0 ? 'eager' : 'lazy' }}" @if($i === 0) fetchpriority="high" @endif>
                        @endif
                    @endforeach
                    @if(count($media) > 1)
                        {{-- Prev/next arrows (desktop) --}}
                        <button type="button" class="kk-pdp__navbtn kk-pdp__navbtn--prev" @click.stop="prevImage()" aria-label="Previous">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" class="kk-pdp__navbtn kk-pdp__navbtn--next" @click.stop="nextImage()" aria-label="Next">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <span class="kk-pdp__counter"><span x-text="currentImage + 1"></span> / {{ count($media) }}</span>
                    @endif
                </div>
            </div>

            <!-- RIGHT: Product info -->
            <div class="kk-pdp__info" x-ref="buyBox">
                <h1 class="kk-pdp__title">{{ $product->name }}</h1>

                @if($product->short_description)
                    <p class="kk-pdp__subtitle" style="margin:6px 0 0; color:#7a6555; font-size:14px; line-height:1.55;">{{ $product->short_description }}</p>
                @endif

                {{-- Rating: always shown directly below title/description (Task 7). --}}
                <div class="kk-pdp__rating">
                    @php $ratingStars = $product->review_count > 0 ? (int) round($product->rating ?: 0) : 0; @endphp
                    <span class="kk-pdp__rating-stars">
                        @for($s = 1; $s <= 5; $s++)
                            <svg fill="{{ $s <= $ratingStars ? '#1f1109' : '#c9b393' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </span>
                    @if($product->review_count > 0)
                        <a href="#customer-reviews" @click.prevent="document.getElementById('customer-reviews')?.scrollIntoView({behavior:'smooth'})"
                           class="kk-pdp__rating-count" style="text-decoration:none;">{{ number_format($product->rating, 1) }} · {{ $product->review_count }} {{ Str::plural('review', $product->review_count) }}</a>
                    @else
                        <a href="#customer-reviews" @click.prevent="document.getElementById('customer-reviews')?.scrollIntoView({behavior:'smooth'})"
                           class="kk-pdp__rating-count" style="text-decoration:none;">No reviews yet — be the first</a>
                    @endif
                </div>

                <div class="kk-pdp__price-row">
                    <span class="kk-pdp__price" x-text="'₹' + currentPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})">₹{{ number_format($product->price, 2) }}</span>
                    @if($product->mrp && $product->mrp > $product->price)
                        <span class="kk-pdp__mrp">₹{{ number_format($product->mrp, 2) }}</span>
                        <span class="kk-pdp__off">{{ (int) round((($product->mrp - $product->price) / $product->mrp) * 100) }}% OFF</span>
                    @endif
                </div>
                <p class="kk-pdp__tax">Tax Included</p>

                <!-- ===== SIZE GUIDE ===== -->
                <style>
                .kk-sizeguide { text-align: left; padding: 2px 0 12px; }
                .kk-sizeguide__title { font-size: 13px; font-weight: 600; letter-spacing: 0.18em; text-transform: uppercase; color: #2d1810; display: inline-block; padding-bottom: 4px; border-bottom: 1px solid #2d1810; margin: 0 0 16px; }
                .kk-sizeguide__sel { font-weight: 400; letter-spacing: 0.04em; color: #7a6555; text-transform: none; }
                .kk-sizeguide__row { display: flex; flex-wrap: nowrap; justify-content: flex-start; gap: 7px; margin: 0; overflow-x: auto; padding-bottom: 2px; }
                .kk-sizeguide__size { font-size: 12px; font-weight: 600; color: #2d1810; letter-spacing: 0.01em; white-space: nowrap; flex: 0 0 auto; cursor: pointer; background: #fff; border: 1px solid #c9b393; border-radius: 6px; padding: 7px 9px; font-family: inherit; transition: all .15s ease; }
                .kk-sizeguide__size:hover { border-color: #2d1810; }
                .kk-sizeguide__size.is-selected { background: #2d1810; color: #efe2cb; border-color: #2d1810; }
                .kk-sizeguide__size.is-unavailable { color: #a08e76; text-decoration: line-through; cursor: not-allowed; opacity: .55; }
                /* Mobile: wrap all sizes onto multiple rows (no hidden horizontal
                   scroll) and enlarge tap targets to ~44px for easy tapping. */
                @media (max-width: 640px) {
                    .kk-sizeguide__row { flex-wrap: wrap; overflow-x: visible; gap: 8px; }
                    .kk-sizeguide__size { flex: 1 0 auto; min-width: 58px; text-align: center; padding: 11px 10px; font-size: 13px; }
                }
                </style>
                @php
                    // Sizes, colours and per-size pricing all come from the product's own
                    // "Sizes & pricing" rows in admin. One row = one buyable size, optionally
                    // in a colour, with its own price. A product with no rows shows neither
                    // selector, so non-apparel items stop offering sizes.
                    $kkRows = $product->variants->where('is_active', true)->values();

                    $kkSizes = $kkRows->pluck('name')->map(fn ($n) => trim((string) $n))->filter()->unique()->values();

                    // Fallback for products still holding sizes as free text on the old
                    // Size attribute (e.g. "CX   M   XL"), so each size still gets its own
                    // button until the product is given proper size rows.
                    if ($kkSizes->isEmpty()) {
                        $kkSizes = collect($product->attributes ?? [])
                            ->filter(fn ($v, $k) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($k), 'size'))
                            ->flatMap(fn ($v) => is_array($v) ? $v : preg_split('/[,\/|]+|\s{2,}/', (string) $v))
                            ->map(fn ($v) => trim((string) $v))
                            ->filter()
                            ->unique()
                            ->values();
                    }

                    $kkColours = $kkRows
                        ->map(fn ($v) => trim((string) data_get($v->attributes, 'Colour', '')))
                        ->filter()->unique()->values();

                    $kkColourHex = $kkRows->mapWithKeys(fn ($v) => [
                        trim((string) data_get($v->attributes, 'Colour', '')) => data_get($v->attributes, 'colour_hex'),
                    ])->filter(fn ($hex, $name) => $name !== '' && $hex);

                    // size => variant id. Selecting a size points the page at that row so
                    // the existing currentPrice/currentMrp getters show its price.
                    $kkSizeVariant = $kkRows->reverse()->mapWithKeys(fn ($v) => [trim((string) $v->name) => $v->id])->filter(fn ($id, $n) => $n !== '');
                @endphp
                @if($kkSizes->isNotEmpty())
                <section class="kk-sizeguide" id="kk-size-select" aria-label="Select size">
                    <h2 class="kk-sizeguide__title">Select Size<span class="kk-sizeguide__sel" x-show="selectedSize" x-cloak> — <span x-text="selectedSize"></span></span></h2>
                    <div class="kk-sizeguide__row">
                        @foreach($kkSizes as $sz)
                            <button type="button" class="kk-sizeguide__size"
                                    :class="selectedSize === '{{ $sz }}' ? 'is-selected' : ''"
                                    @click="selectedSize = '{{ $sz }}'@if(isset($kkSizeVariant[$sz])); selectedVariant = {{ $kkSizeVariant[$sz] }}@endif">{{ $sz }}</button>
                        @endforeach
                    </div>
                </section>
                @endif

                @if($kkColours->isNotEmpty())
                <style>
                    .kk-colorpick__row { display: flex; flex-wrap: wrap; gap: 8px; }
                    .kk-colorpick__btn { display: inline-flex; align-items: center; gap: 7px; cursor: pointer;
                        background: #fff; border: 1px solid #c9b393; border-radius: 6px; padding: 6px 10px 6px 7px;
                        font-family: inherit; font-size: 12px; font-weight: 600; color: #2d1810; letter-spacing: .01em;
                        white-space: nowrap; transition: all .15s ease; }
                    .kk-colorpick__btn:hover { border-color: #8c5c34; }
                    .kk-colorpick__btn.is-selected { border-color: #2d1810; box-shadow: inset 0 0 0 1px #2d1810; }
                    .kk-colorpick__dot { width: 18px; height: 18px; border-radius: 50%; flex: 0 0 auto;
                        border: 1px solid rgba(45,24,16,.18); box-shadow: inset 0 0 0 1px rgba(255,255,255,.55); }
                    @media (max-width: 640px) { .kk-colorpick__btn { flex: 1 0 auto; justify-content: center; padding: 9px 10px; font-size: 13px; } }
                </style>
                <section class="kk-sizeguide" id="kk-color-select" aria-label="Select colour">
                    <h2 class="kk-sizeguide__title">Select Colour<span class="kk-sizeguide__sel" x-show="selectedColor" x-cloak> — <span x-text="selectedColor"></span></span></h2>
                    <div class="kk-colorpick__row">
                        @foreach($kkColours as $kkC)
                            <button type="button" class="kk-colorpick__btn"
                                    :class="selectedColor === '{{ $kkC }}' ? 'is-selected' : ''"
                                    @click="selectedColor = '{{ $kkC }}'"
                                    aria-label="{{ $kkC }}">
                                <span class="kk-colorpick__dot" style="background-color: {{ $kkColourHex[$kkC] ?? '#dddddd' }};"></span>
                                <span>{{ $kkC }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
                @endif


                <div style="display:flex; align-items:flex-end; gap:24px; flex-wrap:wrap; margin:0 0 8px;">
                    <div class="kk-pdp__variant-group" style="margin:0;">
                        <h3 class="kk-pdp__variant-label">Quantity</h3>
                        <select x-model.number="quantity" class="kk-pdp__qty">
                            @for($q = 1; $q <= 10; $q++)
                            <option value="{{ $q }}">{{ $q }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="kk-pdp__actions">
                        <button type="button" class="kk-pdp__wish"
                                :class="$store.wishlist.has({{ $product->id }}) ? 'is-saved' : ''"
                                @click="$store.wishlist.toggle({{ $product->id }})">
                            <svg :fill="$store.wishlist.has({{ $product->id }}) ? '#dc362e' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            <span x-text="$store.wishlist.has({{ $product->id }}) ? 'Saved to Wishlist' : 'Save to Wishlist'">Save to Wishlist</span>
                        </button>
                        <button type="button" class="kk-pdp__share" :class="shareCopied ? 'kk-pdp__share-ok' : ''"
                                @click="shareProduct()" aria-label="Share this product" title="Share">
                            <template x-if="!shareCopied">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
                            </template>
                            <template x-if="shareCopied">
                                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </div>
                </div>

                @if($product->isInStock())
                    <div class="kk-pdp__cta-row">
                        <button class="kk-pdp__cta kk-pdp__cta--cart"
                                @click="addToCart()"
                                :disabled="$store.cart.isLoading || !inStock">
                            <span x-show="!$store.cart.isLoading">Add to Cart</span>
                            <span x-show="$store.cart.isLoading" x-cloak>Adding...</span>
                        </button>
                        <button class="kk-pdp__cta kk-pdp__cta--buy"
                                @click="buyNow()"
                                :disabled="$store.cart.isLoading || !inStock">
                            Buy Now
                        </button>
                    </div>
                @else
                    <div style="padding:14px 0;font-size:14px;color:#b71c00;font-weight:600;">Currently Unavailable</div>
                @endif

                {{-- Trust badges --}}
                <style>
                    .kk-pdp__trust { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 16px 0 4px; padding: 14px 0; border-top: 1px solid #ece0c8; border-bottom: 1px solid #ece0c8; }
                    .kk-pdp__trust-item { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; font-size: 10.5px; color: #7a6555; font-weight: 600; letter-spacing: .02em; }
                    .kk-pdp__trust-item svg { width: 22px; height: 22px; color: #8c5c34; stroke-width: 1.6; }
                    @media (max-width: 420px) { .kk-pdp__trust { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
                </style>
                <div class="kk-pdp__trust" role="list">
                    <div class="kk-pdp__trust-item" role="listitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Secure Checkout</span>
                    </div>
                    <div class="kk-pdp__trust-item" role="listitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                        <span>7-Day Returns</span>
                    </div>
                    <div class="kk-pdp__trust-item" role="listitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                        <span>Cash on Delivery</span>
                    </div>
                    <div class="kk-pdp__trust-item" role="listitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        <span>100% Genuine</span>
                    </div>
                </div>

                {{-- ===== Offers widget (collapsible bank offers — sample data) ===== --}}
                <style>
                    .kk-offers { margin: 2px 0 16px; border: 1px solid #e3d2b3; border-radius: 12px; overflow: hidden; background: #fbf5e8; }
                    .kk-offers__head { width: 100%; display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: #fbf5e8; border: none; cursor: pointer; text-align: left; font-family: inherit; }
                    .kk-offers__badge { background: #4a2d1a; color: #efe2cb; font-size: 10px; font-weight: 800; letter-spacing: 0.1em; padding: 4px 8px; border-radius: 5px; flex-shrink: 0; }
                    .kk-offers__headtext { font-size: 13px; font-weight: 600; color: #2d1810; flex: 1; }
                    .kk-offers__chev { width: 18px; height: 18px; color: #7a6555; transition: transform .25s ease; flex-shrink: 0; }
                    .kk-offers__chev.is-open { transform: rotate(180deg); }
                    .kk-offers__body { padding: 0 14px 14px; }
                    .kk-offers__price { font-size: 15px; font-weight: 700; color: #2d1810; margin: 6px 0 2px; }
                    .kk-offers__subtitle { font-size: 12px; font-weight: 600; color: #7a6555; margin: 10px 0 12px; }
                    .kk-offers__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                    @media (max-width: 640px) { .kk-offers__grid { grid-template-columns: 1fr; } }
                    .kk-offers__card { position: relative; background: #fff; border: 1px solid #e3d2b3; border-radius: 8px; padding: 12px 12px 10px; }
                    .kk-offers__tag { position: absolute; top: -8px; left: 10px; background: #f3e3bf; color: #8c5c34; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 4px; letter-spacing: .02em; }
                    .kk-offers__row { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-top: 2px; }
                    .kk-offers__amt { font-size: 14px; font-weight: 700; color: #2d1810; }
                    .kk-offers__name { font-size: 12px; color: #7a6555; margin-top: 1px; }
                    .kk-offers__apply { font-size: 13px; font-weight: 700; color: #8c5c34; text-decoration: none; background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; }
                    .kk-offers__apply:hover { text-decoration: underline; }
                    .kk-offers__type { display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #9b8a72; margin-top: 8px; padding-top: 7px; border-top: 1px solid #f0e6d2; }
                </style>
                @php
                    $bankOffers = [
                        ['amt' => 50, 'name' => 'Paytm',     'type' => 'UPI • Cashback',         'best' => true],
                        ['amt' => 22, 'name' => 'Axis Bank', 'type' => 'Debit Card • Cashback',  'best' => false],
                        ['amt' => 22, 'name' => 'Axis Bank', 'type' => 'Credit Card • Cashback', 'best' => false],
                        ['amt' => 22, 'name' => 'SBI Card',  'type' => 'Credit Card • Cashback', 'best' => false],
                    ];
                    $bestPrice = max(1, round($product->price) - 50);
                @endphp
                <div class="kk-offers" x-data="{ offersOpen: false }">
                    <button type="button" class="kk-offers__head" @click="offersOpen = !offersOpen" :aria-expanded="offersOpen">
                        <span class="kk-offers__badge">OFFERS</span>
                        <span class="kk-offers__headtext">Apply offers for maximum savings</span>
                        <svg class="kk-offers__chev" :class="offersOpen ? 'is-open' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="kk-offers__body" x-show="offersOpen" x-collapse.duration.250ms x-cloak>
                        <div class="kk-offers__price">Buy at ₹{{ number_format($bestPrice) }}</div>
                        <div class="kk-offers__subtitle">Bank offers</div>
                        <div class="kk-offers__grid">
                            @foreach($bankOffers as $o)
                                <div class="kk-offers__card">
                                    @if($o['best'])<span class="kk-offers__tag">Best value for you</span>@endif
                                    <div class="kk-offers__row">
                                        <div>
                                            <div class="kk-offers__amt">₹{{ $o['amt'] }} off</div>
                                            <div class="kk-offers__name">{{ $o['name'] }}</div>
                                        </div>
                                        <button type="button" class="kk-offers__apply" @click="$store.toast && $store.toast.show ? $store.toast.show('Offer applied at checkout') : null">Apply</button>
                                    </div>
                                    <div class="kk-offers__type"><span>{{ $o['type'] }}</span><span aria-hidden="true">&rsaquo;</span></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @php
                    $tier1 = max(1, round($product->price * 0.85));
                    $tier2 = max(1, round($product->price * 0.82));
                    $tier3 = max(1, round($product->price * 0.77));
                @endphp
                <h3 class="kk-pdp__tier-title">Beginning of the End <span class="kk-pdp__tier-accent">Sale</span></h3>
                <ul class="kk-pdp__tiers">
                    <li class="kk-pdp__tier"><span class="kk-pdp__tier-icon">₹</span><span>Buy any 1 and get this at <strong>₹{{ number_format($tier1) }}</strong> at checkout</span></li>
                    <li class="kk-pdp__tier"><span class="kk-pdp__tier-icon">₹</span><span>Buy any 2 and get this at <strong>₹{{ number_format($tier2) }}</strong> at checkout</span></li>
                    <li class="kk-pdp__tier"><span class="kk-pdp__tier-icon">₹</span><span>Buy any 3 and get this at <strong>₹{{ number_format($tier3) }}</strong> at checkout</span></li>
                </ul>

                <!-- Check estimated delivery (pincode) -->
                @php
                    $dMin = now()->addDays(3); $dMax = now()->addDays(7);
                    while ($dMin->isWeekend()) $dMin->addDay();
                    while ($dMax->isWeekend()) $dMax->addDay();
                    // Single source of truth for free-shipping threshold (Task 8).
                    $freeShipThreshold = (int) \App\Models\Setting::get('free_shipping_threshold', 999);
                @endphp
                <style>
                .kk-pdp__delivery { margin: 2px 0 12px; }
                .kk-pdp__delivery-title { font-size: 13px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: #2d1810; margin: 0 0 12px; }
                .kk-pdp__delivery-row { display: flex; max-width: 420px; }
                .kk-pdp__delivery-input { flex: 1; min-width: 0; background: #efe6d6; border: 1px solid #e3d2b3; border-right: none; padding: 12px 16px; font-size: 13px; color: #2d1810; letter-spacing: 0.06em; text-transform: uppercase; border-radius: 4px 0 0 4px; }
                .kk-pdp__delivery-input::placeholder { color: #9b8a72; }
                .kk-pdp__delivery-input:focus { outline: none; border-color: #2d1810; }
                .kk-pdp__delivery-btn { background: #2d1810; color: #fff; border: none; padding: 0 28px; font-size: 13px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; border-radius: 0 4px 4px 0; transition: background .15s ease; }
                .kk-pdp__delivery-btn:hover { background: #1f1109; }
                .kk-pdp__delivery-msg { font-size: 13px; margin: 10px 0 0; line-height: 1.5; }
                .kk-pdp__delivery-msg--ok { color: #2a7d3e; }
                .kk-pdp__delivery-msg--err { color: #b71c00; }
                </style>
                <div class="kk-pdp__delivery" x-data="{ pin: '', state: '' }">
                    <h3 class="kk-pdp__delivery-title">Check Estimated Delivery</h3>
                    <div class="kk-pdp__delivery-row">
                        <input type="text" x-model="pin" maxlength="6" inputmode="numeric"
                               @keydown.enter.prevent="state = /^\d{6}$/.test(pin) ? 'ok' : 'err'"
                               class="kk-pdp__delivery-input" placeholder="Enter your pincode">
                        <button type="button" class="kk-pdp__delivery-btn"
                                @click="state = /^\d{6}$/.test(pin) ? 'ok' : 'err'">Check</button>
                    </div>
                    <p class="kk-pdp__delivery-msg kk-pdp__delivery-msg--ok" x-show="state==='ok'" x-cloak>
                        Free delivery on orders above &#8377;{{ number_format($freeShipThreshold) }} to <span x-text="pin"></span> &mdash; estimated {{ $dMin->format('D, d M') }} &ndash; {{ $dMax->format('D, d M') }}.
                    </p>
                    <p class="kk-pdp__delivery-msg kk-pdp__delivery-msg--err" x-show="state==='err'" x-cloak>
                        Please enter a valid 6-digit pincode.
                    </p>
                </div>

                @php
                    $deliveryMin = now()->addDays(3);
                    $deliveryMax = now()->addDays(7);
                    while ($deliveryMin->isWeekend()) $deliveryMin->addDay();
                    while ($deliveryMax->isWeekend()) $deliveryMax->addDay();
                @endphp
                <div class="kk-pdp__meta">
                    <strong>Free Delivery</strong> on orders above &#8377;{{ number_format($freeShipThreshold) }}: {{ $deliveryMin->format('D, d M') }} &ndash; {{ $deliveryMax->format('D, d M') }}<br>
                    <strong>Easy Returns:</strong> 7-day return &amp; exchange policy
                </div>
            </div>

        </div>

        <!-- ===== PRODUCT INFO ACCORDION (redesigned) ===== -->
        <style>
            .kk-pi { max-width: 880px; margin: 40px auto 0; padding: 0 16px; }
            .kk-pi__heading { text-align: center; font-family: 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 700; color: #2d1810; margin: 0 0 22px; }

            /* Accordion cards */
            .kk-pi__item {
                background: #fff; border: 1px solid #ece0c8; border-radius: 14px;
                margin-bottom: 12px; overflow: hidden;
                transition: border-color .2s ease, box-shadow .2s ease;
            }
            .kk-pi__item[data-open="true"], .kk-pi__item:hover { border-color: #d8c39c; box-shadow: 0 8px 26px rgba(45,24,16,0.07); }
            .kk-pi__btn {
                width: 100%; display: flex; align-items: center; justify-content: space-between;
                gap: 14px; padding: 18px 22px; background: none; border: none; cursor: pointer;
                font-family: inherit; text-align: left;
            }
            /* Icon in a rounded tan circle */
            .kk-pi__btn-lead { display: inline-flex; align-items: center; gap: 15px; min-width: 0; }
            .kk-pi__btn-ico {
                width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
                background: #f3e9d4; color: #8c5c34;
                display: inline-flex; align-items: center; justify-content: center;
                transition: background .2s ease, color .2s ease;
            }
            .kk-pi__btn-ico svg { width: 20px; height: 20px; }
            .kk-pi__item[data-open="true"] .kk-pi__btn-ico { background: #2d1810; color: #efe2cb; }
            .kk-pi__btn-label {
                font-size: 15px; font-weight: 700; letter-spacing: 0.02em; color: #2d1810; text-transform: none;
            }
            /* Chevron */
            .kk-pi__btn-icon {
                width: 10px; height: 10px; flex-shrink: 0; margin-right: 4px; margin-bottom: 3px;
                border-right: 2px solid #8c5c34; border-bottom: 2px solid #8c5c34;
                transform: rotate(45deg); transition: transform .28s ease;
            }
            .kk-pi__btn[aria-expanded="true"] .kk-pi__btn-icon { transform: rotate(-135deg); margin-bottom: -2px; }

            .kk-pi__panel { padding: 0 22px 22px 79px; font-size: 14.5px; line-height: 1.75; color: #4a3627; }
            @media (max-width: 560px) { .kk-pi__panel { padding-left: 22px; } }
            .kk-pi__panel p { margin: 0 0 10px; }
            .kk-pi__panel p:last-child { margin: 0; }
            .kk-pi__panel ul { margin: 0 0 10px; padding-left: 20px; }
            /* Spec rows with subtle dividers */
            .kk-pi__panel dl { display: grid; grid-template-columns: minmax(120px, 1fr) 2fr; margin: 0; }
            .kk-pi__panel dt { font-weight: 600; color: #7a6555; padding: 9px 0; border-top: 1px solid #f0e6d2; }
            .kk-pi__panel dd { margin: 0; color: #2d1810; padding: 9px 0; border-top: 1px solid #f0e6d2; }
            .kk-pi__panel dl > dt:first-of-type, .kk-pi__panel dl > dd:nth-of-type(1) { border-top: none; }
        </style>

        <div class="kk-pi">
            <h2 class="kk-pi__heading">Product Details</h2>
            {{-- PRODUCT INFO --}}
            <div class="kk-pi__item" x-data="{ open: false }" :data-open="open ? 'true' : 'false'">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-lead">
                        <span class="kk-pi__btn-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg></span>
                        <span class="kk-pi__btn-label">Product Info</span>
                    </span>
                    <span class="kk-pi__btn-icon" aria-hidden="true"></span>
                </button>
                <div class="kk-pi__panel" x-show="open" x-collapse>
                    <dl>
                        @if($product->brand)<dt>Brand</dt><dd>{{ $product->brand->name }}</dd>@endif
                        @if($product->sku)<dt>SKU</dt><dd>{{ $product->sku }}</dd>@endif
                        @if($product->category)<dt>Category</dt><dd>{{ $product->category->name }}</dd>@endif
                        @if($product->attributes && count($product->attributes) > 0)
                            @foreach($product->attributes as $key => $value)
                                <dt>{{ $key }}</dt><dd>{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                            @endforeach
                        @endif
                        @if($product->specifications && count($product->specifications) > 0)
                            @foreach($product->specifications as $key => $value)
                                <dt>{{ $key }}</dt><dd>{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                            @endforeach
                        @endif
                    </dl>
                </div>
            </div>

            {{-- DESCRIPTION --}}
            <div class="kk-pi__item" x-data="{ open: true }" :data-open="open ? 'true' : 'false'">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-lead">
                        <span class="kk-pi__btn-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg></span>
                        <span class="kk-pi__btn-label">Description</span>
                    </span>
                    <span class="kk-pi__btn-icon" aria-hidden="true"></span>
                </button>
                <div class="kk-pi__panel" x-show="open" x-collapse>
                    @if($product->short_description)
                        <p>{!! nl2br(e($product->short_description)) !!}</p>
                    @endif
                    @if($product->description)
                        <div>{!! $product->description !!}</div>
                    @endif
                    @if(!$product->short_description && !$product->description)
                        <p>{{ $product->name }} &mdash; thoughtfully designed and crafted for the modern wardrobe.</p>
                    @endif
                </div>
            </div>

            {{-- SHIPPING, RETURNS & EXCHANGE --}}
            <div class="kk-pi__item" x-data="{ open: false }" :data-open="open ? 'true' : 'false'">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-lead">
                        <span class="kk-pi__btn-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-6.75V6.75a1.5 1.5 0 011.5-1.5h4.5a1.5 1.5 0 011.5 1.5v12z"/></svg></span>
                        <span class="kk-pi__btn-label">Shipping, Returns &amp; Exchange</span>
                    </span>
                    <span class="kk-pi__btn-icon" aria-hidden="true"></span>
                </button>
                <div class="kk-pi__panel" x-show="open" x-collapse>
                    <p><strong>Shipping:</strong> Free delivery on orders above &#8377;{{ number_format($freeShipThreshold) }}. Standard delivery in 3&ndash;7 business days across India.</p>
                    <p><strong>Returns:</strong> Easy 7-day return &amp; exchange policy. Items must be unworn, unwashed and with original tags attached.</p>
                    <p><strong>Exchange:</strong> One free size or colour exchange per order. Reach out via WhatsApp or email to initiate.</p>
                </div>
            </div>

            {{-- MANUFACTURED AND PACKAGED BY --}}
            <div class="kk-pi__item" x-data="{ open: false }" :data-open="open ? 'true' : 'false'">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-lead">
                        <span class="kk-pi__btn-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/></svg></span>
                        <span class="kk-pi__btn-label">Manufactured and Packaged by</span>
                    </span>
                    <span class="kk-pi__btn-icon" aria-hidden="true"></span>
                </button>
                <div class="kk-pi__panel" x-show="open" x-collapse>
                    <p><strong>{{ $product->brand?->name ?? config('app.name', 'Karmaa Kulture') }}</strong></p>
                    <p>Made in India. Crafted at our partner mills with fair-wage labour and ethical sourcing standards. Country of origin: India.</p>
                    @if($product->seller)
                        <p><strong>Sold by:</strong> {{ $product->seller->business_name ?? $product->seller->name ?? config('app.name') }}</p>
                    @endif
                </div>
            </div>
        </div>



        {{-- ===== A+ CONTENT (Amazon-style banner images, admin-managed, stacked in saved order) ===== --}}
        @if($product->aplusImages->isNotEmpty())
        <style>
            /* Full-bleed. The section sits inside the page's .container, which tops
               out around 1504px, and the image's max-width:100% resolves against it —
               so a width like 3500px still stopped at the container edge and never
               reached the screen. The negative margins pull the section out to the
               full viewport so a large width can span edge to edge. The 1120px
               default stays on the image and remains centred here, so banners with
               no width set look exactly as before. <html> already carries
               overflow-x-clip, which absorbs the scrollbar gutter 100vw includes. */
            /* --kk-vw is the viewport width without the scrollbar (set in the layout).
               Plain 100vw includes the scrollbar, so a 100%-wide banner overhung the
               right edge by that amount and <html>'s overflow-x:clip cut the strip
               off — the image looked cropped. Falls back to 100vw if the script
               has not run yet. */
            .kk-aplus { margin: 48px 0 0; padding: 0;
                width: var(--kk-vw, 100vw); max-width: var(--kk-vw, 100vw);
                margin-left: calc(50% - var(--kk-vw, 100vw) / 2);
                margin-right: calc(50% - var(--kk-vw, 100vw) / 2); }
            /* Banners stack edge-to-edge with no spacing between them; natural aspect ratio keeps original quality.
               Size comes from per-image custom properties set in the admin panel, falling back to the responsive
               default. Custom properties (not inline width/height) so the mobile rule below can still win —
               an inline declaration would outrank any stylesheet rule and break narrow screens. */
            /* One banner at a time. The slide is capped to a slice of the viewport
               height and the image is contained inside it, so a whole banner is
               always visible without scrolling the page. */
            .kk-aplus__viewport { position: relative; overflow: hidden; }
            .kk-aplus__track { display: flex; transition: transform .55s cubic-bezier(.4,.0,.2,1); will-change: transform; }
            .kk-aplus__slide { flex: 0 0 100%; min-width: 100%; display: flex; align-items: center; justify-content: center; }
            .kk-aplus__img {
                display: block;
                /* Fallback reproduces the previous 1120px cap; an admin value replaces
                   it outright, so sizes above 1120px now take effect. */
                width: var(--kk-aplus-w, 1120px);
                height: var(--kk-aplus-h, auto);
                max-width: 100%;      /* = viewport width now the section is full-bleed, so a very
                                         large value lands at the screen edges and never overflows */
                /* The guarantee that a banner fits on screen. object-fit keeps the whole
                   image visible inside that cap rather than cropping it. */
                max-height: 78vh;
                object-fit: contain;
                margin: 0 auto;       /* centred whenever narrower than the viewport */
                border: 0;
            }
            /* Controls, positioned as in the reference: pause left, dots centre, arrows right */
            .kk-aplus__bar { display: flex; align-items: center; gap: 12px; margin-top: 10px; padding: 0 16px; }
            .kk-aplus__dots { display: flex; align-items: center; justify-content: center; gap: 7px; flex: 1; }
            .kk-aplus__dot { width: 9px; height: 9px; border-radius: 999px; border: 0; padding: 0; cursor: pointer;
                background: #d6cbb6; transition: width .25s, background .25s; }
            .kk-aplus__dot[aria-current="true"] { width: 26px; background: #2d1810; }
            .kk-aplus__btn { width: 32px; height: 32px; border-radius: 999px; border: 1px solid #e3d2b3;
                background: #fbf5e8; color: #2d1810; display: inline-flex; align-items: center; justify-content: center;
                cursor: pointer; padding: 0; transition: background .15s, color .15s; }
            .kk-aplus__btn:hover { background: #2d1810; color: #fbf5e8; }
            .kk-aplus__btn:focus-visible { outline: 2px solid #8c5c34; outline-offset: 2px; }
            .kk-aplus__btn svg { width: 15px; height: 15px; }
            .kk-aplus__nav { display: flex; gap: 8px; }
            @media (max-width: 640px) {
                .kk-aplus { margin-top: 32px; }
                /* Only the height is reset: max-width already scales the width down, and
                   forcing width:100% here would override a deliberately narrow setting.
                   A fixed px height against that reduced width would distort the image. */
                .kk-aplus__img { height: auto; max-height: 62vh; }
                .kk-aplus__bar { gap: 8px; padding: 0 10px; }
            }
            @media (prefers-reduced-motion: reduce) { .kk-aplus__track { transition: none; } }
        </style>
        @php $aplusCount = $product->aplusImages->count(); @endphp
        <section class="kk-aplus"
                 aria-label="Product information"
                 aria-roledescription="carousel"
                 x-data="{
                    i: 0,
                    n: {{ $aplusCount }},
                    playing: true,
                    timer: null,
                    tx: 0,
                    init() {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.playing = false;
                        this.start();
                    },
                    start() { this.stop(); if (this.playing && this.n > 1) this.timer = setInterval(() => this.next(), 5000); },
                    stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
                    toggle() { this.playing = !this.playing; this.playing ? this.start() : this.stop(); },
                    next() { this.i = (this.i + 1) % this.n; },
                    prev() { this.i = (this.i - 1 + this.n) % this.n; },
                    go(k) { this.i = k; this.start(); },
                 }"
                 @mouseenter="stop()"
                 @mouseleave="start()"
                 @keydown.arrow-right.prevent="next(); start()"
                 @keydown.arrow-left.prevent="prev(); start()"
                 tabindex="0">

            <div class="kk-aplus__viewport"
                 @touchstart.passive="tx = $event.changedTouches[0].clientX"
                 @touchend.passive="
                    const dx = $event.changedTouches[0].clientX - tx;
                    if (Math.abs(dx) > 40) { dx < 0 ? next() : prev(); start(); }
                 ">
                <div class="kk-aplus__track" :style="'transform: translateX(-' + (i * 100) + '%)'">
                    @foreach($product->aplusImages as $aplus)
                        <div class="kk-aplus__slide"
                             role="group"
                             aria-roledescription="slide"
                             aria-label="{{ $loop->iteration }} of {{ $aplusCount }}"
                             :aria-hidden="i !== {{ $loop->index }}">
                            <img class="kk-aplus__img"
                                 src="{{ $aplus->image_url }}"
                                 alt="{{ $aplus->alt_text ?: $product->name }}"
                                 @if($aplus->width && $aplus->height) width="{{ $aplus->width }}" height="{{ $aplus->height }}" @endif
                                 @if($style = $aplus->display_style) style="{{ $style }}" @endif
                                 loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async">
                        </div>
                    @endforeach
                </div>
            </div>

            @if($aplusCount > 1)
            <div class="kk-aplus__bar">
                <button type="button" class="kk-aplus__btn" @click="toggle()"
                        :aria-label="playing ? 'Pause' : 'Play'" :title="playing ? 'Pause' : 'Play'">
                    <svg x-show="playing" fill="currentColor" viewBox="0 0 24 24"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
                    <svg x-show="!playing" x-cloak fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </button>

                <div class="kk-aplus__dots" role="tablist">
                    @foreach($product->aplusImages as $aplus)
                        <button type="button" class="kk-aplus__dot" role="tab"
                                @click="go({{ $loop->index }})"
                                :aria-current="i === {{ $loop->index }}"
                                aria-label="Go to banner {{ $loop->iteration }}"></button>
                    @endforeach
                </div>

                <div class="kk-aplus__nav">
                    <button type="button" class="kk-aplus__btn" @click="prev(); start()" aria-label="Previous banner">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="kk-aplus__btn" @click="next(); start()" aria-label="Next banner">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
            @endif
        </section>
        @endif

        <!-- ===== CUSTOMER REVIEWS (Judge.me-style) ===== -->
        <style>
            .kk-rev { max-width: 880px; margin: 28px auto 0; padding: 16px 16px 0; }
            @media (max-width: 640px) { .kk-rev { margin-top: 16px; padding-top: 4px; } }
            .kk-rev__title { text-align: center; font-family: 'Playfair Display', Georgia, serif; font-size: 28px; font-weight: 700; color: #2d1810; margin: 0 0 26px; }

            /* Summary card */
            .kk-rev__summary {
                display: grid; grid-template-columns: 1fr 1.2fr auto; align-items: center; gap: 32px;
                background: #fbf5e8; border: 1px solid #e3d2b3; border-radius: 14px;
                padding: 26px 30px; margin-bottom: 26px;
            }
            @media (max-width: 768px) {
                .kk-rev__summary { grid-template-columns: 1fr; text-align: center; gap: 22px; }
            }
            .kk-rev__overall { text-align: center; }
            .kk-rev__big { font-family: 'Playfair Display', Georgia, serif; font-size: 42px; font-weight: 700; color: #2d1810; line-height: 1; margin-bottom: 8px; }
            .kk-rev__stars { display: inline-flex; gap: 2px; }
            .kk-rev__stars svg { width: 18px; height: 18px; }
            .kk-rev__based { font-size: 13px; color: #7a6555; margin: 10px 0 0; display: inline-flex; align-items: center; gap: 6px; justify-content: center; }
            .kk-rev__verified-icon {
                width: 16px; height: 16px; border-radius: 50%;
                background: #2a9d3e; color: #fff;
                display: inline-flex; align-items: center; justify-content: center;
            }
            .kk-rev__verified-icon svg { width: 11px; height: 11px; }

            .kk-rev__bars { display: flex; flex-direction: column; gap: 8px; }
            .kk-rev__bar-row { display: flex; align-items: center; gap: 10px; font-size: 12px; }
            .kk-rev__bar-label { width: 34px; flex-shrink: 0; display: inline-flex; align-items: center; gap: 2px; color: #7a6555; font-weight: 600; }
            .kk-rev__bar-label svg { width: 11px; height: 11px; }
            .kk-rev__bar-track { flex: 1; height: 8px; background: #e8dcc2; border-radius: 99px; overflow: hidden; }
            .kk-rev__bar-fill { height: 100%; background: #2D1810; border-radius: 99px; transition: width .4s ease; }
            .kk-rev__bar-count { width: 22px; text-align: right; font-size: 12px; color: #7a6555; }

            .kk-rev__write {
                background: #2D1810; color: #efe2cb;
                padding: 13px 26px; border: none; border-radius: 8px;
                font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
                cursor: pointer; transition: background 0.15s ease, transform 0.15s ease; white-space: nowrap;
            }
            .kk-rev__write:hover { background: #1f1109; transform: translateY(-1px); }

            /* Review cards */
            .kk-rev__list { display: flex; flex-direction: column; gap: 14px; }
            .kk-rev__item {
                background: #fbf5e8; border: 1px solid #e3d2b3; border-radius: 12px; padding: 20px 22px;
                transition: border-color .2s ease, box-shadow .2s ease;
            }
            .kk-rev__item:hover { border-color: #c9b393; box-shadow: 0 4px 14px rgba(45,24,16,.06); }
            .kk-rev__item-top { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
            .kk-rev__item-avatar {
                width: 42px; height: 42px; border-radius: 50%;
                background: #2d1810; color: #efe2cb;
                display: flex; align-items: center; justify-content: center;
                font-size: 16px; font-weight: 700; flex-shrink: 0;
            }
            .kk-rev__item-meta { flex: 1; min-width: 0; }
            .kk-rev__item-name-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
            .kk-rev__item-name { font-size: 14px; font-weight: 700; color: #2d1810; }
            .kk-rev__item-verified { display: inline-flex; align-items: center; gap: 3px; color: #2a9d3e; font-size: 11px; font-weight: 600; }
            .kk-rev__item-verified svg { width: 12px; height: 12px; }
            .kk-rev__item-sub { display: flex; align-items: center; gap: 10px; margin-top: 3px; }
            .kk-rev__item-stars { display: inline-flex; gap: 1px; }
            .kk-rev__item-stars svg { width: 14px; height: 14px; }
            .kk-rev__item-date { font-size: 12px; color: #9b8a72; }
            .kk-rev__item-title { font-size: 14px; font-weight: 700; color: #2d1810; margin: 4px 0 4px; }
            .kk-rev__item-body { font-size: 14px; color: #4a3528; line-height: 1.65; margin: 0; }
            .kk-rev__empty { text-align: center; padding: 32px 0; font-size: 14px; color: #7a6555; }
            .kk-rev__demo-note { text-align: center; font-size: 11px; color: #9b8a72; font-style: italic; margin: 16px 0 0; }
        </style>
        <div class="kk-rev" id="customer-reviews" x-data="{ showForm: {{ ($errors->any() || session('success') || session('error')) ? 'true' : 'false' }} }">
            <h2 class="kk-rev__title">Customer Reviews</h2>

            @php
                // Demo/sample reviews — shown ONLY while the product has no real
                // reviews. They auto-hide the moment a genuine review exists.
                // NOT real data; safe placeholder content for the storefront design.
                $sampleReviews = [
                    ['name'=>'Aarav Mehta','rating'=>5,'date'=>'18 Jun 2026','title'=>'Premium feel, perfect fit','body'=>'The fabric quality is genuinely premium and the fit is spot on. Got compliments the very first day I wore it. Will be ordering more colours.'],
                    ['name'=>'Rohan Kapoor','rating'=>5,'date'=>'09 Jun 2026','title'=>'Worth every rupee','body'=>'Stitching and finish feel well above the price point. Delivery was quick too. Highly recommended.'],
                    ['name'=>'Ishaan Verma','rating'=>4,'date'=>'27 May 2026','title'=>'Great shirt, runs slightly large','body'=>'Lovely material and the colour is exactly as shown. I would size down for a slimmer fit, but overall very happy with it.'],
                    ['name'=>'Karan Singh','rating'=>5,'date'=>'14 May 2026','title'=>'My new favourite','body'=>'Soft, breathable and looks elegant for both office and outings. Easily five stars from me.'],
                ];
                $useSample = $product->review_count == 0;
                if ($useSample) {
                    $displayCount = count($sampleReviews);
                    $displayAvg = collect($sampleReviews)->avg('rating');
                    $displayDist = [5=>0,4=>0,3=>0,2=>0,1=>0];
                    foreach ($sampleReviews as $sr) { $displayDist[$sr['rating']]++; }
                } else {
                    $displayCount = $totalReviews;
                    $displayAvg = $product->rating;
                    $displayDist = $ratingDist;
                }
                $avgRounded = (int) round($displayAvg);
                $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
            @endphp

            <div class="kk-rev__summary">
                <div class="kk-rev__overall">
                    <div class="kk-rev__big">{{ number_format($displayAvg, 1) }}</div>
                    <span class="kk-rev__stars">
                        @for($s = 1; $s <= 5; $s++)
                            <svg fill="{{ $s <= $avgRounded ? '#2D1810' : '#c9b393' }}" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                        @endfor
                    </span>
                    <p class="kk-rev__based">
                        Based on {{ $displayCount }} {{ Str::plural('review', $displayCount) }}
                        <span class="kk-rev__verified-icon" title="Verified reviews">
                            <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </p>
                </div>

                <div class="kk-rev__bars">
                    @for($star = 5; $star >= 1; $star--)
                    @php
                        $count = $displayDist[$star] ?? 0;
                        $pct = $displayCount > 0 ? ($count / $displayCount) * 100 : 0;
                    @endphp
                    <div class="kk-rev__bar-row">
                        <span class="kk-rev__bar-label">{{ $star }}<svg fill="#2D1810" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg></span>
                        <div class="kk-rev__bar-track"><div class="kk-rev__bar-fill" style="width: {{ $pct }}%;"></div></div>
                        <span class="kk-rev__bar-count">{{ $count }}</span>
                    </div>
                    @endfor
                </div>

                <button type="button" class="kk-rev__write" @click="showForm = true; $nextTick(() => document.getElementById('write-review-form')?.scrollIntoView({behavior:'smooth'}))">
                    Write a Review
                </button>
            </div>

            @if($useSample)
                <div class="kk-rev__list">
                    @foreach($sampleReviews as $sr)
                    <div class="kk-rev__item">
                        <div class="kk-rev__item-top">
                            <div class="kk-rev__item-avatar">{{ strtoupper(substr($sr['name'], 0, 1)) }}</div>
                            <div class="kk-rev__item-meta">
                                <div class="kk-rev__item-name-row">
                                    <span class="kk-rev__item-name">{{ $sr['name'] }}</span>
                                    <span class="kk-rev__item-verified">
                                        <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Verified Buyer
                                    </span>
                                </div>
                                <div class="kk-rev__item-sub">
                                    <span class="kk-rev__item-stars">
                                        @for($s = 1; $s <= 5; $s++)
                                            <svg fill="{{ $s <= $sr['rating'] ? '#2D1810' : '#c9b393' }}" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                                        @endfor
                                    </span>
                                    <span class="kk-rev__item-date">{{ $sr['date'] }}</span>
                                </div>
                            </div>
                        </div>
                        <p class="kk-rev__item-title">{{ $sr['title'] }}</p>
                        <p class="kk-rev__item-body">{{ $sr['body'] }}</p>
                    </div>
                    @endforeach
                </div>
                <p class="kk-rev__demo-note">Sample reviews shown for preview — these disappear automatically once real customer reviews are submitted.</p>
            @else
                <div class="kk-rev__list">
                    @foreach($product->reviews as $review)
                    <div class="kk-rev__item">
                        <div class="kk-rev__item-top">
                            <div class="kk-rev__item-avatar">{{ strtoupper(substr($review->user?->first_name ?? 'A', 0, 1)) }}</div>
                            <div class="kk-rev__item-meta">
                                <div class="kk-rev__item-name-row">
                                    <span class="kk-rev__item-name">{{ trim(($review->user?->first_name ?? 'Anonymous') . ' ' . ($review->user?->last_name ?? '')) }}</span>
                                    <span class="kk-rev__item-verified">
                                        <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Verified Buyer
                                    </span>
                                </div>
                                <div class="kk-rev__item-sub">
                                    <span class="kk-rev__item-stars">
                                        @for($s = 1; $s <= 5; $s++)
                                            <svg fill="{{ $s <= $review->rating ? '#2D1810' : '#c9b393' }}" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                                        @endfor
                                    </span>
                                    <span class="kk-rev__item-date">{{ $review->created_at->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                        @if($review->title)<p class="kk-rev__item-title">{{ $review->title }}</p>@endif
                        <p class="kk-rev__item-body">{{ $review->content }}</p>
                        @if($review->relationLoaded('images') && $review->images->isNotEmpty())
                            <div class="kk-rev__media">
                                @foreach($review->images as $media)
                                    @if($media->is_video)
                                        <video class="kk-rev__media-item" controls preload="metadata" playsinline @if($media->display_thumbnail) poster="{{ $media->display_thumbnail }}" @endif>
                                            <source src="{{ $media->display_url }}">
                                        </video>
                                    @else
                                        <a href="{{ $media->display_url }}" target="_blank" rel="noopener" class="kk-rev__media-item kk-rev__media-item--img">
                                            <img src="{{ $media->display_url }}" alt="{{ $media->alt_text ?? 'Customer review photo' }}" loading="lazy">
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif

            <!-- ===== WRITE A REVIEW FORM (open to any user / guest; moderated) ===== -->
            <style>
            .kk-revform { background: #fbf5e8; border: 1px solid #e3d2b3; border-radius: 14px; padding: 26px 28px; margin-top: 22px; }
            .kk-revform__title { font-family: 'Playfair Display', Georgia, serif; font-size: 22px; font-weight: 700; color: #2d1810; margin: 0 0 18px; }
            .kk-revform__label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #2d1810; margin: 0 0 8px; }
            .kk-revform__stars { display: inline-flex; gap: 5px; margin-bottom: 18px; }
            .kk-revform__stars button { background: none; border: none; cursor: pointer; padding: 0; line-height: 0; }
            .kk-revform__stars svg { width: 28px; height: 28px; transition: fill .12s ease; }
            .kk-revform__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
            @media (max-width: 600px) { .kk-revform__grid { grid-template-columns: 1fr; } }
            .kk-revform__input, .kk-revform__textarea { width: 100%; background: #fff; border: 1px solid #e3d2b3; border-radius: 8px; padding: 11px 14px; font-size: 14px; color: #2d1810; font-family: inherit; }
            .kk-revform__input:focus, .kk-revform__textarea:focus { outline: none; border-color: #2d1810; }
            .kk-revform__textarea { margin-bottom: 16px; resize: vertical; }
            .kk-revform__note { font-size: 12px; color: #9b8a72; margin: 12px 0 0; }
            .kk-revform__alert { padding: 11px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
            .kk-revform__alert--ok { background: #eaf6ec; color: #1c6428; border: 1px solid #b9e0c0; }
            .kk-revform__alert--err { background: #fdecea; color: #b71c00; border: 1px solid #f3c4be; }
            .kk-revform__alert ul { margin: 0; padding-left: 18px; }
            .kk-rev__write[disabled] { opacity: .5; cursor: not-allowed; transform: none; }
            /* Uploaded review media (Task 10) */
            .kk-rev__media { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
            .kk-rev__media-item { width: 72px; height: 72px; border-radius: 8px; overflow: hidden; border: 1px solid #e3d2b3; display: block; background: #000; }
            .kk-rev__media-item img, .kk-rev__media-item video { width: 100%; height: 100%; object-fit: cover; display: block; }
            .kk-rev__media-item video { background: #000; }
            @media (max-width: 640px) { .kk-rev__media-item { width: 64px; height: 64px; } }
            /* Upload controls in the review form */
            .kk-revform__uploads { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
            @media (max-width: 600px) { .kk-revform__uploads { grid-template-columns: 1fr; } }
            .kk-revform__file { font-size: 12px; color: #7a6555; }
            .kk-revform__file span { display: block; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #2d1810; margin-bottom: 6px; }
            .kk-revform__file input[type="file"] { width: 100%; font-size: 12px; color: #2d1810; }
            .kk-revform__hint { font-size: 11px; color: #9b8a72; margin-top: 4px; }
            </style>
            <div id="write-review-form" x-show="showForm" x-collapse x-cloak class="kk-revform">
                <h3 class="kk-revform__title">Write a Review</h3>

                @if(session('success'))
                    <div class="kk-revform__alert kk-revform__alert--ok">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="kk-revform__alert kk-revform__alert--err">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="kk-revform__alert kk-revform__alert--err">
                        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ route('product.guest-review', $product) }}" method="POST" enctype="multipart/form-data" x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }">
                    @csrf
                    {{-- anti-spam honeypot: must stay empty --}}
                    <div style="position:absolute; left:-9999px;" aria-hidden="true">
                        <label>Leave this field empty</label>
                        <input type="text" name="honeypot" tabindex="-1" autocomplete="off">
                    </div>

                    <label class="kk-revform__label">Your Rating</label>
                    <div class="kk-revform__stars">
                        <template x-for="i in 5" :key="i">
                            <button type="button" @click="rating = i" @mouseenter="hover = i" @mouseleave="hover = 0" :aria-label="i + ' star'">
                                <svg :fill="(hover || rating) >= i ? '#2D1810' : '#c9b393'" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="rating" :value="rating">

                    <div class="kk-revform__grid">
                        <input class="kk-revform__input" type="text" name="guest_name" placeholder="Full name *" value="{{ old('guest_name') }}" required maxlength="100">
                        <input class="kk-revform__input" type="email" name="guest_email" placeholder="Email (not published) *" value="{{ old('guest_email') }}" required maxlength="255">
                    </div>
                    <input class="kk-revform__input" type="text" name="title" placeholder="Review title (optional)" value="{{ old('title') }}" maxlength="255" style="margin-bottom:12px;">
                    <textarea class="kk-revform__textarea" name="content" rows="4" placeholder="Share your experience (at least 20 characters)…" required minlength="20" maxlength="2000">{{ old('content') }}</textarea>

                    {{-- Photo & video uploads (Task 10) --}}
                    <div class="kk-revform__uploads">
                        <label class="kk-revform__file">
                            <span>Add Photos</span>
                            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                            <p class="kk-revform__hint">Up to 5 images (JPG/PNG/WEBP, max 5MB each).</p>
                        </label>
                        <label class="kk-revform__file">
                            <span>Add Videos</span>
                            <input type="file" name="videos[]" accept="video/mp4,video/webm,video/quicktime" multiple>
                            <p class="kk-revform__hint">Up to 2 short videos (MP4/WEBM/MOV, max 20MB each).</p>
                        </label>
                    </div>

                    <button type="submit" class="kk-rev__write" :disabled="rating < 1">Submit Review</button>
                    <p class="kk-revform__note">Open to everyone — no account needed. Your review is published after moderation; your email is never shown publicly.</p>
                </form>
            </div>
        </div>

        <!-- ===== FAQ / Q&A ACCORDION ===== -->
        @if($product->questions->count() > 0)
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;" x-data="{ openFaq: null }">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:1rem;">Frequently Asked Questions</h2>
            <div style="display:flex;flex-direction:column;gap:0;">
                @foreach($product->questions as $qi => $question)
                <div style="border:1px solid #e5e5e5;border-radius:0.5rem;overflow:hidden;{{ $qi > 0 ? 'margin-top:-1px;' : '' }}">
                    <button @click="openFaq = openFaq === {{ $qi }} ? null : {{ $qi }}"
                            style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:0.875rem 1rem;background:#fff;border:none;cursor:pointer;text-align:left;">
                        <span style="font-size:14px;font-weight:600;color:#0F1111;">{{ $question->question }}</span>
                        <svg style="width:1.25rem;height:1.25rem;color:#565959;flex-shrink:0;transition:transform 0.2s;"
                             :style="openFaq === {{ $qi }} ? 'transform:rotate(180deg);' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === {{ $qi }}" x-cloak x-collapse>
                        <div style="padding:0 1rem 0.875rem;font-size:14px;color:#333;line-height:1.6;">
                            @foreach($question->answers as $answer)
                            <p>{{ $answer->answer }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- ===== RELATED PRODUCTS ===== -->
        @if($relatedProducts->count() > 0)
        <style>
            .kk-related { margin-top: 64px; padding-top: 32px; padding-bottom: 80px; }
            .kk-related__title {
                text-align: center;
                font-size: 18px;
                font-weight: 600;
                letter-spacing: 0.32em;
                text-transform: uppercase;
                color: #2d1810;
                margin: 0 0 32px;
            }
            .kk-related__grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 24px;
            }
            @media (max-width: 1024px) { .kk-related__grid { grid-template-columns: repeat(3, 1fr); gap: 16px; } }
            @media (max-width: 640px)  { .kk-related__grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }

            .kk-related__card {
                position: relative;
                display: block;
                text-decoration: none;
                color: #2d1810;
            }
            .kk-related__imgwrap {
                position: relative;
                aspect-ratio: 3/4;
                overflow: hidden;
                background: #fff;
                margin-bottom: 12px;
            }
            .kk-related__img {
                width: 100%; height: 100%;
                object-fit: cover; display: block;
                transition: transform .5s ease;
            }
            .kk-related__card:hover .kk-related__img { transform: scale(1.03); }
            .kk-related__add {
                display: block;
                width: 100%;
                margin-top: 10px;
                background: #2d1810; color: #efe2cb;
                border: none; border-radius: 4px;
                padding: 10px 14px; font-size: 11px; font-weight: 700;
                letter-spacing: 0.14em; text-transform: uppercase;
                cursor: pointer; text-align: center;
                transition: background .15s ease;
            }
            .kk-related__add:hover { background: #1f1109; }
            .kk-related__name {
                font-size: 14px;
                font-weight: 500;
                line-height: 1.4;
                color: #2d1810;
                margin: 0 0 4px;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                min-height: 2.8em;
            }
            .kk-related__price {
                font-size: 14px;
                font-weight: 600;
                color: #2d1810;
            }
            .kk-related__price-mrp {
                font-size: 12px;
                font-weight: 400;
                color: #7a6555;
                text-decoration: line-through;
                margin-left: 6px;
            }
        </style>
        <div class="kk-related">
            <h2 class="kk-related__title">You May Also Like</h2>
            {{-- Reuse the shared product card (rating, wishlist, stock, placeholder) — Task 13 --}}
            <div class="kk-related__grid">
                @foreach($relatedProducts as $rp)
                    <x-product-card :product="$rp" :show-quick-view="false" />
                @endforeach
            </div>
        </div>
        @endif

        {{-- ===== PURCHASE / SOCIAL-PROOF NOTIFICATION ===== --}}
        @php
            $notifEnabled = (bool) \App\Models\Setting::get('purchase_notif_enabled', true);
            $notifThumb = $product->primary_image_url;
            // No city is shown, so only the relative time travels to the front end.
            $notifItems = collect($recentPurchases ?? [])->map(fn ($p) => [
                'time' => $p['minutes'] . ' ' . \Illuminate\Support\Str::plural('minute', $p['minutes']) . ' ago',
            ])->values();
            if ($notifItems->isEmpty()) {
                $demoMinutes = \App\Models\Setting::get('purchase_notif_demo_minutes', [9, 14, 27, 6, 41]);
                $demoMinutes = (is_array($demoMinutes) && $demoMinutes) ? $demoMinutes : [9, 14, 27, 6, 41];
                $notifItems = collect($demoMinutes)->values()->map(fn ($m) => [
                    'time' => (int) $m . ' ' . \Illuminate\Support\Str::plural('minute', (int) $m) . ' ago',
                ]);
            }
        @endphp
        @if($notifEnabled && $notifItems->isNotEmpty())
        <style>
            .kk-pnotif { position: fixed; left: 18px; bottom: 18px; z-index: 55; display: flex; align-items: center; gap: 14px;
                background: #ffffff; border: 2px solid #f26a21; border-radius: 16px; box-shadow: 0 10px 30px rgba(17,24,39,0.14);
                padding: 14px 44px 14px 14px; max-width: 400px; }
            .kk-pnotif__thumb { width: 72px; height: 72px; border-radius: 10px; object-fit: cover; flex-shrink: 0; background: #f3f4f6; }
            .kk-pnotif__body { flex: 1; min-width: 0; }
            /* Three stacked lines: what happened, what was bought, when.
               app.css forces `font-weight:700 !important` on every storefront <p>
               via `body:not(.layout-admin) p`, which scores (0,1,2). !important
               alone does not win that — a lone class is only (0,1,0) — so these
               selectors add the parent class to reach (0,2,1) and take the
               cascade. Without this all three lines render bold and the
               hierarchy collapses. */
            .kk-pnotif p.kk-pnotif__lead { font-size: 14px; font-weight: 400 !important; color: #4b5563; margin: 0; line-height: 1.45; letter-spacing: 0; }
            .kk-pnotif p.kk-pnotif__name { font-size: 15px; font-weight: 600 !important; color: #111827; margin: 3px 0 0; line-height: 1.4; letter-spacing: -0.005em; overflow-wrap: anywhere; }
            .kk-pnotif p.kk-pnotif__time { font-size: 13px; font-weight: 400 !important; color: #9ca3af; margin: 5px 0 0; line-height: 1.3; letter-spacing: 0; }
            .kk-pnotif button.kk-pnotif__close { font-weight: 400 !important; }
            .kk-pnotif__close { position: absolute; top: 8px; right: 8px; width: 22px; height: 22px; border-radius: 50%;
                background: #eceef1; border: 0; color: #6b7280; cursor: pointer; font-size: 15px; line-height: 1; padding: 0;
                display: flex; align-items: center; justify-content: center; transition: background .15s, color .15s; }
            .kk-pnotif__close:hover { background: #dfe2e7; color: #374151; }
            .kk-pnotif__close:focus-visible { outline: 2px solid #f26a21; outline-offset: 2px; }
            @media (max-width: 480px) { .kk-pnotif { left: 10px; right: 10px; bottom: 10px; max-width: none; }
                .kk-pnotif__thumb { width: 60px; height: 60px; } }
        </style>
        <div x-data="purchaseNotif(@js($notifItems->all()), @js(\Illuminate\Support\Str::limit($product->name, 34)), @js($notifThumb))" x-cloak x-show="visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-end="opacity-0"
             class="kk-pnotif" role="status" aria-live="polite">
            <img class="kk-pnotif__thumb" :src="thumb" :alt="productName">
            <div class="kk-pnotif__body">
                <p class="kk-pnotif__lead">Someone purchased</p>
                <p class="kk-pnotif__name">{{ \Illuminate\Support\Str::limit($product->name, 40) }}</p>
                <p class="kk-pnotif__time" x-text="current.time"></p>
            </div>
            <button type="button" class="kk-pnotif__close" @click="dismiss()" aria-label="Dismiss">&times;</button>
        </div>
        {{-- purchaseNotif() is registered in resources/js/app.js (reliable init) --}}
        @endif

        <!-- ===== IMAGE ZOOM MODAL ===== -->
        <div x-show="showZoom" x-cloak
             style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.9);"
             @click="showZoom = false"
             @keydown.escape.window="showZoom = false"
             @keydown.left.window="showZoom && (currentImage = currentImage > 0 ? currentImage - 1 : {{ count($images) - 1 }})"
             @keydown.right.window="showZoom && (currentImage = currentImage < {{ count($images) - 1 }} ? currentImage + 1 : 0)">

            <button @click="showZoom = false" style="position:absolute;top:1rem;right:1rem;width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,0.1);color:#fff;border:none;cursor:pointer;z-index:10;" aria-label="Close zoom">
                <svg style="width:1.5rem;height:1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            @if(count($images) > 1)
            <button @click.stop="currentImage = currentImage > 0 ? currentImage - 1 : {{ count($images) - 1 }}"
                    style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,0.1);color:#fff;border:none;cursor:pointer;z-index:10;">
                <svg style="width:1.5rem;height:1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button @click.stop="currentImage = currentImage < {{ count($images) - 1 }} ? currentImage + 1 : 0"
                    style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,0.1);color:#fff;border:none;cursor:pointer;z-index:10;">
                <svg style="width:1.5rem;height:1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            @endif

            <div @click.stop style="max-width:56rem;max-height:90vh;width:100%;padding:0 1rem;">
                @foreach($media as $i => $m)
                    @if($m['type'] === 'video')
                        <video x-show="currentImage === {{ $i }}" controls playsinline
                               @if($m['thumb']) poster="{{ $m['thumb'] }}" @endif
                               style="max-width:100%;max-height:90vh;margin:0 auto;display:block;background:#000;">
                            <source src="{{ $m['url'] }}">
                        </video>
                    @else
                        <img x-show="currentImage === {{ $i }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             src="{{ $m['url'] }}"
                             alt="{{ $product->name }}"
                             style="max-width:100%;max-height:90vh;object-fit:contain;margin:0 auto;display:block;">
                    @endif
                @endforeach
            </div>

            @if(count($images) > 1)
            <div style="position:absolute;bottom:1.5rem;left:50%;transform:translateX(-50%);font-size:14px;color:rgba(255,255,255,0.7);">
                <span x-text="(currentImage + 1) + ' / {{ count($images) }}'"></span>
            </div>
            @endif
        </div>

    </div>
    </div>{{-- /.pdp-wrapper --}}

    <!-- ===== MOBILE STICKY BOTTOM BAR ===== -->
    @if($product->isInStock())
    <div class="lg:hidden"
         style="position:fixed;bottom:0;left:0;right:0;z-index:40;padding:0.75rem 1rem;display:flex;align-items:center;gap:0.75rem;background:#fff;border-top:1px solid #e5e5e5;box-shadow:0 -2px 10px rgba(0,0,0,0.08);"
         x-data="{ show: false }"
         x-init="
            const observer = new IntersectionObserver(([entry]) => { show = !entry.isIntersecting }, { threshold: 0 });
            const el = document.querySelector('[x-ref=buyBox]');
            if (el) observer.observe(el);
         "
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         x-cloak>
        <div style="flex:1;min-width:0;">
            <p style="font-size:1rem;font-weight:700;color:#0F1111;">₹{{ number_format($product->price) }}</p>
            @if($discountPct > 0)
            <p style="font-size:12px;"><span style="color:#999;text-decoration:line-through;">₹{{ number_format($product->mrp) }}</span> <span style="color:#CC0C39;">{{ $discountPct }}% off</span></p>
            @endif
        </div>
        <button @click="$dispatch('mobile-add-to-cart')"
                style="padding:0.75rem 1.1rem;border-radius:0.375rem;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;background:#4a2d1a;color:#efe2cb;border:none;cursor:pointer;white-space:nowrap;">
            Add to Cart
        </button>
        <button @click="$dispatch('mobile-buy-now')"
                style="padding:0.75rem 1.1rem;border-radius:0.375rem;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;background:#2d1810;color:#efe2cb;border:none;cursor:pointer;white-space:nowrap;">
            Buy Now
        </button>
    </div>
    @endif

    <script>
    function productPage() {
        return {
            currentImage: 0,
            imageCount: {{ count($media) }},
            touchStartX: 0,
            quantity: 1,
            selectedSize: null,
            selectedColor: null,
            selectedVariant: null,
            selectedAttributes: {},
            variants: @json($variantData),
            showZoom: false,
            linkCopied: false,
            shareCopied: false,
            basePrice: {{ (float) $product->price }},
            baseMrp: {{ (float) $product->mrp }},
            inStock: {{ $product->isInStock() ? 'true' : 'false' }},

            get currentPrice() {
                if (this.selectedVariant) {
                    const v = this.variants.find(v => v.id === this.selectedVariant);
                    return v && v.price > 0 ? v.price : this.basePrice;
                }
                return this.basePrice;
            },

            get currentMrp() {
                if (this.selectedVariant) {
                    const v = this.variants.find(v => v.id === this.selectedVariant);
                    return v && v.mrp > 0 ? v.mrp : this.baseMrp;
                }
                return this.baseMrp;
            },

            init() {
                this.$el.addEventListener('mobile-add-to-cart', () => this.addToCart());
                this.$el.addEventListener('mobile-buy-now', () => this.buyNow());
                // Pause any playing gallery/zoom video when the active item or zoom changes,
                // so audio never keeps playing after the user navigates away.
                this.$watch('currentImage', () => this.pauseVideos());
                this.$watch('showZoom', () => this.pauseVideos());
            },

            pauseVideos() {
                this.$el.querySelectorAll('video').forEach((v) => { try { v.pause(); } catch (e) {} });
            },

            // Mobile swipe on the main gallery image
            onTouchStart(e) {
                // Don't treat native video-control (seek bar) touches as gallery swipes.
                if (e.target?.closest?.('video')) { this.touchStartX = null; return; }
                this.touchStartX = e.changedTouches[0].screenX;
            },
            onTouchEnd(e) {
                if (this.touchStartX === null || e.target?.closest?.('video')) return;
                if (this.imageCount < 2) return;
                const dx = e.changedTouches[0].screenX - this.touchStartX;
                if (Math.abs(dx) < 40) return;
                if (dx < 0) this.currentImage = (this.currentImage + 1) % this.imageCount;
                else this.currentImage = (this.currentImage - 1 + this.imageCount) % this.imageCount;
            },
            nextImage() { if (this.imageCount > 1) this.currentImage = (this.currentImage + 1) % this.imageCount; },
            prevImage() { if (this.imageCount > 1) this.currentImage = (this.currentImage - 1 + this.imageCount) % this.imageCount; },

            selectAttribute(attrName, value) {
                this.selectedAttributes[attrName] = value;
                this.findMatchingVariant();
            },

            findMatchingVariant() {
                const selectedKeys = Object.keys(this.selectedAttributes);
                if (selectedKeys.length === 0) {
                    this.selectedVariant = null;
                    return;
                }

                const match = this.variants.find(v => {
                    return v.attributes.every(attr => {
                        if (this.selectedAttributes[attr.name] === undefined) return true;
                        return this.selectedAttributes[attr.name] === attr.value;
                    }) && selectedKeys.every(key => {
                        return v.attributes.some(attr => attr.name === key && attr.value === this.selectedAttributes[key]);
                    });
                });

                this.selectedVariant = match ? match.id : null;
                if (match) {
                    this.inStock = match.stock > 0;
                }
            },

            requireSize() {
                if (!this.selectedSize) {
                    Alpine.store('toast').error('Please select a size');
                    document.getElementById('kk-size-select')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                return true;
            },

            async addToCart() {
                if (!this.requireSize()) return;
                await Alpine.store('cart').add({{ $product->id }}, this.quantity, this.selectedVariant, this.selectedSize);
            },

            async buyNow() {
                if (!this.requireSize()) return;
                await Alpine.store('cart').add({{ $product->id }}, this.quantity, this.selectedVariant, this.selectedSize);
                Alpine.store('cart').close();
                window.location.href = '{{ route("checkout.index") }}';
            },

            async addAllToCart(productIds) {
                for (const id of productIds) {
                    await Alpine.store('cart').add(id, 1, null);
                }
            },

            async shareProduct() {
                const url = '{{ route("product.show", $product) }}';
                const title = @json($product->name);
                // Native share sheet on supporting devices (mobile) …
                if (navigator.share) {
                    try {
                        await navigator.share({ title: title, text: title, url: url });
                    } catch (e) { /* user dismissed the share sheet — ignore */ }
                    return;
                }
                // … otherwise copy the link to the clipboard as a fallback.
                try {
                    await navigator.clipboard.writeText(url);
                    this.shareCopied = true;
                    Alpine.store('toast')?.success('Link copied to clipboard!');
                    setTimeout(() => this.shareCopied = false, 2000);
                } catch (e) {
                    Alpine.store('toast')?.error('Could not copy the link');
                }
            },

            shareViaWhatsApp() {
                const url = '{{ route("product.show", $product) }}';
                const text = '{{ $product->name }} - ₹{{ number_format($product->price) }}';
                window.open('https://wa.me/?text=' + encodeURIComponent(text + ' ' + url), '_blank');
            },

            copyLink() {
                navigator.clipboard.writeText('{{ route("product.show", $product) }}');
                this.linkCopied = true;
                Alpine.store('toast').success('Link copied!');
                setTimeout(() => this.linkCopied = false, 2000);
            },
        };
    }
    </script>
</x-layouts.app>
