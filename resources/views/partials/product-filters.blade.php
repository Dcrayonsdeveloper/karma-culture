{{--
    The storefront filter sidebar - one copy, used by every listing page.

    Shop and category each carried their own near-identical copy of this and had
    drifted (only one offered Rating), search had a third cut-down version, and
    the brand, deals, flash-sale, new-arrivals and bestsellers pages had none at
    all. Everything now renders from the $filterPanel that App\Support\ProductFilters
    builds, so a shopper sees the same controls wherever they land.

    A section with nothing in it renders nothing: a brand page has no brand list
    to offer, a shop with one size has no size row worth showing.

    Sections are <details>, not an Alpine x-show/x-collapse pair. Closed is the
    resting state now, and an Alpine collapse cannot start closed without either
    flashing eight expanded sections while the bundle loads or hiding every
    control behind an x-cloak that never lifts when the script fails. A <details>
    is shut before a line of JS runs, opens from the keyboard for free, and keeps
    its inputs in the form while folded - so Apply still submits a filter whose
    section is closed.
--}}
@php
    $kkValues = $filterPanel['values'];
    $kkActiveSubs = $filterPanel['active_subcategories'] ?? [];

    // Closed is the default. A section already holding a choice opens itself: a
    // filter the shopper cannot see is one they cannot undo, and the only other
    // way off it is the chip row above the grid, which is out of sight the
    // moment the page is scrolled.
    //
    // Read from values[] - what the request carried - and not from
    // active_subcategories, which also holds the slug a category sub-page ticks
    // on the shopper's behalf. Opening on that would leave the section standing
    // open on every sub-category page for a filter nobody set. The badge on the
    // Filters button and the chip row already draw the line in the same place.
    $kkOpen = [
        'category' => $kkValues['category'] !== null,
        'subcategory' => (bool) $kkValues['subcategory'],
        'size' => (bool) $kkValues['size'],
        'colour' => (bool) $kkValues['colour'],
        'texture' => (bool) $kkValues['texture'],
        'brand' => (bool) $kkValues['brand'],
        'price' => $kkValues['min_price'] !== null || $kkValues['max_price'] !== null,
        'rating' => $kkValues['rating'] !== null,
        'availability' => (bool) $kkValues['in_stock'] || (bool) $kkValues['on_sale'],
    ];
@endphp

@once
    <style>
        /* One hairline between neighbouring sections, drawn by the section
           itself. The dividers used to be eight standalone divs in a space-y-4
           stack, so every 1px line cost 2rem of gap on top of the rows' own
           padding - the panel was mostly empty space.

           The tint is translucent black rather than a fixed neutral-100. This
           panel renders on white here and on the cream page background
           elsewhere, and #f5f5f5 is lighter than cream: the line read as a
           white scratch across the sidebar. A tint of whatever sits behind it
           cannot come out lighter than its own backdrop. */
        .kk-filter-section + .kk-filter-section { border-top: 1px solid rgb(0 0 0 / 0.08); }

        .kk-filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .6875rem 0;
            cursor: pointer;
            user-select: none;
            font-size: .875rem;
            font-weight: 600;
            line-height: 1.25rem;
            color: #171717;
            /* The native disclosure triangle. This row draws its own chevron on
               the other side, and two markers on one line read as a mistake. */
            list-style: none;
        }
        .kk-filter-head::-webkit-details-marker { display: none; }
        .kk-filter-head:hover { color: #000; }
        .kk-filter-head:focus-visible { outline: 2px solid #6F9CA2; outline-offset: 2px; border-radius: .25rem; }

        .kk-filter-chevron { width: 1rem; height: 1rem; flex: none; color: #737373; transition: transform .2s ease; }
        .kk-filter-section[open] > .kk-filter-head .kk-filter-chevron { transform: rotate(180deg); }

        .kk-filter-body { padding-bottom: .75rem; }
        .kk-filter-section[open] > .kk-filter-body { animation: kk-filter-reveal .15s ease-out; }
        @keyframes kk-filter-reveal {
            from { opacity: 0; transform: translateY(-.25rem); }
            to   { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .kk-filter-chevron { transition: none; }
            .kk-filter-section[open] > .kk-filter-body { animation: none; }
        }
    </style>
@endonce

{{-- x-data, so the debounced auto-submit on the radios below has a scope of its
     own. It used to borrow one from whichever section wrapped it; those wrappers
     are gone, and the fragment this same file serves to the header's Filters
     drawer is injected on its own, with no Alpine ancestor guaranteed.

     It carries one piece of state: whether the two price boxes are the wrong way
     round. That has to live at form level rather than beside the boxes, because
     the guard below is the other half of the message - a range nothing can match
     should not reach the grid at all - and `submit` only fires on the form.
     Seeded from the server so a link that arrives backwards is already blocked
     before the shopper has touched anything.

     The guard stops Apply, not the auto-submitting ticks: a shopper who fixes a
     size while the price boxes are wrong is doing the thing that will most
     likely surface the message, and freezing the whole sidebar over one bad pair
     would be a worse trap than the empty grid it prevents. --}}
<form action="{{ $filterPanel['action'] }}" method="GET"
      x-data="{ priceError: @js($kkValues['price_error']) }"
      @submit="priceError && $event.preventDefault()">
    {{-- Anything the page needs carried through a filter submit: the search
         term, a sale scope, and the chosen ordering. --}}
    @foreach($filterPanel['hidden'] ?? [] as $kkName => $kkValue)
        <input type="hidden" name="{{ $kkName }}" value="{{ $kkValue }}">
    @endforeach
    {{-- Always emitted, never gated on "is it the default".
         The guard used to be sort !== 'newest', which is only the default on
         pages that have no default_sort of their own. On /deals, /bestsellers and
         /new-arrivals the constructor falls back to discount/bestselling/newest
         when the request carries no ?sort, so a shopper who had deliberately
         chosen Newest submitted a form that said nothing about ordering - and
         the page handed them discount order back. An explicit sort=newest on
         /shop is a no-op, so carrying it always costs nothing. --}}
    <input type="hidden" name="sort" value="{{ $kkValues['sort'] }}">

    {{-- Categories --}}
    @if($filterPanel['categories']->isNotEmpty())
        <details class="kk-filter-section" {{ $kkOpen['category'] ? 'open' : '' }}>
            <summary class="kk-filter-head">
                Categories
                <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="kk-filter-body">
                <div class="space-y-1.5 max-h-52 overflow-y-auto">
                    @foreach($filterPanel['categories'] as $kkCat)
                        <label class="flex items-center gap-2.5 cursor-pointer group py-0.5 min-h-10 lg:min-h-0">
                            <input type="radio" name="category" value="{{ $kkCat->slug }}"
                                   {{ $kkValues['category'] === $kkCat->slug ? 'checked' : '' }}
                                   @change.debounce.350ms="$el.form.submit()"
                                   class="w-3.5 h-3.5 border-neutral-300 accent-[#6F9CA2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#6F9CA2]">
                            <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">{{ $kkCat->name }}</span>
                            @isset($kkCat->products_total)
                                {{-- What this category would return under the shopper's
                                     other filters, so the list is not a guess. --}}
                                <span class="ml-auto text-xs text-neutral-400 tabular-nums">{{ $kkCat->products_total }}</span>
                            @endisset
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif

    {{-- Sub-categories --}}
    @if($filterPanel['subcategories']->isNotEmpty())
        <details class="kk-filter-section" {{ $kkOpen['subcategory'] ? 'open' : '' }}>
            <summary class="kk-filter-head">
                Sub-categories
                <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="kk-filter-body">
                <div class="space-y-1.5 max-h-52 overflow-y-auto">
                    @foreach($filterPanel['subcategories'] as $kkSub)
                        {{-- A ticked box stays clickable even when the other filters have
                             emptied it out, or there would be no way to untick it. --}}
                        @php
                            $kkEmpty = ($kkSub->products_total ?? null) === 0 && ! in_array($kkSub->slug, $kkActiveSubs, true);
                            // The collection this page IS. The category page ticks it on
                            // the shopper's behalf and re-ticks it on every submit, so as
                            // a live checkbox it swallowed clicks and never changed -
                            // unticking it just reloaded the same page with it ticked
                            // again. Shown as settled instead: it is where they are
                            // standing, and the way out is the parent category, not this
                            // box. Disabled means the browser leaves it out of the submit,
                            // which is exactly what unticking it already did.
                            $kkPinned = ($filterPanel['pinned_subcategory'] ?? null) === $kkSub->slug;
                        @endphp
                        <label class="flex items-center gap-2.5 py-0.5 min-h-10 lg:min-h-0 group {{ $kkEmpty ? 'cursor-not-allowed opacity-45' : ($kkPinned ? 'cursor-default' : 'cursor-pointer') }}"
                               @if($kkEmpty) title="Nothing in this collection yet" @elseif($kkPinned) title="You are browsing this collection" @endif>
                            <input type="checkbox" name="subcategory[]" value="{{ $kkSub->slug }}" onchange="this.form.submit()" @disabled($kkEmpty || $kkPinned)
                                   {{ in_array($kkSub->slug, $kkActiveSubs, true) ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-neutral-300 accent-[#6F9CA2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#6F9CA2]">
                            <span class="text-sm {{ $kkPinned ? 'font-medium text-neutral-900' : 'text-neutral-600 group-hover:text-neutral-900' }} transition-colors">{{ $kkSub->name }}</span>
                            @isset($kkSub->products_total)
                                <span class="ml-auto text-xs text-neutral-400 tabular-nums">{{ $kkSub->products_total }}</span>
                            @endisset
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif

    {{-- Size --}}
    @if($filterPanel['sizes']->isNotEmpty())
        <details class="kk-filter-section" {{ $kkOpen['size'] ? 'open' : '' }}>
            <summary class="kk-filter-head">
                Size
                <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="kk-filter-body">
                <div class="flex flex-wrap gap-1.5">
                    @foreach($filterPanel['sizes'] as $kkSize)
                        <label class="cursor-pointer select-none">
                            <input type="checkbox" name="size[]" value="{{ $kkSize }}"
                                   @checked(in_array($kkSize, $kkValues['size'], true))
                                   onchange="this.form.submit()" class="sr-only peer">
                            {{-- The selected chip is black, so the plain hover:text-* below would repaint
                                 its label near-black and swallow it. Tailwind v4 wraps peer-* in :where(),
                                 which zeroes its specificity, so peer-checked:text-white ties with the hover
                                 rule and loses on source order. The peer-checked:hover:* pair outranks it. --}}
                            <span class="inline-flex items-center min-h-10 lg:min-h-0 px-2.5 py-1 text-xs rounded-md border transition-colors
                                         border-neutral-200 text-neutral-700 hover:border-neutral-500 hover:text-neutral-900
                                         peer-checked:border-neutral-900 peer-checked:bg-neutral-900 peer-checked:text-white
                                         peer-checked:hover:text-white peer-checked:hover:border-neutral-900
                                         peer-focus-visible:ring-2 peer-focus-visible:ring-[#6F9CA2] peer-focus-visible:ring-offset-1">
                                {{ $kkSize }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif

    {{-- Colour --}}
    @if($filterPanel['colours']->isNotEmpty())
        <details class="kk-filter-section" {{ $kkOpen['colour'] ? 'open' : '' }}>
            <summary class="kk-filter-head">
                Colour
                <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="kk-filter-body">
                <div class="flex flex-wrap gap-1.5">
                    @foreach($filterPanel['colours'] as $kkC)
                        <label class="cursor-pointer select-none" title="{{ $kkC['name'] }}">
                            <input type="checkbox" name="colour[]" value="{{ $kkC['name'] }}"
                                   @checked(in_array($kkC['name'], $kkValues['colour'], true))
                                   onchange="this.form.submit()" class="sr-only peer">
                            {{-- The label inherits its colour so the selected state can
                                 invert it. Hardcoding it on the inner span left dark text
                                 on a dark chip once selected. --}}
                            {{-- Selected state is a ring, not a fill: filling the chip
                                 with black fights the swatch, which is the one thing
                                 the customer is actually reading. --}}
                            <span class="inline-flex items-center min-h-10 lg:min-h-0 gap-1.5 px-2 py-1 text-xs rounded-md border transition-all
                                         border-neutral-200 text-neutral-700 bg-white hover:border-neutral-500
                                         peer-checked:border-neutral-900 peer-checked:text-neutral-900 peer-checked:font-semibold
                                         peer-checked:ring-2 peer-checked:ring-neutral-900/15 peer-checked:shadow-sm
                                         peer-checked:hover:border-neutral-900
                                         peer-focus-visible:ring-2 peer-focus-visible:ring-[#6F9CA2] peer-focus-visible:ring-offset-1">
                                <span style="width:12px;height:12px;border-radius:50%;background-color: {{ $kkC['hex'] ?: '#ddd' }}; border:1px solid rgba(0,0,0,.2);"></span>
                                <span>{{ $kkC['name'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif

    {{-- Texture.

         Guarded with a fallback collection rather than a bare key read: a page
         can hand the panel an empty facet of its own, and a compiled view left
         over from before the deploy would otherwise fatal on the missing key
         instead of simply drawing no section. --}}
    @if(($filterPanel['textures'] ?? collect())->isNotEmpty())
        <details class="kk-filter-section" {{ $kkOpen['texture'] ? 'open' : '' }}>
            <summary class="kk-filter-head">
                Texture
                <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="kk-filter-body">
                <div class="flex flex-wrap gap-1.5">
                    @foreach($filterPanel['textures'] as $kkTexture)
                        <label class="cursor-pointer select-none">
                            <input type="checkbox" name="texture[]" value="{{ $kkTexture }}"
                                   @checked(in_array($kkTexture, $kkValues['texture'], true))
                                   onchange="this.form.submit()" class="sr-only peer">
                            {{-- A texture is a plain word with nothing to show beside it, so
                                 this is the Size chip rather than the Colour one - including
                                 the peer-checked:hover pair, which is here for the same
                                 reason: Tailwind v4 wraps peer-* in :where(), so the plain
                                 hover:text-* rule would win on source order and repaint a
                                 selected chip's label near-black on black. --}}
                            <span class="inline-flex items-center min-h-10 lg:min-h-0 px-2.5 py-1 text-xs rounded-md border transition-colors
                                         border-neutral-200 text-neutral-700 hover:border-neutral-500 hover:text-neutral-900
                                         peer-checked:border-neutral-900 peer-checked:bg-neutral-900 peer-checked:text-white
                                         peer-checked:hover:text-white peer-checked:hover:border-neutral-900
                                         peer-focus-visible:ring-2 peer-focus-visible:ring-[#6F9CA2] peer-focus-visible:ring-offset-1">
                                {{ $kkTexture }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif

    {{-- Brand --}}
    @if($filterPanel['brands']->isNotEmpty())
        <details class="kk-filter-section" {{ $kkOpen['brand'] ? 'open' : '' }}>
            <summary class="kk-filter-head">
                Brand
                <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="kk-filter-body">
                <div class="space-y-1.5 max-h-52 overflow-y-auto">
                    @foreach($filterPanel['brands'] as $kkBrand)
                        <label class="flex items-center gap-2.5 cursor-pointer group py-0.5 min-h-10 lg:min-h-0">
                            <input type="checkbox" name="brand[]" value="{{ $kkBrand->slug }}" onchange="this.form.submit()"
                                   {{ in_array($kkBrand->slug, $kkValues['brand'], true) ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-neutral-300 accent-[#6F9CA2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#6F9CA2]">
                            <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">{{ $kkBrand->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif

    {{-- Price Range --}}
    <details class="kk-filter-section" {{ $kkOpen['price'] ? 'open' : '' }}>
        <summary class="kk-filter-head">
            Price Range
            <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </summary>
        <div class="kk-filter-body">
            {{-- Min 1000 with Max 0 asks the shop for `price >= 1000 AND
                 price <= 0` - a range nothing can be in - so the grid came back
                 "0 products found" under a chip reading "₹1,000 - ₹0", with
                 nothing anywhere saying what was wrong.

                 The two numbers are not swapped into a range that works. That
                 was tried, and a shopper who typed 1000 and 0 was handed results
                 for ₹0-₹1,000 - an answer to a question they had not asked, and
                 no way to tell whether the shop had misread them or they had
                 misread the boxes. They are left as typed and the mistake is
                 named instead, right under the boxes still holding the numbers.

                 The message is live as they type (`input`, so it clears the
                 moment the pair makes sense again) and is also rendered by the
                 server, so a shared link or a "Shop It Your Way" hanger typed
                 backwards explains itself on arrival with no JS at all. Both
                 read ProductFilters::PRICE_ORDER_ERROR, so there is one wording.

                 priceError lives on the form's own x-data because that is where
                 the Apply guard reads it: an invalid range must not submit, and
                 the submit event only fires on the form. --}}
            <div class="flex items-center gap-2"
                 @input="
                     const lo = $refs.minPrice, hi = $refs.maxPrice;
                     priceError = (lo.value !== '' && hi.value !== '' && Number(lo.value) > Number(hi.value))
                         ? @js(\App\Support\ProductFilters::PRICE_ORDER_ERROR)
                         : null;
                 ">
                <div class="relative flex-1">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-neutral-600">&#8377;</span>
                    <input type="number" name="min_price" value="{{ $kkValues['min_price'] }}" min="0" step="any" inputmode="decimal"
                           placeholder="Min" aria-label="Minimum price" x-ref="minPrice"
                           {{-- The tint goes on as an inline style, not a class:
                                border-neutral-200 below and a border-red-* utility
                                are both single classes, so which one wins is
                                decided by their order in the built stylesheet
                                rather than by the order here. --}}
                           :aria-invalid="priceError ? 'true' : null"
                           :style="priceError && 'border-color:#f87171'"
                           class="w-full pl-6 pr-2 py-2 text-sm border border-neutral-200 rounded-lg focus:outline-none focus:border-[#6F9CA2] bg-neutral-50">
                </div>
                <span class="text-neutral-300 text-sm">-</span>
                <div class="relative flex-1">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-neutral-600">&#8377;</span>
                    <input type="number" name="max_price" value="{{ $kkValues['max_price'] }}" min="0" step="any" inputmode="decimal"
                           placeholder="Max" aria-label="Maximum price" x-ref="maxPrice"
                           {{-- The tint goes on as an inline style, not a class:
                                border-neutral-200 below and a border-red-* utility
                                are both single classes, so which one wins is
                                decided by their order in the built stylesheet
                                rather than by the order here. --}}
                           :aria-invalid="priceError ? 'true' : null"
                           :style="priceError && 'border-color:#f87171'"
                           class="w-full pl-6 pr-2 py-2 text-sm border border-neutral-200 rounded-lg focus:outline-none focus:border-[#6F9CA2] bg-neutral-50">
                </div>
            </div>
            {{-- role=alert so a screen reader hears it when Alpine reveals it,
                 rather than only on the next full page load. Hidden inline when
                 the server has nothing to report: x-show manages `display` from
                 then on, and if the bundle never loads a message that was never
                 true stays hidden - which is the right resting state. --}}
            <p class="mt-1.5 text-xs text-red-600" role="alert"
               x-show="priceError" x-text="priceError"
               @unless($kkValues['price_error']) style="display:none" @endunless>{{ $kkValues['price_error'] }}</p>
        </div>
    </details>

    {{-- Rating --}}
    @if($filterPanel['show_rating'])
        @php
            // One copy of the star outline, reused by both the filled and the
            // empty star below - the row draws five of them, so inlining the
            // path five times per row put the same 700 characters on the page
            // twenty-five times.
            $kkStarPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
        @endphp
        <details class="kk-filter-section" {{ $kkOpen['rating'] ? 'open' : '' }}>
            <summary class="kk-filter-head">
                Rating
                <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="kk-filter-body">
                <div class="space-y-1.5" role="group" aria-label="Minimum rating">
                    {{-- "Any rating" is what takes a chosen rating back off. A radio
                         group with no empty option can be set but never unset: once a
                         shopper picked 4 stars, the only way back to an unfiltered list
                         was the chip above the grid - and on a page scrolled past it,
                         nothing in the sidebar could undo the choice at all. --}}
                    <label class="flex items-center gap-2.5 cursor-pointer group py-0.5 min-h-10 lg:min-h-0">
                        <input type="radio" name="rating" value=""
                               {{ $kkValues['rating'] === null ? 'checked' : '' }}
                               @change.debounce.350ms="$el.form.submit()"
                               class="w-3.5 h-3.5 border-neutral-300 accent-[#6F9CA2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#6F9CA2]">
                        <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">Any rating</span>
                    </label>
                    {{-- The query is rating >= N, so every row is a floor, not an exact
                         score. The label said plainly "4 <star>", which reads as "rated four"
                         and is not what the box does - a 4.6-star product is in the 4 row
                         too. Five stars with the top ones filled, and the words "& up",
                         say the actual rule. Counting down puts the choosiest option
                         first, which is the one shoppers reach for.

                         1 & up is kept rather than dropped: products default to rating 0
                         until a review is approved, so it is the "has been reviewed at
                         all" filter, not a no-op. --}}
                    @for($kkStars = 5; $kkStars >= 1; $kkStars--)
                        <label class="flex items-center gap-2.5 cursor-pointer group py-0.5 min-h-10 lg:min-h-0">
                            <input type="radio" name="rating" value="{{ $kkStars }}"
                                   {{ $kkValues['rating'] === $kkStars ? 'checked' : '' }}
                                   @change.debounce.350ms="$el.form.submit()"
                                   aria-label="{{ $kkStars }} {{ Str::plural('star', $kkStars) }} and up"
                                   class="w-3.5 h-3.5 border-neutral-300 accent-[#6F9CA2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#6F9CA2]">
                            <span class="flex items-center gap-1.5">
                                {{-- aria-hidden: the input above already carries the whole
                                     label in words, so a screen reader reads "4 stars and
                                     up" once instead of counting out five graphics. --}}
                                <span class="flex items-center gap-0.5" aria-hidden="true">
                                    @for($kkI = 1; $kkI <= 5; $kkI++)
                                        <svg class="w-3.5 h-3.5 {{ $kkI <= $kkStars ? 'text-amber-400' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $kkStarPath }}"/>
                                        </svg>
                                    @endfor
                                </span>
                                <span class="text-xs text-neutral-500 group-hover:text-neutral-700 transition-colors">&amp; up</span>
                            </span>
                        </label>
                    @endfor
                </div>
            </div>
        </details>
    @endif

    {{-- Availability & Offers --}}
    <details class="kk-filter-section" {{ $kkOpen['availability'] ? 'open' : '' }}>
        <summary class="kk-filter-head">
            Availability
            <svg class="kk-filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </summary>
        <div class="kk-filter-body">
            <div class="space-y-2">
                <label class="flex items-center gap-2.5 cursor-pointer group py-0.5 min-h-10 lg:min-h-0">
                    <input type="checkbox" name="in_stock" value="1" onchange="this.form.submit()"
                           @checked($kkValues['in_stock'])
                           class="w-3.5 h-3.5 rounded border-neutral-300 accent-[#6F9CA2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#6F9CA2]">
                    <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">In Stock Only</span>
                </label>
                @if($filterPanel['show_on_sale'] ?? true)
                    <label class="flex items-center gap-2.5 cursor-pointer group py-0.5 min-h-10 lg:min-h-0">
                        <input type="checkbox" name="on_sale" value="1" onchange="this.form.submit()"
                               @checked($kkValues['on_sale'])
                               class="w-3.5 h-3.5 rounded border-neutral-300 accent-[#6F9CA2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#6F9CA2]">
                        <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">On Sale</span>
                    </label>
                @endif
            </div>
        </div>
    </details>

    {{-- Action Buttons.

         In the slide-over the row pins itself to the bottom of the scrolling
         body: the panel is one tall column, so on a phone Apply sat below eight
         expanded sections and the shopper had to scroll the whole form to reach
         it. Everything except the two price boxes auto-submits, which hid the
         problem on desktop and made the drawer apply exactly one filter per
         open on a phone. --}}
    <div class="flex gap-2 pt-4 {{ ($kkStickyActions ?? false) ? 'sticky bottom-0 -mx-4 px-4 pb-4 bg-white border-t border-neutral-200' : '' }}">
        <button type="submit" class="flex-1 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-lg transition-colors">
            Apply
        </button>
        {{-- Reset returns to THIS listing with nothing ticked. It used to send the
             shop back to the home page, which reads as "your filters were so bad we
             threw you out of the shop". --}}
        <a href="{{ $filterPanel['reset'] }}" class="flex-1 py-2.5 text-center text-sm font-medium text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
            Reset
        </a>
    </div>
</form>
