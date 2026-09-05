{{--
    The header's Filters drawer - the filter sidebar on a page that is not a listing.

    Eight pages render the sidebar inline through partials.product-listing. Every
    other storefront page - the home rails, the wishlist, the brand index, a CMS
    page, the cart - had no way to reach a filter at all, so narrowing the
    catalogue meant first finding your way to /shop, which nothing links to.

    This is included on every page and does nothing until the header's Filters
    button is pressed. The listing pages answer that same event with their OWN
    sidebar, so the filters apply to the listing being looked at; this drawer
    stands down whenever one of them is on the page (the data attribute below is
    how it checks) and otherwise opens a panel scoped to the whole shop that
    submits to /shop.

    The panel is fetched on first open rather than rendered here. ProductFilters
    runs five facet queries to build one, and putting those behind every page
    load - the cart, the checkout, a blog post - to serve a drawer most visitors
    never open is not a trade worth making. The fetch happens once and is kept.
--}}
<div x-data="{
        shown: false,
        loaded: false,
        loading: false,
        failed: false,

        /* A listing page has its own sidebar bound to the same event, and it
           filters the list actually on screen. Two panels answering one button
           would put the shop's filters over a category's own, so this one only
           acts when no sidebar is present. Checked per press, not once at boot:
           it costs a single DOM lookup and cannot go stale. */
        handle() {
            if (document.querySelector('[data-kk-filter-sidebar]')) return;
            this.shown = true;
            this.load();
        },

        async load() {
            if (this.loaded || this.loading) return;
            this.loading = true;
            this.failed = false;
            try {
                const res = await fetch(@js(route('shop.filters')), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error(res.status);
                this.$refs.body.innerHTML = await res.text();
                /* The panel carries Alpine of its own: the debounced
                   auto-submit on the category and rating radios. Alpine only
                   walks markup it inserted itself, so injected HTML has to be
                   initialised by hand or those radios never submit. The
                   sections fold without any of this - they are <details>. */
                window.Alpine.initTree(this.$refs.body);
                this.loaded = true;
            } catch (e) {
                this.failed = true;
            } finally {
                this.loading = false;
            }
        },
     }"
     x-cloak
     @open-global-filters.window="handle()">

    <div x-show="shown" class="fixed inset-0 z-[60]"
         role="dialog" aria-modal="true" aria-label="Filters"
         @keydown.escape.window="shown = false">

        <div x-show="shown"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="shown = false" class="absolute inset-0 bg-black/40"></div>

        <div x-show="shown"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             x-trap.noscroll="shown"
             class="absolute inset-y-0 left-0 w-80 max-w-[85vw] bg-white shadow-xl flex flex-col">

            <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
                <h2 class="font-semibold text-neutral-900">Filters</h2>
                <button @click="shown = false" type="button" class="p-1 text-neutral-600 hover:text-neutral-900" aria-label="Close filters">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Says where Apply is going to take them. On a listing the sidebar
                 narrows what is already on screen; here it leaves the page, and
                 a filter panel that silently navigates elsewhere is a nasty
                 surprise on the wishlist or halfway through the cart. --}}
            <p class="px-4 pt-3 text-xs text-neutral-500">Filters open the full shop.</p>

            <div class="flex-1 overflow-y-auto p-4">
                {{-- Skeleton rather than a spinner: the panel is a tall stack of
                     rows and this keeps the drawer from collapsing to nothing and
                     snapping back to full height a moment later. --}}
                <div x-show="loading" class="space-y-3 animate-pulse">
                    @for($kkI = 0; $kkI < 5; $kkI++)
                        <div class="h-4 w-1/3 bg-neutral-200 rounded"></div>
                        <div class="h-8 w-full bg-neutral-100 rounded"></div>
                    @endfor
                </div>

                {{-- A drawer that fails to load still has to leave a way through
                     to the shop, or the button is a dead end. --}}
                <div x-show="failed" x-cloak class="text-center py-10">
                    <p class="text-sm text-neutral-600 mb-4">Filters could not be loaded.</p>
                    <a href="{{ route('shop') }}" class="inline-block px-5 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-lg transition-colors">
                        Browse all products
                    </a>
                </div>

                <div x-ref="body"></div>
            </div>
        </div>
    </div>
</div>
