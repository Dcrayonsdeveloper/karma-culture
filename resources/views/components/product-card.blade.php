@props(['product', 'showQuickView' => true, 'compact' => false, 'salePrice' => null, 'unitsLeft' => null, 'showWishlist' => null, 'showFavourites' => null])

@php
    $discount = $product->discount_percentage ?? 0;
    $hasDiscount = $product->price < $product->mrp;
    $rating = $product->rating ?? 0;
    $reviewCount = $product->review_count ?? 0;
    $outOfStock = !$product->isInStock();

    // A time-limited price passed in by the page (a flash sale). It replaces
    // the figure the card leads with and strikes the normal price beside it,
    // so a sale tile is the same card as every other tile rather than a card
    // with the sale's terms bolted on underneath it. Guarded the same way
    // Product::flashSalePrice() guards checkout: a misconfigured sale must
    // never raise the price.
    $onSale = $salePrice !== null
        && (float) $salePrice > 0
        && (float) $salePrice < (float) $product->price;
    $cardPrice = $onSale ? (float) $salePrice : (float) $product->price;
    // What the shopper is saving against: the price this one replaces.
    $cardWas = $onSale ? (float) $product->price : (float) $product->mrp;
    $cardCut = $onSale
        ? (int) round((1 - $cardPrice / max(0.01, (float) $product->price)) * 100)
        : (int) round($discount);
    $showWas = $onSale || $hasDiscount;

    // Read hover action settings (cached for 1hr by Setting::get)
    //
    // The caller may insist, and each saved list's own page does: the heart is
    // the only way to take something off /wishlist and the star the only way to
    // take it off /favourites, so a card there without one is a list nobody can
    // empty. Everywhere else the admin setting still decides - and each list
    // insists only on its OWN button, so /wishlist does not override an admin
    // who has turned the star off.
    $showWishlist = $showWishlist ?? \App\Models\Setting::get('product_card_wishlist', true);
    $showFavourites = $showFavourites ?? \App\Models\Setting::get('product_card_favourites', true);
    $showAddToCart = \App\Models\Setting::get('product_card_add_to_cart', true);
    $showQuickViewBtn = $showQuickView && \App\Models\Setting::get('product_card_quick_view', true);
    $hasHoverActions = $showWishlist || $showFavourites || $showQuickViewBtn;

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

    // A product whose main media is a video leads with the clip itself rather
    // than with its poster - the still was all a card could show while the tile
    // was an <img>, and a merchandiser who makes a video the main media is
    // asking for the movement. Everything else is a photograph as before.
    $mainVideo = $product->primary_video;
@endphp

@if($compact)
    {{-- Compact card for horizontal scrollable rows --}}
    <div {{ $attributes->merge(['class' => 'group shrink-0 w-full']) }}>
        <a href="{{ route('product.show', $product) }}" class="block relative">
            {{-- 3:4 portrait, the shape every product tile in a listing is drawn
                 at, so a rail of these lines up with the listing grid it sits
                 under. The shot fills it and the excess is cropped; the
                 whole photo is one tap away on the product page, whose zoom is
                 contain. The placeholder is tried via data-fallback when the
                 URL 404s. --}}
            @if($mainVideo)
                {{-- The poster is the frame the merchandiser chose, so it stands
                     in until the clip has enough of itself to paint. --}}
                <x-media video
                         :src="$mainVideo->display_url"
                         :poster="$mainVideo->display_thumbnail"
                         :alt="$product->name"
                         zoom cover
                         class="aspect-[3/4] bg-neutral-50 rounded-[20px] overflow-hidden mb-2" />
            @else
                <x-media :src="$product->primary_image_url"
                         :alt="$product->name"
                         :fallback="$placeholderImage"
                         zoom cover
                         class="aspect-[3/4] bg-neutral-50 rounded-[20px] overflow-hidden mb-2" />
            @endif
            @if($showWas)
                <span class="absolute top-2 left-2 bg-[#F8931D] text-white font-bold rounded-full text-[8px] w-8 h-8 flex items-center justify-center sm:w-auto sm:h-auto sm:text-[10px] sm:px-2 sm:py-0.5 sm:rounded-md">{{ $cardCut }}%<span class="hidden sm:inline">&nbsp;Off</span></span>
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
            <span class="text-sm font-bold text-[#222]">@price($cardPrice)</span>
            @if($showWas)
                <span class="text-[10px] text-neutral-600 line-through">@price($cardWas)</span>
                <span class="text-[10px] font-semibold text-[#B06D0F]">{{ $cardCut }}% off</span>
            @endif
        </div>

        @if($unitsLeft !== null)
            <p class="text-[10px] font-medium mt-0.5 px-1" style="color:#8C5C34;">
                {{ $unitsLeft > 0 ? $unitsLeft . ' left at this price' : 'Sale price sold out' }}
            </p>
        @endif

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

        {{-- Actions. The CTA and the add-to-cart shortcut share one line: the
             button used to sit on its own row above the bar, which spent a
             whole line of card height on a 40px control. --}}
        @if($showAddToCart)
            <div class="mt-2 px-1 kk-card-actions">
                <a href="{{ route('product.show', $product) }}"
                   class="kk-card-actions__cta py-2.5 text-[12px] font-semibold text-white rounded-md transition-colors duration-200 text-center"
                   style="background:#2D1810;"
                   onmouseover="this.style.background='#1F1109'"
                   onmouseout="this.style.background='#2D1810'">
                    View Product
                </a>
                @unless($outOfStock)
                    @include('partials.quick-add-button', ['product' => $product])
                @endunless
            </div>
        @endif
    </div>
@else
    {{-- Full product card - MudKid style --}}
    <div {{ $attributes->merge(['class' => 'group card-product flex flex-col bg-white rounded-[20px] overflow-hidden']) }}>
        {{-- Image Section --}}
        <div class="relative aspect-[3/4] overflow-hidden bg-neutral-50">
            {{-- 3:4 portrait rather than the square this used to be. Clothing is
                 photographed upright, so a square frame spent a third of every
                 tile on floor and backdrop and showed the garment smaller than
                 the card had room for; the taller frame is also what the rest of
                 the site now draws, so a card looks the same wherever it appears.

                 The shot fills the frame and the excess is cropped, which is what
                 keeps a row of cards reading as one row. Nothing is lost for good:
                 the product page's zoom is contain. The placeholder rides on
                 data-fallback so a broken URL falls back once and then degrades to
                 a designed frame rather than an empty rectangle. --}}
            <a href="{{ route('product.show', $product) }}" class="block h-full">
                @if($mainVideo)
                    {{-- The poster is the frame the merchandiser chose, so it
                         stands in until the clip has enough of itself to
                         paint. --}}
                    <x-media video
                             :src="$mainVideo->display_url"
                             :poster="$mainVideo->display_thumbnail"
                             :alt="$product->name"
                             zoom cover
                             class="h-full" />
                @else
                    <x-media :src="$product->primary_image_url"
                             :alt="$product->name"
                             :fallback="$placeholderImage"
                             zoom cover
                             class="h-full" />
                @endif
            </a>

            {{-- Top-left badges --}}
            <div class="absolute top-2 left-2 sm:top-3 sm:left-3 flex flex-col gap-1">
                @if($showWas)
                    <span class="bg-[#F8931D] text-white font-bold rounded-full text-[8px] w-8 h-8 flex items-center justify-center sm:w-auto sm:h-auto sm:text-[10px] sm:px-2 sm:py-0.5 sm:rounded-md">{{ $cardCut }}%<span class="hidden sm:inline">&nbsp;Off</span></span>
                @endif
            </div>

            {{-- Top-right hover actions (Wishlist, Favourites + Quick View).

                 The column is flex-col, so the star sits directly under the
                 heart and the pair reads as one stack of save actions rather
                 than two unrelated controls. Same size, same chrome, same focus
                 ring - only the icon and the saved colour tell them apart, which
                 is the whole point: they are the same gesture into two lists.

                 aria-label rather than a title, and it names the list, because
                 a screen reader otherwise announces two identical "save"
                 buttons stacked on every tile in the grid. --}}
            @if($hasHoverActions)
                <div class="absolute top-3 right-3 flex flex-col gap-1.5 sm:opacity-0 sm:group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200">
                    @if($showWishlist)
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                class="w-10 h-10 bg-white rounded-full shadow-sm flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-[#6F9CA2] focus:ring-offset-1"
                                :style="$store.wishlist.has({{ $product->id }}) ? 'color: #ef4444;' : 'color: #737373;'"
                                :aria-pressed="$store.wishlist.has({{ $product->id }}) ? 'true' : 'false'"
                                aria-label="Toggle wishlist">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    @endif
                    @if($showFavourites)
                        <button @click="$store.favourites.toggle({{ $product->id }})"
                                class="w-10 h-10 bg-white rounded-full shadow-sm flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-[#6F9CA2] focus:ring-offset-1"
                                :style="$store.favourites.has({{ $product->id }}) ? 'color: #b06d0f;' : 'color: #737373;'"
                                :aria-pressed="$store.favourites.has({{ $product->id }}) ? 'true' : 'false'"
                                aria-label="Toggle favourites">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
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
                 the name starts at the same height on every card, even for
                 products with neither. --}}
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
            <h3 class="text-[13px] font-medium text-[#222] mb-1 leading-snug">
                <a href="{{ route('product.show', $product) }}" class="line-clamp-2 hover:text-[#6F9CA2] transition-colors">
                    {{ $product->name }}
                </a>
            </h3>

            {{-- Price Row (directly after the name so prices align) --}}
            <div class="flex flex-wrap items-baseline gap-1.5 mb-1.5">
                <span class="text-sm font-bold text-[#222]">@price($cardPrice)</span>
                @if($showWas)
                    <span class="text-[11px] text-neutral-600 line-through">@price($cardWas)</span>
                    <span class="text-[11px] font-semibold text-[#B06D0F]">{{ $cardCut }}% off</span>
                @endif
            </div>

            {{-- How much of the sale allocation is left, in the sale's own
                 colour. Sits with the price it qualifies rather than below the
                 card, where it used to read as a caption for the next tile. --}}
            @if($unitsLeft !== null)
                <p class="text-[11px] font-medium mb-1.5" style="color:#8C5C34;">
                    {{ $unitsLeft > 0 ? $unitsLeft . ' left at this price' : 'Sale price sold out' }}
                </p>
            @endif

            {{-- Rating Badge --}}
            @if($rating > 0)
                <div class="flex items-center gap-1 mb-1">
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
                <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
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

            {{-- Actions, on one line. mt-auto keeps the strip pinned to the
                 bottom of the card so it lines up across a row whatever the
                 name above it wraps to. --}}
            @if($showAddToCart)
                <div class="mt-auto pt-2 kk-card-actions">
                    <a href="{{ route('product.show', $product) }}"
                       class="kk-card-actions__cta py-2.5 text-[13px] font-semibold text-white rounded-md transition-colors duration-200 text-center"
                       style="background:#2D1810;"
                       onmouseover="this.style.background='#1F1109'"
                       onmouseout="this.style.background='#2D1810'">
                        View Product
                    </a>
                    @unless($outOfStock)
                        @include('partials.quick-add-button', ['product' => $product])
                    @endunless
                </div>
            @endif
        </div>
    </div>
@endif
