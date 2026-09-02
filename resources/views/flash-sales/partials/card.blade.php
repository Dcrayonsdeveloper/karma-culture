{{--
    A product card with the sale's own terms attached: the discount badge, the
    sale price, and how many units are left at it. The pivot row is set on each
    product by FlashSaleController.
--}}
@php
    $kkSalePrice = $isLive ? $product->flashSalePrice() : null;
    $kkLimit = $product->pivot?->stock_limit;
    $kkSold = (int) ($product->pivot->sold_count ?? 0);
    $kkLeft = $kkLimit !== null ? max(0, (int) $kkLimit - $kkSold) : null;
@endphp

<div class="relative">
    @if($kkSalePrice)
        <span class="absolute top-2 left-2 z-10 text-[10px] font-bold px-2 py-1 rounded-full text-white" style="background:#8C5C34;">
            {{ (int) round((1 - $kkSalePrice / max(0.01, (float) $product->price)) * 100) }}% OFF
        </span>
    @endif

    <x-product-card :product="$product" />

    @if($kkSalePrice)
        <p class="text-xs mt-1" style="color:#8C5C34;">
            <strong>@price($kkSalePrice)</strong>
            <span class="text-neutral-500 line-through ml-1">@price($product->price)</span>
        </p>
        @if($kkLeft !== null)
            <p class="text-[11px] text-neutral-600 mt-0.5">
                {{ $kkLeft > 0 ? $kkLeft . ' left at this price' : 'Sale price sold out' }}
            </p>
        @endif
    @endif
</div>
