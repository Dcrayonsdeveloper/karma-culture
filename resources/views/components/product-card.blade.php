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
        1 => asset_v('images/placeholder-girls.svg'),
        8 => asset_v('images/placeholder-boys.svg'),
        15 => asset_v('images/placeholder-baby.svg'),
        default => asset_v('images/placeholder-boys.svg'),
    };
@endphp

@if($compact)
    {{-- Compact card for horizontal scrollable rows --}}
    <div {{ $attributes->merge(['class' => 'group shrink-0 w-full']) }}>
        <a href="{{ route('product.show', $product) }}" class="block relative">
            {{-- Same square tile as before, but the shot is contained over a
                 blurred copy of itself so nothing gets cropped away, and the
                 placeholder is tried via data-fallback when the URL 404s. --}}
            <x-media :src="$product->primary_image_url"
                     :alt="$product->name"
                     :fallback="$placeholderImage"
                     zoom
                     class="aspect-square bg-neutral-50 rounded-[20px] overflow-hidden mb-2" />
            @if($hasDiscount)
                <span class="absolute top-2 left-2 bg-[#F8931D] text-white font-bold rounded-full text-[8px] w-8 h-8 flex items-center justify-center sm:w-auto sm:h-auto sm:text-[10px] sm:px-2 sm:py-0.5 sm:rounded-md">{{ round($discount) }}%<span class="hidden sm:inline">&nbsp;Off</span></span>
            @endif
        </a>

        <a href="{{ route('product.show', $product) }}" class="block px-1">
            {{-- Eyebrow: brand first, category as fallback - always rendered so
                 names and prices align across the row. --}}
            @if($product->brand)
                <p class="text-[10px] text-kk-text uppercase tracking-wide mb-0.5 leading-[15px] min-h-[15px] truncate">{{ $product->brand->name }}</p>
            @elseif($product->category)
                <p class="text-[10px] text-kk-text uppercase tracking-wide mb-0.5 leading-[15px] min-h-[15px] truncate">{{ $product->category->name }}</p>
            @else
                <p class="text-[10px] text-kk-text uppercase tracking-wide mb-0.5 leading-[15px] min-h-[15px]" aria-hidden="true">&nbsp;</p>
            @endif
            <h3 class="text-xs text-[#222] line-clamp-1 mb-1 group-hover:text-[#6F9CA2] leading-snug font-medium">
                {{ $product->name }}
            </h3>
        </a>

        <div class="flex items-baseline gap-1 flex-wrap px-1">
            <span class="text-sm font-bold text-[#222]">@price($product->price)</span>
            @if($hasDiscount)
                <span class="text-[10px] text-neutral-600 line-through">@price($product->mrp)</span>
                <span class="text-[10px] font-semibold text-[#B06D0F]">{{ round($discount) }}% off</span>
            @endif
        </div>

        @if($rating > 0)
            <div class="flex items-center gap-1 mt-1 px-1">
                <span class="inline-flex items-center gap-0.5 bg-[#C1539C] text-white text-[10px] font-bold px-1 py-0.5 rounded-sm">
                    {{ number_format($rating, 1) }}
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                </span>
                <span class="text-[10px] text-neutral-600">({{ $reviewCount }})</span>
            </div>
        @endif

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
            <div class="flex flex-wrap gap-1 mt-1 px-1">
                @foreach($product->attributes as $name => $value)
                    {{-- Skip structured values such as the colour list; the card shows simple tags only. --}}
                    @continue(is_array($value) || is_object($value))
                    <span class="text-[9px] text-neutral-600 bg-neutral-50 border border-neutral-100 rounded-full px-1.5 py-0.5 inline-flex items-center gap-0.5">
                        @if(isset($colorMap[$name][$value]))
                            <span class="w-2.5 h-2.5 rounded-full border border-neutral-200 shrink-0" style="background-color: {{ $colorMap[$name][$value] }}"></span>
                        @endif
                        {{ Str::limit($value, 12) }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- View Product --}}
        @if($showAddToCart)
            <div class="mt-2 px-1">
                <a href="{{ route('product.show', $product) }}"
                   class="block w-full py-2.5 text-[12px] font-semibold text-white rounded-md transition-colors duration-200 text-center"
                   style="background:#2D1810;"
                   onmouseover="this.style.background='#1F1109'"
                   onmouseout="this.style.background='#2D1810'">
                    View Product
                </a>
            </div>
        @endif
    </div>
@else
    {{-- Full product card - MudKid style --}}
    <div {{ $attributes->merge(['class' => 'group card-product flex flex-col bg-white rounded-[20px] overflow-hidden']) }}>
        {{-- Image Section --}}
        <div class="relative aspect-square overflow-hidden bg-neutral-50">
            {{-- The square frame is kept so every card in a row stays the same
                 height, but the shot inside it is contained over a blurred copy
                 of itself - an off-ratio product photo is shown whole instead of
                 having its edges cut off. The placeholder rides on data-fallback
                 so a broken URL falls back once and then degrades to a designed
                 frame rather than an empty rectangle. --}}
            <a href="{{ route('product.show', $product) }}" class="block h-full">
                <x-media :src="$product->primary_image_url"
                         :alt="$product->name"
                         :fallback="$placeholderImage"
                         zoom
                         class="h-full" />
            </a>

            {{-- Top-left badges --}}
            <div class="absolute top-2 left-2 sm:top-3 sm:left-3 flex flex-col gap-1">
                @if($hasDiscount)
                    <span class="bg-[#F8931D] text-white font-bold rounded-full text-[8px] w-8 h-8 flex items-center justify-center sm:w-auto sm:h-auto sm:text-[10px] sm:px-2 sm:py-0.5 sm:rounded-md">{{ round($discount) }}%<span class="hidden sm:inline">&nbsp;Off</span></span>
                @endif
            </div>

            {{-- Top-right hover actions (Wishlist + Quick View) --}}
            @if($hasHoverActions)
                <div class="absolute top-3 right-3 flex flex-col gap-1.5 sm:opacity-0 sm:group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200">
                    @if($showWishlist)
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                class="w-10 h-10 bg-white rounded-full shadow-sm flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-[#6F9CA2] focus:ring-offset-1"
                                :style="$store.wishlist.has({{ $product->id }}) ? 'color: #ef4444;' : 'color: #737373;'"
                                aria-label="Toggle wishlist">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Out of stock overlay --}}
            @if($outOfStock)
                <div class="absolute inset-0 bg-white/70 flex items-center justify-center">
                    <span class="text-xs font-semibold text-neutral-600 bg-white px-3 py-1 rounded-full shadow-sm">Out of Stock</span>
                </div>
            @endif
        </div>

        {{-- Content Section --}}
        <div class="p-3 flex flex-col flex-1">
            {{-- Eyebrow: brand first, category as fallback. Always rendered so
                 the name and price sit at the same height on every card, even
                 for products with neither. --}}
            @if($product->brand)
                <p class="text-[10px] text-kk-text uppercase tracking-wider mb-0.5 leading-[15px] min-h-[15px] truncate">{{ $product->brand->name }}</p>
            @elseif($product->category)
                <a href="{{ route('category.show', $product->category) }}" class="text-[10px] text-kk-text uppercase tracking-wider mb-0.5 leading-[15px] min-h-[15px] truncate block hover:text-[#6F9CA2]">
                    {{ $product->category->name }}
                </a>
            @else
                <p class="text-[10px] text-kk-text uppercase tracking-wider mb-0.5 leading-[15px] min-h-[15px]" aria-hidden="true">&nbsp;</p>
            @endif

            {{-- Product Name --}}
            <h3 class="text-[13px] font-medium text-[#222] mb-1.5 leading-snug min-h-9">
                <a href="{{ route('product.show', $product) }}" class="line-clamp-2 hover:text-[#6F9CA2] transition-colors">
                    {{ $product->name }}
                </a>
            </h3>

            {{-- Price Row (directly after the name so prices align) --}}
            <div class="flex items-baseline gap-1.5 mb-2.5">
                <span class="text-sm font-bold text-[#222]">@price($product->price)</span>
                @if($hasDiscount)
                    <span class="text-[11px] text-neutral-600 line-through">@price($product->mrp)</span>
                    <span class="text-[11px] font-semibold text-[#B06D0F]">{{ round($discount) }}% off</span>
                @endif
            </div>

            {{-- Rating Badge --}}
            @if($rating > 0)
                <div class="flex items-center gap-1 mb-1.5">
                    <span class="inline-flex items-center gap-0.5 bg-[#C1539C] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-sm">
                        {{ number_format($rating, 1) }}
                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </span>
                    <span class="text-[10px] text-neutral-600">({{ $reviewCount }})</span>
                </div>
            @endif

            {{-- Quick Attributes (Size, Shade, etc.) --}}
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
                        // Skip structured values such as the colour list.
                        if (is_array($value) || is_object($value)) { continue; }
                        if (isset($colorMap[$name][$value])) {
                            $colorAttrs[] = ['name' => $name, 'value' => $value, 'code' => $colorMap[$name][$value]];
                        } else {
                            $textAttrs[] = ['name' => $name, 'value' => $value];
                        }
                    }
                @endphp
                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                    @foreach($colorAttrs as $ca)
                        <span class="inline-flex items-center gap-1 text-[10px] text-neutral-600 bg-neutral-50 border border-neutral-100 rounded-full px-1.5 py-0.5">
                            <span class="w-3 h-3 rounded-full border border-neutral-200 shrink-0" style="background-color: {{ $ca['code'] }}"></span>
                            {{ $ca['value'] }}
                        </span>
                    @endforeach
                    @foreach(array_slice($textAttrs, 0, 3) as $ta)
                        <span class="text-[10px] text-neutral-600 bg-neutral-50 border border-neutral-100 rounded-full px-1.5 py-0.5">{{ $ta['value'] }}</span>
                    @endforeach
                </div>
            @endif

            {{-- View Product --}}
            @if($showAddToCart)
                <div class="mt-auto pt-1">
                    <a href="{{ route('product.show', $product) }}"
                       class="block w-full py-2.5 text-[13px] font-semibold text-white rounded-md transition-colors duration-200 text-center"
                       style="background:#2D1810;"
                       onmouseover="this.style.background='#1F1109'"
                       onmouseout="this.style.background='#2D1810'">
                        View Product
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif
