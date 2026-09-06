{{--
    The same product card every other listing draws, handed the sale's terms:
    the discounted price it should lead with and how many units are left at it.

    It used to wrap the card in a div, stamp a second discount badge over the
    photo and hang the sale price and stock line underneath as loose text, so a
    sale tile looked nothing like a tile on the shop or home page. The card
    renders both itself now.

    The pivot row is attached to each product by FlashSaleController, so the
    figures are read from it directly rather than through
    Product::flashSalePrice(), which re-queries the sale once per card.
--}}
@php
    $kkPivot = $product->pivot ?? null;
    $kkOnSale = ($isLive ?? false) && $kkPivot?->sale_price !== null;

    $kkLimit = $kkPivot?->stock_limit;
    $kkSold = (int) ($kkPivot->sold_count ?? 0);
    // Null means an unlimited allocation - there is no count to promise.
    $kkLeft = $kkLimit !== null ? max(0, (int) $kkLimit - $kkSold) : null;

    // A limit that has been reached ends the discount for later buyers, the
    // same rule Product::flashSalePrice() applies when the order is placed.
    // The card still says so, so a sold-out tile explains itself instead of
    // quietly showing the normal price.
    $kkSalePrice = ($kkOnSale && $kkLeft !== 0) ? (float) $kkPivot->sale_price : null;
@endphp

<x-product-card :product="$product"
                :sale-price="$kkSalePrice"
                :units-left="$kkOnSale ? $kkLeft : null" />
