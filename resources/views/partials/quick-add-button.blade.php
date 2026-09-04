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
        {{-- A trolley rather than the bag-with-a-plus this used to draw. At
             19px the plus inside the bag was three strokes in the space of
             four pixels and read as smudge; the trolley carries the meaning
             in its silhouette instead, which survives being small.

             Drawn on the same 24x24 grid at the same 1.8 weight with the
             same round caps as every other icon in the store, so it sits
             beside them without looking borrowed. --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2.8 3.6h2.1l2.4 10.7a1.7 1.7 0 0 0 1.66 1.32h7.9a1.7 1.7 0 0 0 1.66-1.3L20.3 7.6H5.9"/>
            <circle cx="9.4" cy="20" r="1.35"/>
            <circle cx="17.4" cy="20" r="1.35"/>
        </svg>
    </button>
</div>
