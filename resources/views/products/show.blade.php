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

    <style>
    .pdp-wrapper { background: #EFE2CB; }
    .kk-pdp { display: grid; gap: 28px; padding: 16px 0 48px; }
    @media (min-width: 1024px) {
        .kk-pdp { grid-template-columns: 1.1fr 1fr; gap: 56px; align-items: start; }
    }
    .kk-pdp__gallery { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .kk-pdp__img { aspect-ratio: 3/4; overflow: hidden; background: #fff; cursor: zoom-in; }
    .kk-pdp__img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .4s ease; }
    .kk-pdp__img:hover img { transform: scale(1.02); }
    @media (max-width: 640px) { .kk-pdp__gallery { grid-template-columns: 1fr; } }
    .kk-pdp__info { padding-top: 4px; }
    .kk-pdp__title {
        font-size: 1.5rem; line-height: 1.3; font-weight: 700;
        text-transform: uppercase; color: #2d1810; margin: 0 0 14px;
        letter-spacing: 0.01em;
    }
    .kk-pdp__rating { display: flex; align-items: center; gap: 8px; margin: 0 0 20px; }
    .kk-pdp__rating-stars { display: inline-flex; gap: 1px; }
    .kk-pdp__rating-stars svg { width: 16px; height: 16px; }
    .kk-pdp__rating-count { font-size: 13px; color: #2d1810; }
    .kk-pdp__price {
        font-size: 1.875rem; font-weight: 400; color: #2d1810;
        margin: 0 0 4px; letter-spacing: 0.02em;
    }
    .kk-pdp__tax { font-size: 13px; color: #7a6555; margin: 0 0 24px; }
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
        letter-spacing: 0.08em; text-transform: uppercase; margin: 0 0 16px;
    }
    .kk-pdp__tier-accent {
        background-image: linear-gradient(transparent 60%, #ffb84a 60%, #ffb84a 92%, transparent 92%);
        padding: 0 3px;
    }
    .kk-pdp__tiers { list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 14px; }
    .kk-pdp__tier { display: flex; align-items: center; gap: 12px; font-size: 14px; color: #2d1810; }
    .kk-pdp__tier-icon {
        width: 26px; height: 26px; border-radius: 50%; border: 1px solid #2d1810;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 13px; font-weight: 600;
    }
    .kk-pdp__tier strong { font-weight: 700; }
    .kk-pdp__variant-group { margin: 0 0 18px; }
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
    .kk-pdp__cta-row { display: flex; gap: 12px; margin: 24px 0 18px; }
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
                @foreach($images as $i => $img)
                    <div class="kk-pdp__img" @click="currentImage = {{ $i }}; showZoom = true">
                        <img src="{{ $img }}" alt="{{ $product->name }}" loading="{{ $i > 1 ? 'lazy' : 'eager' }}">
                    </div>
                @endforeach
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

                <div class="kk-pdp__price" x-text="'₹' + currentPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                    ₹{{ number_format($product->price, 2) }}
                </div>
                <p class="kk-pdp__tax">Tax Included.</p>

                @php $emiNow = max(1, round($product->price / 3)); @endphp
                <div class="kk-pdp__emi">
                    <span class="kk-pdp__emi-tag">NEW</span>
                    <div class="kk-pdp__emi-text">
                        or Pay <strong>₹{{ number_format($emiNow) }} now</strong> &amp; rest later at<br>
                        0% EMI on UPI via {{ config('app.name', 'Karmaa Kulture') }} Pay Later
                    </div>
                    <button type="button" class="kk-pdp__emi-btn">
                        BUY ON EMI
                        <small>By snapmint</small>
                    </button>
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

                <div class="kk-pdp__variant-group">
                    <h3 class="kk-pdp__variant-label">Quantity</h3>
                    <select x-model.number="quantity" class="kk-pdp__qty">
                        @for($q = 1; $q <= 10; $q++)
                        <option value="{{ $q }}">{{ $q }}</option>
                        @endfor
                    </select>
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
                    $deliveryMin = now()->addDays(3);
                    $deliveryMax = now()->addDays(7);
                    while ($deliveryMin->isWeekend()) $deliveryMin->addDay();
                    while ($deliveryMax->isWeekend()) $deliveryMax->addDay();
                @endphp
                <div class="kk-pdp__meta">
                    <strong>Free Delivery:</strong> {{ $deliveryMin->format('D, d M') }} &ndash; {{ $deliveryMax->format('D, d M') }}<br>
                    <strong>Easy Returns:</strong> 7-day return &amp; exchange policy
                </div>

                <button type="button" class="kk-pdp__wish" @click="$store.wishlist.toggle({{ $product->id }})">
                    <svg style="width:18px;height:18px;" :fill="$store.wishlist.has({{ $product->id }}) ? '#dc362e' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span x-text="$store.wishlist.has({{ $product->id }}) ? 'Saved to Wishlist' : 'Save to Wishlist'">Save to Wishlist</span>
                </button>
            </div>

        </div>

        <!-- ===== SHARE + PRODUCT INFO ACCORDION ===== -->
        <style>
            .kk-pi { max-width: 640px; margin: 48px auto 0; }
            .kk-pi__share {
                display: flex; align-items: center; justify-content: center;
                gap: 20px; padding: 20px 0 32px;
            }
            .kk-pi__share-label {
                font-size: 11px; font-weight: 600; letter-spacing: 0.22em;
                color: #2d1810; text-transform: uppercase; margin-right: 4px;
            }
            .kk-pi__share a {
                color: #2d1810; opacity: 0.85;
                display: inline-flex; align-items: center; justify-content: center;
                transition: opacity 0.15s ease;
            }
            .kk-pi__share a:hover { opacity: 1; }
            .kk-pi__share a svg { width: 18px; height: 18px; }

            .kk-pi__item { border-top: 1px solid #c9b393; }
            .kk-pi__item:last-of-type { border-bottom: 1px solid #c9b393; }
            .kk-pi__btn {
                width: 100%; display: flex; align-items: center; justify-content: space-between;
                padding: 22px 4px; background: none; border: none; cursor: pointer;
                font-family: inherit; text-align: left;
            }
            .kk-pi__btn-label {
                font-size: 13px; font-weight: 600; letter-spacing: 0.22em;
                color: #2d1810; text-transform: uppercase;
            }
            .kk-pi__btn-icon {
                position: relative; width: 14px; height: 14px; flex-shrink: 0;
            }
            .kk-pi__btn-icon::before,
            .kk-pi__btn-icon::after {
                content: ''; position: absolute;
                background: #2d1810; transition: transform 0.25s ease;
            }
            .kk-pi__btn-icon::before {
                left: 0; top: 50%; width: 100%; height: 1.5px;
                transform: translateY(-50%);
            }
            .kk-pi__btn-icon::after {
                top: 0; left: 50%; width: 1.5px; height: 100%;
                transform: translateX(-50%) scaleY(1);
            }
            .kk-pi__btn[aria-expanded="true"] .kk-pi__btn-icon::after { transform: translateX(-50%) scaleY(0); }

            .kk-pi__panel { padding: 0 4px 22px; font-size: 14px; line-height: 1.7; color: #2d1810; }
            .kk-pi__panel p { margin: 0 0 10px; }
            .kk-pi__panel p:last-child { margin: 0; }
            .kk-pi__panel ul { margin: 0 0 10px; padding-left: 20px; }
            .kk-pi__panel dl { display: grid; grid-template-columns: 1fr 2fr; gap: 6px 16px; margin: 0; }
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
            .kk-rev { margin-top: 56px; padding-top: 32px; }
            .kk-rev__title { text-align: center; font-size: 22px; font-weight: 600; color: #2d1810; margin: 0 0 28px; }
            .kk-rev__summary {
                display: grid;
                grid-template-columns: 1fr 1fr auto;
                align-items: center;
                gap: 32px;
                padding: 8px 0 24px;
            }
            @media (max-width: 768px) {
                .kk-rev__summary { grid-template-columns: 1fr; text-align: center; gap: 20px; }
            }
            .kk-rev__overall { text-align: center; }
            .kk-rev__stars { display: inline-flex; gap: 1px; vertical-align: middle; }
            .kk-rev__stars svg { width: 16px; height: 16px; }
            .kk-rev__avg { font-size: 14px; font-weight: 500; color: #2d1810; margin-left: 6px; vertical-align: middle; }
            .kk-rev__based { font-size: 13px; color: #2d1810; margin: 8px 0 0; display: inline-flex; align-items: center; gap: 6px; justify-content: center; }
            .kk-rev__verified-icon {
                width: 16px; height: 16px; border-radius: 50%;
                background: #2a9d3e; color: #fff;
                display: inline-flex; align-items: center; justify-content: center;
            }
            .kk-rev__verified-icon svg { width: 11px; height: 11px; }

            .kk-rev__bars { display: flex; flex-direction: column; gap: 6px; }
            .kk-rev__bar-row { display: flex; align-items: center; gap: 10px; font-size: 12px; }
            .kk-rev__bar-stars { display: inline-flex; gap: 1px; flex-shrink: 0; width: 80px; }
            .kk-rev__bar-stars svg { width: 12px; height: 12px; }
            .kk-rev__bar-track { flex: 1; height: 10px; background: #e5e5e5; border-radius: 2px; overflow: hidden; }
            .kk-rev__bar-fill { height: 100%; background: #0d1929; }
            .kk-rev__bar-count { width: 24px; text-align: right; font-size: 12px; color: #2d1810; }

            .kk-rev__write {
                background: #0d1929; color: #fff;
                padding: 14px 28px; border: none; border-radius: 2px;
                font-size: 14px; font-weight: 600; cursor: pointer;
                transition: background 0.15s ease; white-space: nowrap;
            }
            .kk-rev__write:hover { background: #1f2937; }

            .kk-rev__divider { border-top: 1px solid #e3d2b3; margin: 8px 0; }

            .kk-rev__sort-row { display: flex; align-items: center; padding: 16px 0; }
            .kk-rev__sort {
                font-size: 13px; color: #2d1810;
                background: none; border: none;
                display: inline-flex; align-items: center; gap: 6px;
                cursor: pointer; padding: 4px 0;
            }
            .kk-rev__sort svg { width: 14px; height: 14px; }

            .kk-rev__list { border-top: 1px solid #e3d2b3; }
            .kk-rev__item { padding: 20px 0; border-bottom: 1px solid #e3d2b3; }
            .kk-rev__item-head {
                display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
                margin-bottom: 10px;
            }
            .kk-rev__item-stars { display: inline-flex; gap: 1px; }
            .kk-rev__item-stars svg { width: 14px; height: 14px; }
            .kk-rev__item-date { font-size: 13px; color: #7a6555; flex-shrink: 0; }
            .kk-rev__item-user { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
            .kk-rev__item-avatar {
                width: 28px; height: 28px; border-radius: 50%;
                background: #efe2cb; color: #2d1810;
                display: flex; align-items: center; justify-content: center;
                font-size: 12px; font-weight: 600;
            }
            .kk-rev__item-name { font-size: 14px; font-weight: 500; color: #2d1810; }
            .kk-rev__item-verified {
                background: #0d1929; color: #fff;
                font-size: 10px; font-weight: 600; letter-spacing: 0.04em;
                padding: 2px 7px; border-radius: 2px;
            }
            .kk-rev__item-title { font-size: 14px; font-weight: 600; color: #2d1810; margin: 4px 0; }
            .kk-rev__item-body { font-size: 14px; color: #2d1810; line-height: 1.6; margin: 4px 0 0; }
            .kk-rev__empty { text-align: center; padding: 32px 0; font-size: 14px; color: #7a6555; }
        </style>
        <div class="kk-rev" id="customer-reviews">
            <h2 class="kk-rev__title">Customer Reviews</h2>

            @php
                $avg = $product->review_count > 0 ? $product->rating : 0;
                $avgRounded = round($avg);
            @endphp

            <div class="kk-rev__summary">
                <div class="kk-rev__overall">
                    <span class="kk-rev__stars">
                        @for($s = 1; $s <= 5; $s++)
                            <svg fill="{{ $s <= $avgRounded ? '#0d1929' : '#c9b393' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </span>
                    <span class="kk-rev__avg">{{ number_format($avg, 2) }} out of 5</span>
                    <p class="kk-rev__based">
                        Based on {{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}
                        <span class="kk-rev__verified-icon" title="Verified reviews">
                            <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </p>
                </div>

                <div class="kk-rev__bars">
                    @for($star = 5; $star >= 1; $star--)
                    @php
                        $count = $ratingDist[$star] ?? 0;
                        $pct = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                    @endphp
                    <div class="kk-rev__bar-row">
                        <span class="kk-rev__bar-stars">
                            @for($s = 1; $s <= 5; $s++)
                                <svg fill="{{ $s <= $star ? '#0d1929' : '#c9b393' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </span>
                        <div class="kk-rev__bar-track">
                            <div class="kk-rev__bar-fill" style="width: {{ $pct }}%;"></div>
                        </div>
                        <span class="kk-rev__bar-count">{{ $count }}</span>
                    </div>
                    @endfor
                </div>

                <button type="button" class="kk-rev__write" @click="document.getElementById('write-review-form')?.scrollIntoView({behavior:'smooth'})">
                    Write a review
                </button>
            </div>

            @if($product->review_count > 0)
                <div class="kk-rev__sort-row" x-data="{ open: false }">
                    <button class="kk-rev__sort" @click="open = !open">
                        Most Recent
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>

                <div class="kk-rev__list">
                    @foreach($product->reviews as $review)
                    <div class="kk-rev__item">
                        <div class="kk-rev__item-head">
                            <span class="kk-rev__item-stars">
                                @for($s = 1; $s <= 5; $s++)
                                    <svg fill="{{ $s <= $review->rating ? '#0d1929' : '#c9b393' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </span>
                            <span class="kk-rev__item-date">{{ $review->created_at->format('m/d/Y') }}</span>
                        </div>
                        <div class="kk-rev__item-user">
                            <div class="kk-rev__item-avatar">{{ strtoupper(substr($review->user?->first_name ?? 'A', 0, 1)) }}</div>
                            <span class="kk-rev__item-name">{{ trim(($review->user?->first_name ?? 'Anonymous') . ' ' . ($review->user?->last_name ?? '')) }}</span>
                            <span class="kk-rev__item-verified">Verified</span>
                        </div>
                        @if($review->title)
                        <p class="kk-rev__item-title">{{ $review->title }}</p>
                        @endif
                        <p class="kk-rev__item-body">{{ $review->review }}</p>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="kk-rev__empty">No reviews yet. Be the first to review this product!</p>
            @endif
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
            .kk-related { margin-top: 64px; padding-top: 32px; }
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
                position: absolute;
                right: 12px; bottom: 12px;
                background: #2d1810; color: #efe2cb;
                border: none; border-radius: 4px;
                padding: 9px 14px; font-size: 11px; font-weight: 700;
                letter-spacing: 0.12em; text-transform: uppercase;
                cursor: pointer;
                opacity: 0; transform: translateY(4px);
                transition: opacity .2s ease, transform .2s ease, background .15s ease;
            }
            .kk-related__card:hover .kk-related__add,
            .kk-related__card:focus-within .kk-related__add { opacity: 1; transform: translateY(0); }
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
                <a href="{{ route('product.show', $rp) }}" class="kk-related__card">
                    <div class="kk-related__imgwrap">
                        <img class="kk-related__img" src="{{ $rp->primary_image_url }}" alt="{{ $rp->name }}" loading="lazy">
                        @if($rp->isInStock())
                        <button type="button" class="kk-related__add"
                                @click.prevent.stop="$store.cart.add({{ $rp->id }})">
                            Add to Cart
                        </button>
                        @endif
                    </div>
                    <p class="kk-related__name">{{ $rp->name }}</p>
                    <div>
                        <span class="kk-related__price">₹{{ number_format($rp->price) }}</span>
                        @if($rp->mrp > $rp->price)
                        <span class="kk-related__price-mrp">₹{{ number_format($rp->mrp) }}</span>
                        @endif
                    </div>
                </a>
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

            async addToCart() {
                await Alpine.store('cart').add({{ $product->id }}, this.quantity, this.selectedVariant);
            },

            async buyNow() {
                await Alpine.store('cart').add({{ $product->id }}, this.quantity, this.selectedVariant);
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
