@props(['product', 'showQuickView' => true, 'compact' => false])

@php
    $discount = $product->discount_percentage ?? 0;
    $hasDiscount = $product->price < $product->mrp;
    $rating = $product->rating ?? 0;
    $reviewCount = $product->review_count ?? 0;
    $outOfStock = !$product->isInStock();

    // Read hover action settings (cached for 1hr by Setting::get)
    $showWishlist = \App\Models\Setting::get('product_card_wishlist', true);
    $showAddToCart = \App\Models\Setting::get('product_card_add_to_cart', true);
    $showQuickViewBtn = $showQuickView && \App\Models\Setting::get('product_card_quick_view', true);
    $hasHoverActions = $showWishlist || $showQuickViewBtn;

    // Category-aware placeholder image
    $rootCatId = null;
    if ($product->category) {
        $rootCatId = $product->category->parent_id ?? $product->category->id;
    }
    $placeholderImage = match($rootCatId) {
        1 => asset('images/placeholder-girls.svg'),
        8 => asset('images/placeholder-boys.svg'),
        15 => asset('images/placeholder-baby.svg'),
        default => asset('images/placeholder-boys.svg'),
    };
@endphp

@if($compact)
    {{-- Compact card for horizontal scrollable rows --}}
    <div {{ $attributes->merge(['class' => 'group shrink-0 w-full']) }}>
        <a href="{{ route('product.show', $product) }}" class="block relative">
            <div class="aspect-[3/4] bg-[#F2E4D2] overflow-hidden mb-3 rounded-2xl">
                <img src="{{ $product->primary_image_url }}"
                     alt="{{ $product->name }}"
                     class="w-full h-full object-cover transition-transform duration-500"
                     style="will-change:transform;"
                     onmouseenter="this.style.transform='scale(1.06)'"
                     onmouseleave="this.style.transform='scale(1)'"
                     loading="lazy"
                     onerror="this.src='{{ $placeholderImage }}'">
            </div>
            @if($hasDiscount)
                <span class="absolute top-2 left-2 bg-[#3E2A1F] text-white font-semibold text-[10px] px-2 py-0.5 tracking-wide">{{ round($discount) }}% OFF</span>
            @endif
        </a>

        <a href="{{ route('product.show', $product) }}" class="block">
            @if($product->brand)
                <p class="text-[10px] text-[#3E2A1F] uppercase tracking-[0.1em] mb-0.5 font-medium">{{ $product->brand->name }}</p>
            @endif
            <h3 class="text-xs text-[#212121] line-clamp-1 mb-1 group-hover:text-[#3E2A1F] leading-snug font-medium transition-colors">
                {{ $product->name }}
            </h3>
        </a>

        @if($rating > 0)
            <div class="flex items-center gap-1 mb-1">
                <span class="inline-flex items-center gap-0.5 text-[#3E2A1F] text-[10px] font-semibold">
                    {{ number_format($rating, 1) }}
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                </span>
                <span class="text-[10px] text-neutral-400">({{ $reviewCount }})</span>
            </div>
        @endif

        <div class="flex items-baseline gap-1.5 flex-wrap">
            <span class="text-sm font-bold text-[#212121]">@price($product->price)</span>
            @if($hasDiscount)
                <span class="text-[10px] text-neutral-400 line-through">@price($product->mrp)</span>
            @endif
        </div>

        {{-- Quick Attributes --}}
        @if(is_array($product->attributes) && count($product->attributes))
            @php
                $colorMap = cache()->remember('attr_color_map', 3600, function() {
                    $map = [];
                    foreach (\App\Models\Attribute::where('type', 'color')->with('values')->get() as $attr) {
                        foreach ($attr->values as $val) {
                            if ($val->color_code) $map[$attr->name][$val->value] = $val->color_code;
                        }
                    }
                    return $map;
                });
            @endphp
            <div class="flex flex-wrap gap-1 mt-1">
                @foreach($product->attributes as $name => $value)
                    <span class="text-[9px] text-neutral-700 bg-[#F2E4D2] border border-[#e8e3da] px-1.5 py-0.5 inline-flex items-center gap-0.5">
                        @if(isset($colorMap[$name][$value]))
                            <span class="w-2.5 h-2.5 border border-neutral-200 shrink-0" style="background-color: {{ $colorMap[$name][$value] }}"></span>
                        @endif
                        {{ Str::limit($value, 12) }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Add to Cart --}}
        @if($showAddToCart)
            <div class="mt-3">
                @unless($outOfStock)
                    <button @click="$store.cart.add({{ $product->id }})"
                            class="w-full py-3 text-sm font-semibold text-white tracking-wide transition-colors duration-200 bg-[#3E2A1F] hover:bg-[#212121] rounded-full">
                        Add to Cart
                    </button>
                @else
                    <button @click="$dispatch('notify-stock', { productId: {{ $product->id }} })"
                            class="w-full py-3 text-sm font-medium text-neutral-500 bg-neutral-200 tracking-wide rounded-full cursor-not-allowed">
                        Out of Stock
                    </button>
                @endunless
            </div>
        @endif
    </div>
@else
    {{-- Full product card - Luxury style --}}
    <div {{ $attributes->merge(['class' => 'group flex flex-col bg-[#FAF5EF] overflow-hidden rounded-2xl']) }}>
        {{-- Image Section --}}
        <div class="relative aspect-[3/4] overflow-hidden bg-[#F2E4D2] rounded-2xl">
            <a href="{{ route('product.show', $product) }}">
                <img src="{{ $product->primary_image_url }}"
                     alt="{{ $product->name }}"
                     class="w-full h-full object-cover transition-transform duration-500"
                     style="will-change:transform;"
                     onmouseenter="this.style.transform='scale(1.06)'"
                     onmouseleave="this.style.transform='scale(1)'"
                     loading="lazy"
                     onerror="this.src='{{ $placeholderImage }}'">
            </a>

            {{-- Top-left badges --}}
            <div class="absolute top-3 left-3 flex flex-col gap-1">
                @if($hasDiscount)
                    <span class="bg-[#3E2A1F] text-white font-semibold text-[10px] px-2 py-0.5 tracking-wide">{{ round($discount) }}% OFF</span>
                @endif
            </div>

            {{-- Top-right hover actions --}}
            @if($hasHoverActions)
                <div class="absolute top-3 right-3 flex flex-col gap-1.5 sm:opacity-0 sm:group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200">
                    @if($showWishlist)
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                class="w-9 h-9 bg-white/90 backdrop-blur-sm flex items-center justify-center transition-colors focus:outline-none"
                                :style="$store.wishlist.has({{ $product->id }}) ? 'color: #ef4444;' : 'color: #595959;'"
                                aria-label="Toggle wishlist">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    @endif
                    @if($showQuickViewBtn)
                        <button @click="$dispatch('quick-view', { productId: {{ $product->id }} })"
                                class="w-9 h-9 bg-white/90 backdrop-blur-sm flex items-center justify-center text-neutral-700 hover:text-[#3E2A1F] transition-colors focus:outline-none"
                                aria-label="Quick view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Out of stock overlay --}}
            @if($outOfStock)
                <div class="absolute inset-0 bg-white/70 flex items-center justify-center">
                    <span class="text-xs font-semibold text-neutral-600 bg-white px-3 py-1">Out of Stock</span>
                </div>
            @endif
        </div>

        {{-- Content Section --}}
        <div class="py-3 px-1 flex flex-col flex-1">
            {{-- Brand --}}
            @if($product->brand)
                <p class="text-[10px] text-[#3E2A1F] uppercase tracking-[0.1em] mb-0.5 font-medium">{{ $product->brand->name }}</p>
            @elseif($product->category)
                <a href="{{ route('category.show', $product->category) }}" class="text-[10px] text-[#3E2A1F] uppercase tracking-[0.1em] mb-0.5 block hover:text-[#5E3A26] font-medium">
                    {{ $product->category->name }}
                </a>
            @endif

            {{-- Product Name --}}
            <h3 class="text-[13px] font-medium text-[#212121] mb-1.5 leading-snug min-h-9">
                <a href="{{ route('product.show', $product) }}" class="line-clamp-2 hover:text-[#3E2A1F] transition-colors">
                    {{ $product->name }}
                </a>
            </h3>

            {{-- Rating --}}
            @if($rating > 0)
                <div class="flex items-center gap-1 mb-1.5">
                    <span class="inline-flex items-center gap-0.5 text-[#3E2A1F] text-[10px] font-semibold">
                        {{ number_format($rating, 1) }}
                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </span>
                    <span class="text-[10px] text-neutral-400">({{ $reviewCount }})</span>
                </div>
            @endif

            {{-- Price Row --}}
            <div class="flex items-baseline gap-1.5 mb-2.5">
                <span class="text-sm font-bold text-[#212121]">@price($product->price)</span>
                @if($hasDiscount)
                    <span class="text-[11px] text-neutral-400 line-through">@price($product->mrp)</span>
                    <span class="text-[11px] font-semibold text-[#3E2A1F]">{{ round($discount) }}% off</span>
                @endif
            </div>

            {{-- Quick Attributes --}}
            @if(is_array($product->attributes) && count($product->attributes))
                @php
                    $colorMap = cache()->remember('attr_color_map', 3600, function() {
                        $map = [];
                        foreach (\App\Models\Attribute::where('type', 'color')->with('values')->get() as $attr) {
                            foreach ($attr->values as $val) {
                                if ($val->color_code) $map[$attr->name][$val->value] = $val->color_code;
                            }
                        }
                        return $map;
                    });
                    $colorAttrs = [];
                    $textAttrs = [];
                    foreach ($product->attributes as $name => $value) {
                        if (isset($colorMap[$name][$value])) {
                            $colorAttrs[] = ['name' => $name, 'value' => $value, 'code' => $colorMap[$name][$value]];
                        } else {
                            $textAttrs[] = ['name' => $name, 'value' => $value];
                        }
                    }
                @endphp
                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                    @foreach($colorAttrs as $ca)
                        <span class="inline-flex items-center gap-1 text-[10px] text-neutral-700 bg-[#F2E4D2] border border-[#e8e3da] px-1.5 py-0.5">
                            <span class="w-3 h-3 border border-neutral-200 shrink-0" style="background-color: {{ $ca['code'] }}"></span>
                            {{ $ca['value'] }}
                        </span>
                    @endforeach
                    @foreach(array_slice($textAttrs, 0, 3) as $ta)
                        <span class="text-[10px] text-neutral-700 bg-[#F2E4D2] border border-[#e8e3da] px-1.5 py-0.5">{{ $ta['value'] }}</span>
                    @endforeach
                </div>
            @endif

            {{-- Add to Cart / Notify --}}
            @if($showAddToCart)
                <div class="mt-auto pt-2">
                    @unless($outOfStock)
                        <button @click="$store.cart.add({{ $product->id }})"
                                class="w-full py-3 text-sm font-semibold text-white tracking-wide transition-colors duration-200 bg-[#3E2A1F] hover:bg-[#212121] rounded-full">
                            Add to Cart
                        </button>
                    @else
                        <button @click="$dispatch('notify-stock', { productId: {{ $product->id }} })"
                                class="w-full py-3 text-sm font-medium text-neutral-500 bg-neutral-200 tracking-wide rounded-full cursor-not-allowed">
                            Out of Stock
                        </button>
                    @endunless
                </div>
            @endif
        </div>
    </div>
@endif
