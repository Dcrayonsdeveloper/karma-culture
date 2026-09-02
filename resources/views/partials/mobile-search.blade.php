<!-- Mobile Search Panel -->
{{--
    Search, below sm, where the inline header bar is hidden.

    The header's magnifier used to be a plain link to /search. With no query
    that page has nothing to type into - it renders "Start searching. Enter a
    keyword to find products." and stops - so tapping search on a phone dropped
    the shopper on a dead end and asked them to enter a keyword with no field
    to enter it in.

    This is the same searchBar() component the desktop bar runs on, so
    suggestions, the typewriter placeholder and voice search behave identically;
    only the frame is different - full screen, because a phone has no room for
    a dropdown under a 40px input.
--}}
<div x-data="{ open: false, ...searchBar() }"
     x-init="stopTypewriter()"
     {{-- No typewriter here, deliberately. The panel focuses its field the moment
          it opens, and the desktop bar stops the animation on focus for good
          reason: a placeholder that is still typing itself out competes with the
          shopper who is already typing, and it leaves half-words like "Search for"
          under a live cursor. stopTypewriter() sets the plain full placeholder and
          cancels the timer, so nothing churns behind a closed panel either. --}}
     @open-mobile-search.window="
        open = true;
        $nextTick(() => $refs.mobileSearchInput.focus());
     "
     @keydown.escape.window="open = false; stopTypewriter()"
     x-show="open"
     x-cloak
     class="sm:hidden fixed inset-0 z-50 bg-kk-cream flex flex-col"
     role="dialog"
     aria-modal="true"
     {{-- Named "Search", not "Search products": the field inside carries that
          label, and a screen reader announcing both reads it twice over. --}}
     aria-label="Search">

    {{-- Search bar row: back arrow, field, mic. Mirrors a native search screen,
         where the field IS the header rather than sitting under one. --}}
    <div class="shrink-0 bg-kk-cream-lighter border-b border-kk-cream-dark px-2 py-2.5">
        <form action="{{ route('search') }}" method="GET" class="flex items-center gap-1.5">
            <button type="button"
                    @click="open = false; stopTypewriter()"
                    class="p-2 -ml-0.5 text-kk-brown shrink-0"
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
                    class="p-2 shrink-0 transition-colors"
                    :class="listening ? 'text-red-500' : 'text-kk-brown'"
                    :aria-label="listening ? 'Stop listening' : 'Search by voice'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z"/>
                </svg>
            </button>
        </form>
    </div>

    {{-- Results --}}
    <div class="flex-1 overflow-y-auto overscroll-contain">
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
                             nodes either side of the <span> it printed “ shirt ”. --}}
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
            <div class="px-6 py-14 text-center">
                <p class="text-sm text-kk-text">No matches for &ldquo;<span x-text="query"></span>&rdquo;</p>
                <p class="text-xs text-kk-text-muted mt-1">Try a shorter word, or a different spelling.</p>
                <a href="{{ route('shop') }}" class="inline-block mt-5 px-5 py-2.5 bg-kk-brown text-white text-sm font-semibold rounded-full">
                    Browse all products
                </a>
            </div>
        </template>

        {{-- Nothing typed yet. A phone screen this empty needs somewhere to go,
             so the popular searches double as the first tap. --}}
        <template x-if="query.length < 2 && !loading">
            <div class="px-4 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-kk-text-muted mb-2.5">Popular searches</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(['Shirts', 'Polo T-Shirts', 'Formal Shirts', 'Linen Shirts', 'Trousers', 'Chinos'] as $kkTerm)
                        <a href="{{ route('search', ['q' => $kkTerm]) }}"
                           class="px-3.5 py-2 bg-kk-cream-lighter border border-kk-cream-dark rounded-full text-sm text-kk-text">
                            {{ $kkTerm }}
                        </a>
                    @endforeach
                </div>
            </div>
        </template>
    </div>
</div>
