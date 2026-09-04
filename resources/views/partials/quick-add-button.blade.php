{{-- The round add-to-cart button that sits beside a card's "View Product" bar.

     Icon only and circular on purpose: it shares the strip with a full-width
     lettered CTA, and a second lettered bar there read as two competing primary
     actions instead of one CTA with a shortcut beside it. The name still
     reaches a screen reader through aria-label, which names the product too -
     a listing has 24 of these and "Add to cart" alone identifies none of them.

     It opens the quick-add popup rather than posting: nearly everything here is
     sold in a size and a colour, and a cart line with neither cannot be packed.

     Callers are expected to leave this out for a sold-out product - the popup
     would open on a disabled button. --}}
@php /** @var \App\Models\Product $product */ @endphp
<div class="kk-quickadd-row">
    <button type="button"
            class="kk-quickadd"
            @click.prevent.stop="$store.quickAdd.show({{ $product->id }})"
            aria-label="Add {{ $product->name }} to cart"
            title="Add to cart">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5.5 8h13l-1.1 11.2a1.5 1.5 0 0 1-1.5 1.3H8.1a1.5 1.5 0 0 1-1.5-1.3z"/>
            <path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>
            <path d="M12 12.2v4"/>
            <path d="M10 14.2h4"/>
        </svg>
    </button>
</div>
