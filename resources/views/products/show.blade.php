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

        // Rating distribution
        $ratingDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $totalReviews = $product->review_count ?: 0;
        foreach ($product->reviews as $r) {
            if (isset($ratingDist[$r->rating])) {
                $ratingDist[$r->rating]++;
            }
        }
    @endphp

    <!-- Breadcrumb -->
    <div style="background:#f8f8f8;border-bottom:1px solid #e5e5e5;">
        <div class="container mx-auto px-4" style="padding-top:0.5rem;padding-bottom:0.5rem;">
            <x-breadcrumb :items="$breadcrumbs" />
        </div>
    </div>

    <style>
    @media (min-width: 1024px) {
        .product-page-grid { grid-template-columns: 1fr 1fr 300px; }
    }
    </style>
    <div class="container mx-auto px-4 py-4 lg:py-6" x-data="productPage()">

        <!-- ===== MAIN 3-COLUMN LAYOUT ===== -->
        <div class="grid grid-cols-1 gap-6 lg:gap-8 product-page-grid">

            <!-- ===== LEFT: IMAGE GALLERY ===== -->
            <div>
                <div class="lg:flex lg:gap-3 lg:sticky lg:top-4">
                    <!-- Vertical Thumbnails (desktop) -->
                    @if(count($images) > 1)
                    <div class="hidden lg:flex lg:flex-col gap-2 shrink-0" style="width:64px;">
                        @foreach($images as $i => $img)
                        <button @mouseenter="currentImage = {{ $i }}" @click="currentImage = {{ $i }}"
                                class="rounded overflow-hidden transition-all"
                                style="width:64px;height:64px;padding:2px;"
                                :style="currentImage === {{ $i }} ? 'border:2px solid #6F9CA2;' : 'border:2px solid #e5e5e5;'">
                            <img src="{{ $img }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:2px;">
                        </button>
                        @endforeach
                    </div>
                    @endif

                    <!-- Main Image -->
                    <div style="flex:1;position:relative;">
                        <div @click="showZoom = true"
                             style="background:#fff;border:1px solid #e5e5e5;border-radius:0.75rem;overflow:hidden;cursor:zoom-in;aspect-ratio:1/1;">
                            @foreach($images as $i => $img)
                            <img x-show="currentImage === {{ $i }}"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 src="{{ $img }}"
                                 alt="{{ $product->name }}"
                                 style="width:100%;height:100%;object-fit:contain;padding:0.5rem;">
                            @endforeach
                        </div>

                        <!-- Discount Badge -->
                        @if($discountPct > 0)
                        <div style="position:absolute;top:0.75rem;left:0.75rem;background:#cc0c39;color:#fff;padding:0.125rem 0.5rem;border-radius:0.25rem;font-size:12px;font-weight:700;">
                            -{{ $discountPct }}%
                        </div>
                        @endif

                        <!-- Wishlist -->
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                style="position:absolute;top:0.75rem;right:0.75rem;width:2.5rem;height:2.5rem;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.12);display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;"
                                :style="$store.wishlist.has({{ $product->id }}) ? 'color:#ef4444;' : 'color:#9ca3af;'"
                                aria-label="Toggle wishlist">
                            <svg style="width:1.25rem;height:1.25rem;" :fill="$store.wishlist.has({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </button>

                        <!-- Mobile Nav Arrows -->
                        @if(count($images) > 1)
                        <button @click="currentImage = currentImage > 0 ? currentImage - 1 : {{ count($images) - 1 }}"
                                class="lg:hidden"
                                style="position:absolute;left:0.5rem;top:50%;transform:translateY(-50%);width:2rem;height:2rem;border-radius:50%;background:rgba(255,255,255,0.9);box-shadow:0 1px 3px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;">
                            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="currentImage = currentImage < {{ count($images) - 1 }} ? currentImage + 1 : 0"
                                class="lg:hidden"
                                style="position:absolute;right:3.5rem;top:50%;transform:translateY(-50%);width:2rem;height:2rem;border-radius:50%;background:rgba(255,255,255,0.9);box-shadow:0 1px 3px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;">
                            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        @endif

                        <!-- Mobile Dots -->
                        @if(count($images) > 1)
                        <div class="flex lg:hidden" style="justify-content:center;gap:0.375rem;margin-top:0.75rem;">
                            @foreach($images as $i => $img)
                            <button @click="currentImage = {{ $i }}"
                                    style="height:0.5rem;border-radius:9999px;border:none;cursor:pointer;transition:all 0.2s;"
                                    :style="currentImage === {{ $i }} ? 'background:#6F9CA2;width:20px;' : 'background:#d1d5db;width:8px;'"></button>
                            @endforeach
                        </div>
                        @endif

                        <!-- Image Counter -->
                        @if(count($images) > 1)
                        <div class="hidden lg:flex" style="position:absolute;bottom:0.75rem;right:0.75rem;background:rgba(0,0,0,0.6);color:#fff;padding:0.25rem 0.5rem;border-radius:0.25rem;font-size:12px;font-weight:500;align-items:center;gap:0.25rem;">
                            <svg style="width:0.75rem;height:0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span x-text="(currentImage + 1) + '/{{ count($images) }}'"></span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ===== MIDDLE: PRODUCT INFO ===== -->
            <div>
                <!-- Brand -->
                @if($product->brand)
                <a href="{{ route('products.index', ['brand' => $product->brand->slug ?? '']) }}"
                   style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6F9CA2;text-decoration:none;display:inline-block;margin-bottom:0.25rem;">
                    {{ $product->brand->name }}
                </a>
                @endif

                <!-- Title -->
                <h1 style="font-size:1.375rem;font-weight:600;line-height:1.3;color:#0F1111;margin-bottom:0.5rem;">
                    {{ $product->name }}
                </h1>

                <!-- Rating -->
                @if($product->rating > 0)
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:0.25rem;">
                        <span style="font-size:14px;font-weight:600;color:#007185;">{{ number_format($product->rating, 1) }}</span>
                        <div style="display:flex;">
                            @for($s = 1; $s <= 5; $s++)
                            <svg style="width:1rem;height:1rem;color:{{ $s <= round($product->rating) ? '#FFA41C' : '#ddd' }};" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <a href="#customer-reviews" @click.prevent="document.getElementById('customer-reviews')?.scrollIntoView({behavior:'smooth'})"
                           style="font-size:14px;color:#007185;text-decoration:none;">{{ $product->review_count }} {{ Str::plural('rating', $product->review_count) }}</a>
                    </div>
                    @if($product->sales_count > 0)
                    <span style="font-size:12px;color:#565959;">{{ number_format($product->sales_count) }}+ bought</span>
                    @endif
                </div>
                @endif

                <div style="border-top:1px solid #e5e5e5;margin:0.5rem 0;"></div>

                <!-- Price Block -->
                <div style="margin-bottom:1rem;">
                    @if($discountPct > 0)
                    <div style="display:inline-block;background:#cc0c39;color:#fff;padding:0.125rem 0.5rem;border-radius:0.25rem;font-size:12px;font-weight:700;margin-bottom:0.375rem;">Limited Time Deal</div>
                    <div style="display:flex;align-items:baseline;gap:0.5rem;">
                        <span style="font-size:14px;color:#cc0c39;font-weight:500;">-{{ $discountPct }}%</span>
                        <span style="font-size:1.75rem;font-weight:500;color:#0F1111;" x-text="'₹' + currentPrice.toLocaleString('en-IN')">₹{{ number_format($product->price) }}</span>
                    </div>
                    <div style="margin-top:0.125rem;">
                        <span style="font-size:13px;color:#565959;">M.R.P.: </span>
                        <span style="font-size:13px;color:#565959;text-decoration:line-through;" x-text="'₹' + currentMrp.toLocaleString('en-IN')">₹{{ number_format($product->mrp) }}</span>
                    </div>
                    @else
                    <span style="font-size:1.75rem;font-weight:500;color:#0F1111;" x-text="'₹' + currentPrice.toLocaleString('en-IN')">₹{{ number_format($product->price) }}</span>
                    @endif
                    <p style="font-size:12px;color:#565959;margin-top:0.25rem;">Inclusive of all taxes</p>
                </div>

                <!-- Stock Status -->
                <div style="margin-bottom:0.75rem;">
                    @if($product->isInStock())
                    <span style="font-size:15px;font-weight:500;color:#007600;">&#10003; In Stock</span>
                    @if($product->stock_quantity <= 5 && $product->stock_quantity > 0)
                    <span style="font-size:12px;color:#cc0c39;margin-left:0.5rem;">Only {{ $product->stock_quantity }} left - order soon!</span>
                    @endif
                    @if($product->sales_count >= 10)
                    <p style="font-size:12px;color:#565959;margin-top:0.125rem;">{{ number_format($product->sales_count) }}+ bought this month</p>
                    @endif
                    @else
                    <span style="font-size:15px;font-weight:500;color:#cc0c39;">Currently Unavailable</span>
                    @endif
                </div>

                <!-- Coupon Offers -->
                @if(isset($activeCoupons) && $activeCoupons->count() > 0)
                <div style="margin-bottom:1rem;" x-data="{ copiedCode: '' }">
                    <h3 style="font-size:14px;font-weight:700;color:#0F1111;margin-bottom:0.5rem;">Offers</h3>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;">
                        @foreach($activeCoupons as $coupon)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0.75rem;border:1px dashed #6F9CA2;border-radius:0.5rem;background:#f8fcfc;">
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:1.25rem;height:1.25rem;border-radius:0.25rem;background:#CC0C39;color:#fff;flex-shrink:0;">
                                    <svg style="width:0.75rem;height:0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </span>
                                <div>
                                    <span style="font-size:13px;font-weight:600;color:#0F1111;">{{ $coupon->code }}</span>
                                    <span style="font-size:12px;color:#565959;margin-left:0.25rem;">
                                        @if($coupon->type === 'percentage')
                                            {{ number_format($coupon->value) }}% off
                                        @elseif($coupon->type === 'fixed')
                                            ₹{{ number_format($coupon->value) }} off
                                        @elseif($coupon->type === 'free_shipping')
                                            Free shipping
                                        @endif
                                        @if($coupon->min_order_amount > 0)
                                            on orders above ₹{{ number_format($coupon->min_order_amount) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <button @click="navigator.clipboard.writeText('{{ $coupon->code }}'); copiedCode = '{{ $coupon->code }}'; setTimeout(() => copiedCode = '', 2000)"
                                    style="font-size:12px;font-weight:600;color:#6F9CA2;background:none;border:1px solid #6F9CA2;border-radius:0.25rem;padding:0.25rem 0.625rem;cursor:pointer;white-space:nowrap;"
                                    x-text="copiedCode === '{{ $coupon->code }}' ? 'Copied!' : 'Copy'">Copy</button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Offers (static trust offers) -->
                <div style="margin-bottom:1rem;">
                    @if(!isset($activeCoupons) || $activeCoupons->count() === 0)
                    <h3 style="font-size:14px;font-weight:700;color:#0F1111;margin-bottom:0.5rem;">Offers</h3>
                    @endif
                    <div style="display:flex;flex-direction:column;gap:0.5rem;">
                        <div style="display:flex;align-items:flex-start;gap:0.5rem;font-size:13px;">
                            <span style="flex-shrink:0;margin-top:0.125rem;width:1.25rem;height:1.25rem;border-radius:0.25rem;display:flex;align-items:center;justify-content:center;background:#CC0C39;color:#fff;">
                                <svg style="width:0.75rem;height:0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            </span>
                            <div><span style="font-weight:500;color:#0F1111;">Free Delivery</span> <span style="color:#565959;">on orders above ₹499</span></div>
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:0.5rem;font-size:13px;">
                            <span style="flex-shrink:0;margin-top:0.125rem;width:1.25rem;height:1.25rem;border-radius:0.25rem;display:flex;align-items:center;justify-content:center;background:#CC0C39;color:#fff;">
                                <svg style="width:0.75rem;height:0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </span>
                            <div><span style="font-weight:500;color:#0F1111;">Easy Returns</span> <span style="color:#565959;">7 day return & exchange policy</span></div>
                        </div>
                        @if($savings > 0)
                        <div style="display:flex;align-items:flex-start;gap:0.5rem;font-size:13px;">
                            <span style="flex-shrink:0;margin-top:0.125rem;width:1.25rem;height:1.25rem;border-radius:0.25rem;display:flex;align-items:center;justify-content:center;background:#CC0C39;color:#fff;">
                                <svg style="width:0.75rem;height:0.75rem;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 9H10a3 3 0 013 3v1a1 1 0 102 0v-1a5 5 0 00-5-5H8.414l1.293-1.293z" clip-rule="evenodd"/></svg>
                            </span>
                            <div><span style="font-weight:500;color:#0F1111;">You Save</span> <span style="color:#CC0C39;">₹{{ number_format($savings) }} ({{ $discountPct }}% off)</span></div>
                        </div>
                        @endif
                    </div>
                </div>

                <div style="border-top:1px solid #e5e5e5;margin:0.75rem 0;"></div>

                <!-- Variant Selectors -->
                @if(!empty($variantGroups))
                <div style="margin-bottom:1rem;">
                    @foreach($variantGroups as $attrName => $values)
                    <div style="margin-bottom:1rem;">
                        <h3 style="font-size:14px;font-weight:700;color:#0F1111;margin-bottom:0.5rem;">
                            {{ $attrName }}: <span style="font-weight:400;color:#565959;" x-text="selectedAttributes['{{ $attrName }}'] || ''"></span>
                        </h3>
                        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                            @foreach($values as $val)
                            <button @click="selectAttribute('{{ $attrName }}', '{{ $val }}')"
                                    style="border-radius:0.5rem;font-size:13px;font-weight:500;padding:0.375rem 1rem;cursor:pointer;transition:all 0.15s;background:#fff;color:#0F1111;"
                                    :style="selectedAttributes['{{ $attrName }}'] === '{{ $val }}'
                                        ? 'border:2px solid #6F9CA2;background:#f0f7f8;'
                                        : 'border:2px solid #e5e5e5;'">{{ $val }}</button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    <div style="border-top:1px solid #e5e5e5;margin:0.75rem 0;"></div>
                </div>
                @endif
            </div>

            <!-- ===== RIGHT: BUY BOX ===== -->
            <div x-ref="buyBox">
                <div class="lg:sticky lg:top-4">
                    <div style="border:1px solid #e5e5e5;border-radius:0.75rem;padding:1.25rem;background:#fff;">
                        <!-- Price (desktop only - already shown in info column) -->
                        <div class="hidden lg:block" style="margin-bottom:0.25rem;">
                            <span style="font-size:1.75rem;font-weight:500;color:#0F1111;" x-text="'₹' + currentPrice.toLocaleString('en-IN')">₹{{ number_format($product->price) }}</span>
                        </div>
                        @if($discountPct > 0)
                        <div class="hidden lg:block" style="margin-bottom:0.25rem;">
                            <span style="font-size:13px;color:#565959;">M.R.P.: </span>
                            <span style="font-size:13px;color:#565959;text-decoration:line-through;" x-text="'₹' + currentMrp.toLocaleString('en-IN')">₹{{ number_format($product->mrp) }}</span>
                        </div>
                        @endif
                        <!-- Stock -->
                        <div style="margin-bottom:0.75rem;">
                            @if($product->isInStock())
                            <span style="font-size:14px;color:#007600;font-weight:500;">In stock</span>
                            @else
                            <span style="font-size:14px;color:#cc0c39;font-weight:500;">Currently Unavailable</span>
                            @endif
                        </div>

                        @if(!$product->isInStock())
                        <!-- Back in Stock Notification -->
                        <div x-data="{ bisEmail: '{{ auth()->user()->email ?? '' }}', bisSubmitted: false, bisError: '' }" style="margin-bottom:0.75rem;">
                            <div x-show="!bisSubmitted" style="padding:0.75rem;border:1px solid #fcd5ce;border-radius:0.5rem;background:#fff5f5;">
                                <p style="font-size:13px;font-weight:600;color:#0F1111;margin-bottom:0.5rem;">Get notified when back in stock</p>
                                <div style="display:flex;gap:0.375rem;">
                                    <input type="email" x-model="bisEmail" placeholder="Enter your email"
                                           style="flex:1;padding:0.375rem 0.5rem;border:1px solid #d5d9d9;border-radius:0.375rem;font-size:13px;outline:none;">
                                    <button @click="
                                        bisError = '';
                                        if (!bisEmail || !bisEmail.includes('@')) { bisError = 'Enter a valid email'; return; }
                                        fetch('{{ route('product.notify-back-in-stock', $product) }}', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ email: bisEmail })
                                        }).then(r => r.json()).then(d => { bisSubmitted = true; }).catch(() => { bisError = 'Something went wrong'; });
                                    " style="padding:0.375rem 0.75rem;border-radius:0.375rem;font-size:13px;font-weight:500;background:#6F9CA2;color:#fff;border:none;cursor:pointer;white-space:nowrap;">
                                        Notify Me
                                    </button>
                                </div>
                                <p x-show="bisError" x-text="bisError" style="font-size:11px;color:#cc0c39;margin-top:0.25rem;" x-cloak></p>
                            </div>
                            <div x-show="bisSubmitted" x-cloak style="padding:0.75rem;border:1px solid #c6f0c6;border-radius:0.5rem;background:#f0fdf4;">
                                <div style="display:flex;align-items:center;gap:0.375rem;">
                                    <svg style="width:1rem;height:1rem;color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span style="font-size:13px;color:#16a34a;font-weight:500;">We'll notify you when it's back!</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($product->isInStock())
                        <!-- Estimated Delivery -->
                        @php
                            $deliveryMin = now()->addDays(3);
                            $deliveryMax = now()->addDays(7);
                            // Skip weekends for min date
                            while ($deliveryMin->isWeekend()) $deliveryMin->addDay();
                            while ($deliveryMax->isWeekend()) $deliveryMax->addDay();
                        @endphp
                        <div style="margin-bottom:0.75rem;padding:0.5rem 0.625rem;border:1px solid #e5e5e5;border-radius:0.5rem;background:#f7fafa;">
                            <div style="display:flex;align-items:flex-start;gap:0.5rem;">
                                <svg style="width:1.125rem;height:1.125rem;color:#6F9CA2;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                <div>
                                    <span style="font-size:13px;color:#0F1111;font-weight:500;">FREE Delivery: </span>
                                    <span style="font-size:13px;color:#0F1111;font-weight:700;">{{ $deliveryMin->format('D, d M') }} - {{ $deliveryMax->format('D, d M') }}</span>
                                    <div style="font-size:11px;color:#565959;margin-top:2px;">Order within <span style="color:#007600;font-weight:500;">{{ 24 - now()->hour }}h {{ 60 - now()->minute }}m</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;">
                            <label style="font-size:14px;color:#0F1111;">Qty:</label>
                            <select x-model.number="quantity" style="padding:0.5rem 1.5rem 0.5rem 0.5rem;border:1px solid #d5d9d9;border-radius:0.5rem;font-size:14px;background:#f0f2f2;color:#0F1111;cursor:pointer;outline:none;min-height:44px;">
                                @for($q = 1; $q <= 10; $q++)
                                <option value="{{ $q }}">{{ $q }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Add to Cart -->
                        <button @click="addToCart()"
                                :disabled="$store.cart.isLoading || (!inStock)"
                                style="width:100%;padding:0.75rem;border-radius:9999px;font-size:14px;font-weight:500;cursor:pointer;background:#FFA41C;color:#0F1111;border:1px solid #FF8F00;transition:background 0.15s;margin-bottom:0.5rem;"
                                onmouseenter="this.style.background='#FA8900'" onmouseleave="this.style.background='#FFA41C'"
                                :class="{ 'opacity-60 cursor-wait': $store.cart.isLoading }">
                            <span x-show="!$store.cart.isLoading">Add to Cart</span>
                            <span x-show="$store.cart.isLoading" x-cloak>Adding...</span>
                        </button>

                        <!-- Buy Now -->
                        <button @click="buyNow()"
                                :disabled="$store.cart.isLoading || (!inStock)"
                                style="width:100%;padding:0.75rem;border-radius:9999px;font-size:14px;font-weight:500;cursor:pointer;background:#FFD814;color:#0F1111;border:1px solid #FCD200;transition:background 0.15s;"
                                onmouseenter="this.style.background='#F7CA00'" onmouseleave="this.style.background='#FFD814'">
                            Buy Now
                        </button>
                        @endif

                        <!-- Secure Transaction -->
                        <div style="margin-top:1rem;display:flex;align-items:center;gap:0.375rem;">
                            <svg style="width:0.875rem;height:0.875rem;color:#6F9CA2;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span style="font-size:12px;font-weight:500;color:#6F9CA2;">Secure transaction</span>
                        </div>

                        <!-- Payment Badges -->
                        <div style="display:flex;align-items:center;gap:0.375rem;flex-wrap:wrap;margin-top:0.5rem;">
                            <span style="font-size:10px;font-weight:600;color:#1A1F71;background:#f0f0f0;padding:0.125rem 0.375rem;border-radius:0.125rem;">VISA</span>
                            <span style="font-size:10px;font-weight:600;color:#EB001B;background:#f0f0f0;padding:0.125rem 0.375rem;border-radius:0.125rem;">MC</span>
                            <span style="font-size:10px;font-weight:600;color:#6F9CA2;background:#f0f0f0;padding:0.125rem 0.375rem;border-radius:0.125rem;">UPI</span>
                            <span style="font-size:10px;font-weight:600;color:#005BAC;background:#f0f0f0;padding:0.125rem 0.375rem;border-radius:0.125rem;">RuPay</span>
                            <span style="font-size:10px;font-weight:600;color:#333;background:#f0f0f0;padding:0.125rem 0.375rem;border-radius:0.125rem;">Net Banking</span>
                        </div>

                        <!-- Pay on Delivery -->
                        <div style="margin-top:0.5rem;">
                            <span style="font-size:10px;font-weight:600;color:#fff;background:#6F9CA2;padding:0.125rem 0.5rem;border-radius:0.125rem;">Pay on Delivery</span>
                        </div>
                    </div>

                    <!-- Seller Info -->
                    <div style="margin-top:0.75rem;font-size:13px;color:#565959;padding:0 0.25rem;">
                        Ships from <span style="font-weight:500;color:#0F1111;">{{ config('app.name') }}</span>
                        @if($product->seller)
                        <br>Sold by <span style="font-weight:500;color:#007185;">{{ $product->seller->business_name ?? $product->seller->name ?? config('app.name') }}</span>
                        @endif
                    </div>

                    <!-- Wishlist -->
                    <div style="margin-top:0.625rem;padding:0 0.25rem;">
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                style="display:inline-flex;align-items:center;gap:0.25rem;font-size:12px;background:none;border:none;cursor:pointer;padding:0;color:#007185;">
                            <svg style="width:0.75rem;height:0.75rem;" :fill="$store.wishlist.has({{ $product->id }}) ? 'currentColor' : 'none'" :style="$store.wishlist.has({{ $product->id }}) ? 'color:#ef4444;' : 'color:#007185;'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            <span x-text="$store.wishlist.has({{ $product->id }}) ? 'Remove from Wishlist' : 'Add to Wishlist'">Add to Wishlist</span>
                        </button>
                    </div>

                    <!-- Share -->
                    <div style="margin-top:0.5rem;padding:0 0.25rem;display:flex;align-items:center;gap:1rem;">
                        <button @click="shareViaWhatsApp()" style="display:flex;align-items:center;gap:0.25rem;font-size:12px;font-weight:500;color:#565959;background:none;border:none;cursor:pointer;" aria-label="Share on WhatsApp">
                            <svg style="width:0.875rem;height:0.875rem;color:#25D366;" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Share
                        </button>
                        <button @click="copyLink()" style="display:flex;align-items:center;gap:0.25rem;font-size:12px;font-weight:500;color:#565959;background:none;border:none;cursor:pointer;">
                            <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="linkCopied ? 'Copied!' : 'Copy link'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ABOUT THIS ITEM ===== -->
        @if($product->short_description)
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:0.75rem;">About this item</h2>
            <div style="font-size:14px;color:#333;line-height:1.7;">
                {!! nl2br(e($product->short_description)) !!}
            </div>
        </div>
        @endif

        <!-- ===== PRODUCT DESCRIPTION ===== -->
        @if($product->description)
        <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:0.75rem;">Product Description</h2>
            <div style="font-size:14px;color:#333;line-height:1.7;">
                {!! $product->description !!}
            </div>
        </div>
        @endif

        <!-- ===== PRODUCT SPECIFICATIONS ===== -->
        @if(($product->specifications && count($product->specifications) > 0) || ($product->attributes && count($product->attributes) > 0))
        <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:0.75rem;">Product Specifications</h2>
            <div style="max-width:40rem;">
                <table style="width:100%;font-size:14px;border-collapse:collapse;">
                    @if($product->brand)
                    <tr>
                        <td style="padding:0.625rem 0.75rem;font-weight:500;color:#565959;width:40%;border-bottom:1px solid #e5e5e5;background:#fafafa;">Brand</td>
                        <td style="padding:0.625rem 0.75rem;color:#0F1111;border-bottom:1px solid #e5e5e5;">{{ $product->brand->name }}</td>
                    </tr>
                    @endif
                    @if($product->sku)
                    <tr>
                        <td style="padding:0.625rem 0.75rem;font-weight:500;color:#565959;width:40%;border-bottom:1px solid #e5e5e5;">SKU</td>
                        <td style="padding:0.625rem 0.75rem;color:#0F1111;border-bottom:1px solid #e5e5e5;">{{ $product->sku }}</td>
                    </tr>
                    @endif
                    @if($product->category)
                    <tr>
                        <td style="padding:0.625rem 0.75rem;font-weight:500;color:#565959;width:40%;border-bottom:1px solid #e5e5e5;background:#fafafa;">Category</td>
                        <td style="padding:0.625rem 0.75rem;color:#0F1111;border-bottom:1px solid #e5e5e5;">{{ $product->category->name }}</td>
                    </tr>
                    @endif
                    @if($product->attributes && count($product->attributes) > 0)
                        @foreach($product->attributes as $key => $value)
                        <tr>
                            <td style="padding:0.625rem 0.75rem;font-weight:500;color:#565959;width:40%;border-bottom:1px solid #e5e5e5;{{ $loop->even ? 'background:#fafafa;' : '' }}">{{ $key }}</td>
                            <td style="padding:0.625rem 0.75rem;color:#0F1111;border-bottom:1px solid #e5e5e5;">{{ is_array($value) ? implode(', ', $value) : $value }}</td>
                        </tr>
                        @endforeach
                    @endif
                    @if($product->specifications && count($product->specifications) > 0)
                        @foreach($product->specifications as $key => $value)
                        <tr>
                            <td style="padding:0.625rem 0.75rem;font-weight:500;color:#565959;width:40%;border-bottom:1px solid #e5e5e5;{{ $loop->even ? 'background:#fafafa;' : '' }}">{{ $key }}</td>
                            <td style="padding:0.625rem 0.75rem;color:#0F1111;border-bottom:1px solid #e5e5e5;">{{ is_array($value) ? implode(', ', $value) : $value }}</td>
                        </tr>
                        @endforeach
                    @endif
                </table>
            </div>
        </div>
        @endif

        <!-- ===== FREQUENTLY BOUGHT TOGETHER ===== -->
        @if(isset($crossSellProducts) && $crossSellProducts->count() > 0)
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;" x-data="{
            items: [
                { id: {{ $product->id }}, name: '{{ addslashes($product->name) }}', price: {{ (float)$product->price }}, image: '{{ $product->primary_image_url }}', checked: true, url: '{{ route('product.show', $product) }}' },
                @foreach($crossSellProducts as $cs)
                { id: {{ $cs->id }}, name: '{{ addslashes($cs->name) }}', price: {{ (float)$cs->price }}, image: '{{ $cs->primary_image_url }}', checked: true, url: '{{ route('product.show', $cs) }}' },
                @endforeach
            ],
            get total() { return this.items.filter(i => i.checked).reduce((s, i) => s + i.price, 0); },
            get checkedIds() { return this.items.filter(i => i.checked).map(i => i.id); }
        }">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:1rem;">Frequently Bought Together</h2>
            <div class="flex flex-wrap items-center gap-3">
                <template x-for="(item, idx) in items" :key="item.id">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span x-show="idx > 0" style="font-size:1.5rem;font-weight:300;color:#ccc;">+</span>
                        <div style="position:relative;">
                            <a :href="item.url" style="display:block;width:90px;height:90px;border-radius:0.5rem;overflow:hidden;border:1px solid #e5e5e5;background:#fff;" class="sm:!w-[120px] sm:!h-[120px]">
                                <img :src="item.image" :alt="item.name" style="width:100%;height:100%;object-fit:contain;padding:0.25rem;">
                            </a>
                            <label style="position:absolute;top:-0.25rem;right:-0.25rem;background:#fff;border-radius:0.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.15);cursor:pointer;">
                                <input type="checkbox" x-model="item.checked" :disabled="idx === 0"
                                       style="width:1.125rem;height:1.125rem;accent-color:#6F9CA2;cursor:pointer;">
                            </label>
                        </div>
                    </div>
                </template>

                <div style="margin-left:1rem;">
                    <p style="font-size:14px;color:#565959;margin-bottom:0.375rem;">
                        Total: <span style="font-weight:700;color:#0F1111;" x-text="'₹' + total.toLocaleString('en-IN')"></span>
                    </p>
                    <button @click="addAllToCart(checkedIds)"
                            style="padding:0.5rem 1.25rem;border-radius:9999px;font-size:13px;font-weight:600;cursor:pointer;background:#FFD814;color:#0F1111;border:1px solid #FCD200;transition:background 0.15s;white-space:nowrap;"
                            onmouseenter="this.style.background='#F7CA00'" onmouseleave="this.style.background='#FFD814'">
                        Add all to Cart
                    </button>
                </div>
            </div>

            <!-- Item names & prices -->
            <div style="margin-top:0.75rem;">
                <template x-for="item in items" :key="item.id">
                    <div style="display:flex;align-items:center;gap:0.5rem;font-size:13px;padding:0.25rem 0;">
                        <input type="checkbox" x-model="item.checked" style="width:0.875rem;height:0.875rem;accent-color:#6F9CA2;cursor:pointer;">
                        <a :href="item.url" style="color:#007185;font-weight:500;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:20rem;" x-text="item.name"></a>
                        <span style="font-weight:700;color:#0F1111;flex-shrink:0;" x-text="'₹' + item.price.toLocaleString('en-IN')"></span>
                    </div>
                </template>
            </div>
        </div>
        @endif

        <!-- ===== COMPARE WITH SIMILAR ITEMS ===== -->
        @if(isset($compareProducts) && $compareProducts->count() >= 2)
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:1rem;">Compare with similar items</h2>
            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                <table style="width:100%;min-width:480px;border-collapse:collapse;font-size:13px;">
                    <!-- Product Images -->
                    <tr>
                        <td style="padding:0.75rem;width:100px;font-weight:600;color:#565959;vertical-align:top;border-bottom:1px solid #e5e5e5;"></td>
                        <td style="padding:0.75rem;text-align:center;border-bottom:1px solid #e5e5e5;background:#f8fcfc;">
                            <div style="width:120px;height:120px;margin:0 auto;border:1px solid #e5e5e5;border-radius:0.5rem;overflow:hidden;background:#fff;">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:contain;padding:0.25rem;">
                            </div>
                            <p style="font-size:12px;font-weight:600;color:#0F1111;margin-top:0.5rem;line-height:1.3;">{{ Str::limit($product->name, 40) }}</p>
                        </td>
                        @foreach($compareProducts->take(3) as $cp)
                        <td style="padding:0.75rem;text-align:center;border-bottom:1px solid #e5e5e5;">
                            <a href="{{ route('product.show', $cp) }}" style="text-decoration:none;">
                                <div style="width:120px;height:120px;margin:0 auto;border:1px solid #e5e5e5;border-radius:0.5rem;overflow:hidden;background:#fff;">
                                    <img src="{{ $cp->primary_image_url }}" alt="{{ $cp->name }}" style="width:100%;height:100%;object-fit:contain;padding:0.25rem;">
                                </div>
                                <p style="font-size:12px;font-weight:500;color:#007185;margin-top:0.5rem;line-height:1.3;">{{ Str::limit($cp->name, 40) }}</p>
                            </a>
                        </td>
                        @endforeach
                    </tr>
                    <!-- Price -->
                    <tr>
                        <td style="padding:0.5rem 0.75rem;font-weight:600;color:#565959;border-bottom:1px solid #e5e5e5;">Price</td>
                        <td style="padding:0.5rem 0.75rem;text-align:center;font-weight:700;color:#0F1111;border-bottom:1px solid #e5e5e5;background:#f8fcfc;">₹{{ number_format($product->price) }}</td>
                        @foreach($compareProducts->take(3) as $cp)
                        <td style="padding:0.5rem 0.75rem;text-align:center;font-weight:700;color:#0F1111;border-bottom:1px solid #e5e5e5;">₹{{ number_format($cp->price) }}</td>
                        @endforeach
                    </tr>
                    <!-- Rating -->
                    <tr>
                        <td style="padding:0.5rem 0.75rem;font-weight:600;color:#565959;border-bottom:1px solid #e5e5e5;">Rating</td>
                        <td style="padding:0.5rem 0.75rem;text-align:center;border-bottom:1px solid #e5e5e5;background:#f8fcfc;">
                            @if($product->rating > 0)
                            <div style="display:flex;align-items:center;justify-content:center;gap:0.25rem;">
                                <span style="font-weight:600;color:#0F1111;">{{ number_format($product->rating, 1) }}</span>
                                <svg style="width:0.875rem;height:0.875rem;color:#FFA41C;" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                            @else
                            <span style="color:#565959;">-</span>
                            @endif
                        </td>
                        @foreach($compareProducts->take(3) as $cp)
                        <td style="padding:0.5rem 0.75rem;text-align:center;border-bottom:1px solid #e5e5e5;">
                            @if($cp->rating > 0)
                            <div style="display:flex;align-items:center;justify-content:center;gap:0.25rem;">
                                <span style="font-weight:600;color:#0F1111;">{{ number_format($cp->rating, 1) }}</span>
                                <svg style="width:0.875rem;height:0.875rem;color:#FFA41C;" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                            @else
                            <span style="color:#565959;">-</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    <!-- Brand -->
                    <tr>
                        <td style="padding:0.5rem 0.75rem;font-weight:600;color:#565959;border-bottom:1px solid #e5e5e5;">Brand</td>
                        <td style="padding:0.5rem 0.75rem;text-align:center;color:#0F1111;border-bottom:1px solid #e5e5e5;background:#f8fcfc;">{{ $product->brand?->name ?? '-' }}</td>
                        @foreach($compareProducts->take(3) as $cp)
                        <td style="padding:0.5rem 0.75rem;text-align:center;color:#0F1111;border-bottom:1px solid #e5e5e5;">{{ $cp->brand?->name ?? '-' }}</td>
                        @endforeach
                    </tr>
                    <!-- Availability -->
                    <tr>
                        <td style="padding:0.5rem 0.75rem;font-weight:600;color:#565959;border-bottom:1px solid #e5e5e5;">Availability</td>
                        <td style="padding:0.5rem 0.75rem;text-align:center;border-bottom:1px solid #e5e5e5;background:#f8fcfc;">
                            <span style="color:{{ $product->isInStock() ? '#007600' : '#cc0c39' }};">{{ $product->isInStock() ? 'In Stock' : 'Out of Stock' }}</span>
                        </td>
                        @foreach($compareProducts->take(3) as $cp)
                        <td style="padding:0.5rem 0.75rem;text-align:center;border-bottom:1px solid #e5e5e5;">
                            <span style="color:{{ $cp->isInStock() ? '#007600' : '#cc0c39' }};">{{ $cp->isInStock() ? 'In Stock' : 'Out of Stock' }}</span>
                        </td>
                        @endforeach
                    </tr>
                    <!-- Add to Cart row -->
                    <tr>
                        <td style="padding:0.75rem;"></td>
                        <td style="padding:0.75rem;text-align:center;background:#f8fcfc;">
                            <button @click="addToCart()" style="padding:0.375rem 1rem;border-radius:9999px;font-size:12px;font-weight:600;cursor:pointer;background:#FFD814;color:#0F1111;border:1px solid #FCD200;">Add to Cart</button>
                        </td>
                        @foreach($compareProducts->take(3) as $cp)
                        <td style="padding:0.75rem;text-align:center;">
                            @if($cp->isInStock())
                            <button @click="$store.cart.add({{ $cp->id }})" style="padding:0.375rem 1rem;border-radius:9999px;font-size:12px;font-weight:600;cursor:pointer;background:#FFD814;color:#0F1111;border:1px solid #FCD200;">Add to Cart</button>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                </table>
            </div>
        </div>
        @endif

        <!-- ===== CUSTOMER REVIEWS ===== -->
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;" id="customer-reviews">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:1rem;">Customer Reviews</h2>

            @if($product->review_count > 0)
            <!-- Rating Summary with Distribution -->
            <div class="flex flex-col sm:flex-row gap-6" style="margin-bottom:1.5rem;">
                <!-- Overall Rating -->
                <div style="text-align:center;min-width:120px;">
                    <div style="font-size:3rem;font-weight:700;color:#0F1111;line-height:1;">{{ number_format($product->rating, 1) }}</div>
                    <div style="display:flex;align-items:center;gap:0.125rem;justify-content:center;margin-top:0.25rem;">
                        @for($s = 1; $s <= 5; $s++)
                        <svg style="width:1.125rem;height:1.125rem;color:{{ $s <= round($product->rating) ? '#FFA41C' : '#ddd' }};" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p style="font-size:13px;color:#565959;margin-top:0.25rem;">{{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}</p>
                </div>

                <!-- Rating Distribution Bars -->
                <div style="flex:1;max-width:20rem;">
                    @for($star = 5; $star >= 1; $star--)
                    @php $pct = $totalReviews > 0 ? round(($ratingDist[$star] / $totalReviews) * 100) : 0; @endphp
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
                        <span style="font-size:13px;color:#007185;white-space:nowrap;width:3rem;">{{ $star }} star</span>
                        <div style="flex:1;height:1.125rem;background:#e5e5e5;border-radius:0.25rem;overflow:hidden;">
                            <div style="height:100%;background:#FFA41C;border-radius:0.25rem;width:{{ $pct }}%;transition:width 0.3s;"></div>
                        </div>
                        <span style="font-size:12px;color:#565959;width:2.5rem;text-align:right;">{{ $pct }}%</span>
                    </div>
                    @endfor
                </div>
            </div>

            <!-- Individual Reviews -->
            <div>
                @foreach($product->reviews as $review)
                <div style="padding-bottom:1rem;margin-bottom:1rem;border-bottom:1px solid #e5e5e5;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">
                        <div style="width:1.75rem;height:1.75rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;background:#6F9CA2;color:#fff;">
                            {{ strtoupper(substr($review->user?->first_name ?? 'A', 0, 1)) }}
                        </div>
                        <span style="font-size:13px;font-weight:500;color:#0F1111;">{{ $review->user?->first_name ?? 'Anonymous' }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.25rem;margin-bottom:0.25rem;">
                        @for($s = 1; $s <= 5; $s++)
                        <svg style="width:0.875rem;height:0.875rem;color:{{ $s <= $review->rating ? '#FFA41C' : '#ddd' }};" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        @if($review->title)
                        <span style="font-size:14px;font-weight:700;color:#0F1111;margin-left:0.25rem;">{{ $review->title }}</span>
                        @endif
                    </div>
                    <p style="font-size:12px;color:#565959;margin-bottom:0.375rem;">Reviewed on {{ $review->created_at->format('d M Y') }}</p>
                    <p style="font-size:14px;color:#333;">{{ $review->review }}</p>
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center;padding:2.5rem 0;">
                <p style="font-size:14px;color:#565959;">No reviews yet. Be the first to review this product!</p>
            </div>
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
        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e5e5e5;">
            <h2 style="font-size:18px;font-weight:700;color:#0F1111;margin-bottom:1rem;">Products related to this item</h2>
            <div style="display:flex;gap:1rem;overflow-x:auto;padding-bottom:1rem;-webkit-overflow-scrolling:touch;">
                @foreach($relatedProducts as $rp)
                <a href="{{ route('product.show', $rp) }}"
                   style="flex-shrink:0;width:180px;border:1px solid #e5e5e5;border-radius:0.5rem;padding:0.75rem;background:#fff;text-decoration:none;transition:box-shadow 0.15s;"
                   onmouseenter="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseleave="this.style.boxShadow='none'">
                    <div style="background:#fafafa;border-radius:0.375rem;overflow:hidden;aspect-ratio:1/1;margin-bottom:0.5rem;">
                        <img src="{{ $rp->primary_image_url }}" alt="{{ $rp->name }}" style="width:100%;height:100%;object-fit:contain;padding:0.25rem;">
                    </div>
                    <p style="font-size:13px;font-weight:500;color:#0F1111;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:0.25rem;line-height:1.3;">{{ $rp->name }}</p>
                    @if($rp->rating > 0)
                    <div style="display:flex;align-items:center;gap:0.25rem;margin-bottom:0.25rem;">
                        @for($s = 1; $s <= 5; $s++)
                        <svg style="width:0.75rem;height:0.75rem;color:{{ $s <= round($rp->rating) ? '#FFA41C' : '#ddd' }};" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        <span style="font-size:10px;color:#565959;">{{ $rp->review_count }}</span>
                    </div>
                    @endif
                    <div style="display:flex;align-items:baseline;gap:0.375rem;">
                        <span style="font-size:14px;font-weight:700;color:#0F1111;">₹{{ number_format($rp->price) }}</span>
                        @if($rp->mrp > $rp->price)
                        <span style="font-size:10px;color:#999;text-decoration:line-through;">₹{{ number_format($rp->mrp) }}</span>
                        <span style="font-size:10px;font-weight:500;color:#CC0C39;">{{ $rp->discount_percentage }}% off</span>
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
                style="padding:0.625rem 1.25rem;border-radius:0.5rem;font-size:13px;font-weight:600;background:#6F9CA2;color:#fff;border:none;cursor:pointer;">
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
