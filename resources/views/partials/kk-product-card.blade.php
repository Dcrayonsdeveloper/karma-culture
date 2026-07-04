@php
    /** @var \App\Models\Product $product */
    $tag = $tag ?? null;
    $hasDiscount = isset($product->mrp) && $product->price < $product->mrp;
    $discount = (int) ($product->discount_percentage ?? 0);
    $brandName = optional($product->brand)->name;

    // Show the product's actual admin-uploaded image; fall back to placeholder
    // only when the product has none.
    $img = $product->primary_image_url ?? asset('images/placeholder-boys.svg');
@endphp

<div class="kk-product">
    <a href="{{ route('product.show', $product) }}" class="kk-product__media block">
        <img src="{{ $img }}" alt="{{ $product->name }}" loading="lazy"
             onerror="this.src='{{ asset('images/placeholder-boys.svg') }}'">
        @if($tag)<span class="kk-product__tag">{{ Str::upper($tag) }}</span>@endif
        @if($hasDiscount && $discount > 0)
            <span class="kk-product__discount">{{ $discount }}% OFF</span>
        @endif
    </a>
    <div class="kk-product__body">
        @if($brandName)
            <span class="kk-product__label">{{ $brandName }}</span>
        @endif
        <a href="{{ route('product.show', $product) }}" style="color:inherit; text-decoration:none;">
            <h3 class="kk-product__name">{{ $product->name }}</h3>
        </a>
        <div class="kk-product__price">
            @if($hasDiscount)<del>@price($product->mrp)</del>@endif
            @price($product->price)
        </div>
        <div class="kk-product__cta">
            <a href="{{ route('product.show', $product) }}" class="kk-btn-brown"
               style="width:100%; border-radius:6px;">View Product</a>
        </div>
    </div>
</div>
