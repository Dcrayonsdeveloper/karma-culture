<!-- Mobile Search (in-header) -->
{{--
    Search, below sm, where the inline header bar is hidden.

    This sits inside the header row rather than at body level so it can take
    that row over exactly - nothing has to measure where the header ended up,
    and it travels with the sticky header when the page scrolls behind it.

    It has been three things now, and both reasons are worth keeping. First a
    plain link to /search: that page has no field to type in until it already
    has a query, so it dropped the shopper on a dead end that asked for a
    keyword with nowhere to put one. Then a full-screen panel, which fixed the
    dead end but read as a separate screen - a phone covered edge to edge is a
    new page, whatever the URL bar says. Now the row becomes the field and the
    answers drop underneath it, so the page they were already reading is still
    there behind.

    Nothing here navigates on its own. The form still points at /search so it
    works with JavaScript off, but with Alpine up the phone keyboard's Search
    key answers in place. Only a tap on a product, or on "See all results",
    leaves the page.

    It runs the same searchBar() component as the desktop bar, so suggestions
    and voice behave identically; only the frame differs.
--}}
<div x-data="{ open: false, ...searchBar() }"
     x-init="stopTypewriter()"
     {{-- No typewriter here, deliberately. The field is focused the moment it
          opens, and the desktop bar stops the animation on focus for good
          reason: a placeholder that is still typing itself out competes with
          the shopper who is already typing, and it leaves half-words like
          "Search for" under a live cursor. stopTypewriter() sets the plain full
          placeholder and cancels the timer, so nothing churns behind a closed
          field either. --}}
     @open-mobile-search.window="
        open = true;
        $nextTick(() => $refs.mobileSearchInput.focus());
     "
     @keydown.escape.window="open = false"
     class="sm:hidden">

    {{-- The page behind, dimmed and out of reach. Deliberately not an
         @click.outside on the wrapper: the magnifier that opens this sits
         outside it, so a single tap would open and close in the same breath.
         touch-action stops a drag here scrolling the page underneath. --}}
    <div x-show="open" x-cloak x-transition.opacity
         @click="open = false"
         class="fixed inset-0 z-20 bg-black/40"
         style="touch-action: none;"
         aria-hidden="true"></div>

    {{-- The header row itself: back, field, mic. Opaque, so the logo and icons
         underneath are covered rather than showing through it.

         -left-3/-right-3 and its own px-3 cancel and restore the header's
         padding: without that the cream stops short of both screen edges and
         the dimmed page shows through as a grey sliver down each side of the
         search row. The contents land in exactly the same place either way. --}}
    <div x-show="open" x-cloak class="absolute -left-3 -right-3 top-0 bottom-0 px-3 z-30 bg-kk-cream flex items-center">
        <form action="{{ route('search') }}" method="GET" role="search"
              {{-- The blue Search key on a phone keyboard would otherwise load
                   /search. Flush the debounce instead and drop the keyboard, so
                   what fills the screen is the answer it was already typing
                   towards. --}}
              @submit.prevent="fetchSuggestions(); $refs.mobileSearchInput.blur()"
              class="flex items-center gap-1.5 w-full">
            <button type="button"
                    @click="open = false"
                    class="p-2.5 text-kk-brown shrink-0"
                    aria-label="Close search">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </button>

            <div class="relative flex-1 min-w-0">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-kk-brown pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="search"
                       name="q"
                       x-ref="mobileSearchInput"
                       x-model="query"
                       @input.debounce.300ms="fetchSuggestions()"
                       :placeholder="currentPlaceholder"
                       class="w-full pl-9 pr-3 py-2.5 text-sm bg-white border border-kk-cream-dark rounded-full text-kk-brown placeholder-kk-text-muted focus:border-kk-brown focus:outline-none"
                       autocomplete="off"
                       enterkeyhint="search"
                       aria-label="Search products">
            </div>

            {{-- Voice, on the same component as the desktop bar. Hidden when the
                 browser has no Speech Recognition, rather than offering a button
                 that can only ever explain itself. --}}
            <button x-show="recognition" x-cloak
                    type="button"
                    @click.prevent="toggleMic()"
                    class="p-2.5 shrink-0 transition-colors"
                    :class="listening ? 'text-red-500' : 'text-kk-brown'"
                    :aria-label="listening ? 'Stop listening' : 'Search by voice'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z"/>
                </svg>
            </button>
        </form>
    </div>

    {{-- The answers, dropped under the header. -left-3/-right-3 cancels the
         header's px-3 so the sheet runs edge to edge; capped and scrolled
         inside itself so the page behind keeps its own scroll position. --}}
    <div x-show="open" x-cloak x-transition
         class="absolute -left-3 -right-3 top-full z-30 max-h-[70vh] overflow-y-auto overscroll-contain bg-kk-cream border-t border-kk-cream-dark shadow-lg">

        {{-- Permission, listening and failure states. Same panel as the desktop
             bar: without it the mic button on a phone had no way to say
             anything, so every refusal was a button that just did nothing. --}}
        @include('partials.voice-search-panel')

        {{-- Typed enough, and something came back. --}}
        <template x-if="results.length > 0">
            <ul class="divide-y divide-kk-cream-dark/60">
                <template x-for="result in results" :key="result.id">
                    <li>
                        <a :href="result.url" class="flex items-center gap-3 px-4 py-3 active:bg-kk-cream-lighter">
                            {{-- Contained, not cropped: a tall product shot cropped to a
                                 square thumb shows nothing but fabric. --}}
                            <div class="kk-media kk-media--thumb w-11 h-11 rounded shrink-0">
                                <img class="kk-media__fill" :src="result.image" alt="" aria-hidden="true">
                                <img :src="result.image" :alt="result.name" data-fallback="{{ asset_v('images/no-product-image.svg') }}">
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm text-kk-text truncate" x-text="result.name"></div>
                                <div class="text-xs text-kk-text-muted truncate" x-text="result.category"></div>
                            </div>
                            <svg class="w-4 h-4 text-kk-text-muted shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                            </svg>
                        </a>
                    </li>
                </template>

                {{-- The suggestion list is capped, so it is not the whole answer.
                     This is the way through to the full, filterable results. --}}
                <li>
                    <a :href="'{{ route('search') }}?q=' + encodeURIComponent(query)"
                       class="flex items-center justify-center gap-1.5 px-4 py-3.5 text-sm font-semibold text-kk-brown active:bg-kk-cream-lighter">
                        {{-- One span, not three flex items. The row's gap-1.5 applies
                             between flex children, and with the quotes as bare text
                             nodes either side of the <span> it printed a space inside
                             each quotation mark. --}}
                        <span class="truncate">See all results for &ldquo;<span x-text="query"></span>&rdquo;</span>
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                </li>
            </ul>
        </template>

        {{-- Searched, found nothing. Offering the whole catalogue beats a dead end. --}}
        <template x-if="query.length >= 2 && results.length === 0 && !loading">
            <div class="px-6 py-10 text-center">
                <p class="text-sm text-kk-text">No matches for &ldquo;<span x-text="query"></span>&rdquo;</p>
                <p class="text-xs text-kk-text-muted mt-1">Try a shorter word, or a different spelling.</p>
                <a href="{{ route('shop') }}" class="inline-block mt-5 px-5 py-2.5 bg-kk-brown text-white text-sm font-semibold rounded-full">
                    Browse all products
                </a>
            </div>
        </template>

        {{-- Nothing typed yet. The popular searches double as the first tap, so
             an empty field is still somewhere to go. Buttons rather than links:
             they fill the field and answer here, the way every other route
             through this panel now does. --}}
        <template x-if="query.length < 2 && !loading">
            <div class="px-4 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-kk-text-muted mb-2.5">Popular searches</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(['Shirts', 'Polo T-Shirts', 'Formal Shirts', 'Linen Shirts', 'Trousers', 'Chinos'] as $kkTerm)
                        <button type="button"
                                @click="query = @js($kkTerm); fetchSuggestions()"
                                class="px-3.5 py-2.5 bg-kk-cream-lighter border border-kk-cream-dark rounded-full text-sm text-kk-text">
                            {{ $kkTerm }}
                        </button>
                    @endforeach
                </div>
            </div>
        </template>
    </div>
</div>
