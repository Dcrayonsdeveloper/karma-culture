@php
    // The store already substitutes this when a product has no image at all, but
    // a row pointing at a file that has since gone missing still 404'd into an
    // empty tile, so the frame gets it as an explicit fallback too.
    $placeholder = asset_v('images/no-product-image.svg');
@endphp

<x-layouts.app>
    <x-slot name="title">My Wishlist - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8 max-w-5xl">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-neutral-900">My Wishlist</h1>
                <p class="text-sm text-neutral-600 mt-1" x-show="!$store.wishlist.isLoading" x-cloak>
                    <span x-text="$store.wishlist.count"></span> <span x-text="$store.wishlist.count === 1 ? 'item' : 'items'"></span> saved
                </p>
            </div>

            {{-- Loading skeleton --}}
            <div x-show="$store.wishlist.isLoading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <template x-for="i in 4" :key="i">
                    <div class="bg-white rounded-xl border border-neutral-100 overflow-hidden animate-pulse">
                        <div class="aspect-[4/5] bg-neutral-200"></div>
                        <div class="p-3 space-y-2">
                            <div class="h-3 w-3/4 bg-neutral-200 rounded"></div>
                            <div class="h-3 w-1/3 bg-neutral-200 rounded"></div>
                            <div class="h-8 w-full bg-neutral-200 rounded"></div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Wishlist grid --}}
            <div x-show="!$store.wishlist.isLoading && $store.wishlist.items.length > 0" x-cloak class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <template x-for="item in $store.wishlist.items" :key="item.id">
                    <div class="bg-white rounded-xl border border-neutral-100 overflow-hidden group flex flex-col">
                        {{-- The tile stays 4:5 so the grid lines up, but the saved
                             product is shown whole rather than cropped to that ratio -
                             a wishlist is the one place the customer is checking they
                             saved the right thing. The blurred copy behind it fills
                             what contain leaves over. --}}
                        <a :href="item.url" class="kk-media kk-media--zoom relative block aspect-[4/5] overflow-hidden">
                            <img class="kk-media__fill" :src="item.image" alt="" aria-hidden="true" loading="lazy" decoding="async">
                            <img :src="item.image" :alt="item.name" data-fallback="{{ $placeholder }}" loading="lazy" decoding="async">
                            <span class="kk-media__fallback" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                    <circle cx="8.5" cy="9.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 20"/>
                                </svg>
                            </span>
                            {{-- The frame lifts the image to z-1, so the badge and the
                                 remove button have to be lifted clear of it - without
                                 this the button paints under the image and the click
                                 lands on the link instead. --}}
                            <template x-if="item.discount > 0">
                                <span class="absolute top-2 left-2 z-10 bg-[#8c5c34] text-white text-[10px] font-bold px-2 py-0.5 rounded-md" x-text="item.discount + '% Off'"></span>
                            </template>
                            <button @click.prevent="$store.wishlist.remove(item.id)"
                                    class="absolute top-2 right-2 z-10 w-10 h-10 sm:w-8 sm:h-8 flex items-center justify-center bg-white/90 rounded-full text-[#8c5c34] hover:bg-white shadow-sm"
                                    aria-label="Remove from wishlist">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </button>
                        </a>
                        <div class="p-3 flex flex-col flex-1">
                            <a :href="item.url" class="text-sm font-medium text-neutral-900 line-clamp-1 hover:text-[#8c5c34] transition-colors" x-text="item.name"></a>
                            <div class="flex items-baseline gap-1.5 mt-1">
                                <span class="text-sm font-bold text-neutral-900" x-text="'₹' + Number(item.price).toLocaleString('en-IN',{maximumFractionDigits:2})"></span>
                                <template x-if="item.mrp > item.price">
                                    <span class="text-[11px] text-neutral-500 line-through" x-text="'₹' + Number(item.mrp).toLocaleString('en-IN',{maximumFractionDigits:2})"></span>
                                </template>
                            </div>
                            <template x-if="!item.in_stock">
                                <p class="text-[11px] font-medium text-red-500 mt-1">Out of Stock</p>
                            </template>
                            <a :href="item.url"
                               class="mt-3 block w-full py-3 sm:py-2.5 text-center text-[12px] font-semibold text-white rounded-md transition-colors"
                               style="background:#2D1810;"
                               onmouseover="this.style.background='#1F1109'" onmouseout="this.style.background='#2D1810'">
                                View Product
                            </a>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty state --}}
            <div x-show="!$store.wishlist.isLoading && $store.wishlist.items.length === 0" x-cloak class="max-w-md mx-auto text-center py-20">
                <div class="w-20 h-20 mx-auto mb-6 bg-neutral-100 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-neutral-900 mb-2">Your wishlist is empty</h2>
                <p class="text-neutral-600 mb-6">Tap the heart on any product to save it here - no login needed.</p>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-white text-sm font-semibold rounded-lg transition-colors" style="background:#4a2d1a;" onmouseover="this.style.background='#2d1810'" onmouseout="this.style.background='#4a2d1a'">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
