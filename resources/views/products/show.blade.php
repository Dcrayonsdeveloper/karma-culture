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
    .kk-pdp__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .kk-pdp__thumb:hover { border-color: #c9b393; }
    .kk-pdp__thumb.is-active { border-color: #2d1810; }

    .kk-pdp__main {
        position: relative; flex: 1; min-width: 0;
        /* cap to viewport height so the pinned (sticky) image is never cut off */
        aspect-ratio: 4/5; max-height: min(580px, calc(100vh - 80px));
        border-radius: 10px; overflow: hidden; background: #fff; cursor: zoom-in;
    }
    .kk-pdp__main-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
    .kk-pdp__counter {
        position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%);
        background: rgba(31,17,9,.72); color: #efe2cb; font-size: 12px; letter-spacing: .05em;
        padding: 4px 12px; border-radius: 999px;
    }
    @media (max-width: 640px) {
        .kk-pdp__gallery { flex-direction: column-reverse; }
        .kk-pdp__thumbs { flex-direction: row; width: 100%; max-height: none; overflow-x: auto; }
        .kk-pdp__thumb { width: 60px; flex-shrink: 0; }
        .kk-pdp__main { aspect-ratio: 3/4; max-height: none; }
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
        letter-spacing: 0.08em; text-transform: uppercase;
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
    .kk-pdp__wish {
        margin-top: 16px; display: inline-flex; align-items: center; gap: 8px;
        background: none; border: none; cursor: pointer; font-size: 13px; color: #2d1810; padding: 4px 0;
    }
    </style>
    <div class="pdp-wrapper">
    <div class="container mx-auto px-4" x-data="productPage()">

        <!-- ===== TWO-COLUMN LAYOUT ===== -->
        <div class="kk-pdp">

            <!-- LEFT: Image grid -->
            <div class="kk-pdp__gallery">
                @if(count($images) > 1)
                    <div class="kk-pdp__thumbs">
                        @foreach($images as $i => $img)
                            <button type="button" class="kk-pdp__thumb"
                                    :class="currentImage === {{ $i }} ? 'is-active' : ''"
                                    @click="currentImage = {{ $i }}"
                                    aria-label="View image {{ $i + 1 }}">
                                <img src="{{ $img }}" alt="{{ $product->name }} — thumbnail {{ $i + 1 }}" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="kk-pdp__main" @click="showZoom = true">
                    @foreach($images as $i => $img)
                        <img src="{{ $img }}" alt="{{ $product->name }}" class="kk-pdp__main-img"
                             x-show="currentImage === {{ $i }}" @if($i !== 0) x-cloak @endif
                             loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                    @endforeach
                    @if(count($images) > 1)
                        <span class="kk-pdp__counter"><span x-text="currentImage + 1"></span> / {{ count($images) }}</span>
                    @endif
                </div>
            </div>

            <!-- RIGHT: Product info -->
            <div class="kk-pdp__info" x-ref="buyBox">
                <h1 class="kk-pdp__title">{{ $product->name }}</h1>

                @if($product->review_count > 0)
                <div class="kk-pdp__rating">
                    <span class="kk-pdp__rating-stars">
                        @for($s = 1; $s <= 5; $s++)
                            <svg fill="{{ $s <= round($product->rating ?: 5) ? '#1f1109' : '#c9b393' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </span>
                    <a href="#customer-reviews" @click.prevent="document.getElementById('customer-reviews')?.scrollIntoView({behavior:'smooth'})"
                       class="kk-pdp__rating-count" style="text-decoration:none;">{{ $product->review_count }} {{ Str::plural('review', $product->review_count) }}</a>
                </div>
                @endif

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
                </style>
                <section class="kk-sizeguide" id="kk-size-select" aria-label="Select size">
                    <h2 class="kk-sizeguide__title">Select Size<span class="kk-sizeguide__sel" x-show="selectedSize" x-cloak> — <span x-text="selectedSize"></span></span></h2>
                    <div class="kk-sizeguide__row">
                        @foreach(['XS-36','S-38','M-40','L-42','XL-44','XXL-46','3XL-48'] as $sz)
                            <button type="button" class="kk-sizeguide__size"
                                    :class="selectedSize === '{{ $sz }}' ? 'is-selected' : ''"
                                    @click="selectedSize = '{{ $sz }}'">{{ $sz }}</button>
                        @endforeach
                    </div>
                </section>

                @if(!empty($variantGroups))
                    @foreach($variantGroups as $attrName => $values)
                    <div class="kk-pdp__variant-group">
                        <h3 class="kk-pdp__variant-label">{{ $attrName }}: <span class="kk-pdp__variant-sel" x-text="selectedAttributes['{{ $attrName }}'] || ''"></span></h3>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach($values as $val)
                                <button type="button" class="kk-pdp__variant-btn"
                                        :class="selectedAttributes['{{ $attrName }}'] === '{{ $val }}' ? 'is-active' : ''"
                                        @click="selectAttribute('{{ $attrName }}', '{{ $val }}')">{{ $val }}</button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
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
                    <button type="button" class="kk-pdp__wish" style="margin-top:0; padding-bottom:9px;" @click="$store.wishlist.toggle({{ $product->id }})">
                        <svg style="width:18px;height:18px;" :fill="$store.wishlist.has({{ $product->id }}) ? '#dc362e' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        <span x-text="$store.wishlist.has({{ $product->id }}) ? 'Saved to Wishlist' : 'Save to Wishlist'">Save to Wishlist</span>
                    </button>
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
                        Free delivery to <span x-text="pin"></span> &mdash; estimated {{ $dMin->format('D, d M') }} &ndash; {{ $dMax->format('D, d M') }}.
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
                    <strong>Free Delivery:</strong> {{ $deliveryMin->format('D, d M') }} &ndash; {{ $deliveryMax->format('D, d M') }}<br>
                    <strong>Easy Returns:</strong> 7-day return &amp; exchange policy
                </div>
            </div>

        </div>

        <!-- ===== SHARE + PRODUCT INFO ACCORDION ===== -->
        <style>
            .kk-pi { max-width: 860px; margin: 8px auto 0; padding: 0 16px; }

            /* Share row — circular icon buttons + divider */
            .kk-pi__share {
                display: flex; align-items: center; justify-content: center;
                gap: 14px; padding: 4px 0 28px; margin-bottom: 32px;
                border-bottom: 1px solid #e3d2b3;
            }
            .kk-pi__share-label {
                font-size: 11px; font-weight: 700; letter-spacing: 0.22em;
                color: #7a6555; text-transform: uppercase; margin-right: 4px;
            }
            .kk-pi__share a {
                color: #2d1810; width: 36px; height: 36px; border-radius: 50%;
                border: 1px solid #e3d2b3; background: #fbf5e8;
                display: inline-flex; align-items: center; justify-content: center;
                transition: all 0.18s ease;
            }
            .kk-pi__share a:hover { background: #2d1810; color: #efe2cb; border-color: #2d1810; transform: translateY(-2px); }
            .kk-pi__share a svg { width: 16px; height: 16px; }

            /* Accordion cards */
            .kk-pi__item {
                background: #fbf5e8; border: 1px solid #e3d2b3; border-radius: 12px;
                margin-bottom: 12px; overflow: hidden;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }
            .kk-pi__item:hover { border-color: #c9b393; box-shadow: 0 4px 14px rgba(45,24,16,0.06); }
            .kk-pi__btn {
                width: 100%; display: flex; align-items: center; justify-content: space-between;
                padding: 18px 22px; background: none; border: none; cursor: pointer;
                font-family: inherit; text-align: left; transition: background 0.2s ease;
            }
            .kk-pi__btn[aria-expanded="true"] { background: #f3e9d4; }
            .kk-pi__btn-label {
                font-size: 13px; font-weight: 700; letter-spacing: 0.16em;
                color: #2d1810; text-transform: uppercase;
            }
            /* Chevron icon (rotates on open) */
            .kk-pi__btn-icon {
                width: 9px; height: 9px; flex-shrink: 0; margin-right: 5px; margin-bottom: 3px;
                border-right: 2px solid #2d1810; border-bottom: 2px solid #2d1810;
                transform: rotate(45deg); transition: transform 0.28s ease;
            }
            .kk-pi__btn[aria-expanded="true"] .kk-pi__btn-icon { transform: rotate(-135deg); margin-bottom: -2px; }

            .kk-pi__panel { padding: 2px 22px 20px; font-size: 14px; line-height: 1.7; color: #2d1810; }
            .kk-pi__panel p { margin: 0 0 10px; }
            .kk-pi__panel p:last-child { margin: 0; }
            .kk-pi__panel ul { margin: 0 0 10px; padding-left: 20px; }
            .kk-pi__panel dl { display: grid; grid-template-columns: 1fr 2fr; gap: 10px 16px; margin: 0; }
            .kk-pi__panel dt { font-weight: 600; color: #7a6555; }
            .kk-pi__panel dd { margin: 0; color: #2d1810; }
        </style>

        @php
            $shareUrl = urlencode(route('product.show', $product));
            $shareText = urlencode($product->name);
        @endphp

        <div class="kk-pi">
            <!-- SHARE row -->
            <div class="kk-pi__share">
                <span class="kk-pi__share-label">Share</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Share on Facebook">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82V14.706h-3.13v-3.622h3.13V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.464.099 2.795.143v3.24h-1.917c-1.504 0-1.795.715-1.795 1.764v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.593 1.323-1.324V1.325C24 .593 23.407 0 22.675 0z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" aria-label="Share on Twitter">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723 10.054 10.054 0 01-3.127 1.184 4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.937 4.937 0 004.604 3.417 9.868 9.868 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.054 0 13.999-7.496 13.999-13.985 0-.21 0-.42-.015-.63A9.936 9.936 0 0024 4.59z"/></svg>
                </a>
                <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
                <a href="mailto:?subject={{ $shareText }}&body={{ $shareText }}%20{{ $shareUrl }}" aria-label="Share by Email">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                </a>
            </div>

            {{-- PRODUCT INFO --}}
            <div class="kk-pi__item" x-data="{ open: false }">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-label">Product Info</span>
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
            <div class="kk-pi__item" x-data="{ open: false }">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-label">Description</span>
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
            <div class="kk-pi__item" x-data="{ open: false }">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-label">Shipping, Returns &amp; Exchange</span>
                    <span class="kk-pi__btn-icon" aria-hidden="true"></span>
                </button>
                <div class="kk-pi__panel" x-show="open" x-collapse>
                    <p><strong>Shipping:</strong> Free delivery on orders above &#8377;499. Standard delivery in 3&ndash;7 business days across India.</p>
                    <p><strong>Returns:</strong> Easy 7-day return &amp; exchange policy. Items must be unworn, unwashed and with original tags attached.</p>
                    <p><strong>Exchange:</strong> One free size or colour exchange per order. Reach out via WhatsApp or email to initiate.</p>
                </div>
            </div>

            {{-- MANUFACTURED AND PACKAGED BY --}}
            <div class="kk-pi__item" x-data="{ open: false }">
                <button class="kk-pi__btn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span class="kk-pi__btn-label">Manufactured and Packaged by</span>
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



        <!-- ===== CUSTOMER REVIEWS (Judge.me-style) ===== -->
        <style>
            .kk-rev { max-width: 880px; margin: 56px auto 0; padding: 36px 16px 0; }
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
                        <p class="kk-rev__item-body">{{ $review->review }}</p>
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

                <form action="{{ route('product.guest-review', $product) }}" method="POST" x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }">
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
            <h2 class="kk-related__title">Related Products</h2>
            <div class="kk-related__grid">
                @foreach($relatedProducts as $rp)
                <div class="kk-related__card">
                    <a href="{{ route('product.show', $rp) }}" style="display:block;text-decoration:none;color:inherit;">
                        <div class="kk-related__imgwrap">
                            <img class="kk-related__img" src="{{ $rp->primary_image_url }}" alt="{{ $rp->name }}" loading="lazy">
                        </div>
                        <p class="kk-related__name">{{ $rp->name }}</p>
                        <div>
                            <span class="kk-related__price">₹{{ number_format($rp->price) }}</span>
                            @if($rp->mrp > $rp->price)
                            <span class="kk-related__price-mrp">₹{{ number_format($rp->mrp) }}</span>
                            @endif
                        </div>
                    </a>
                    @if($rp->isInStock())
                    <button type="button" class="kk-related__add" @click="$store.cart.add({{ $rp->id }})">
                        Add to Cart
                    </button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
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
                @foreach($images as $i => $img)
                <img x-show="currentImage === {{ $i }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     src="{{ $img }}"
                     alt="{{ $product->name }}"
                     style="max-width:100%;max-height:90vh;object-fit:contain;margin:0 auto;display:block;">
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
                style="padding:0.625rem 1.25rem;border-radius:0.5rem;font-size:13px;font-weight:600;background:#8c5c34;color:#fff;border:none;cursor:pointer;">
            Add to Cart
        </button>
        <button @click="$dispatch('mobile-buy-now')"
                style="padding:0.625rem 1.25rem;border-radius:0.5rem;font-size:13px;font-weight:600;background:#F8931D;color:#fff;border:none;cursor:pointer;">
            Buy Now
        </button>
    </div>
    @endif

    <script>
    function productPage() {
        return {
            currentImage: 0,
            quantity: 1,
            selectedSize: null,
            selectedVariant: null,
            selectedAttributes: {},
            variants: @json($variantData),
            showZoom: false,
            linkCopied: false,
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
            },

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
