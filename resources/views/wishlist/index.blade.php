<x-layouts.app>
    <x-slot name="title">My Wishlist - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-neutral-900">My Wishlist</h1>
                {{-- The server's count is the one on screen at first paint; Alpine
                     takes the label over from there so removing a tile corrects it
                     without a reload. --}}
                <p class="text-sm text-neutral-600 mt-1">
                    <span x-text="$store.wishlist.count">{{ $products->count() }}</span>
                    <span x-text="$store.wishlist.count === 1 ? 'item' : 'items'">{{ $products->count() === 1 ? 'item' : 'items' }}</span> saved
                </p>
            </div>

            @if($products->isNotEmpty())
                {{-- The listing grid's card, at the listing grid's size.

                     One column more than the shop from lg up, and that is what
                     makes them match rather than what makes them differ. The
                     shop's grid shares its row with the filter sidebar - 240px
                     of w-60 and a 24px gap - so it draws its four columns into
                     about 984px. This page has no sidebar, so the same four
                     columns would have the full 1248px to spread over and every
                     tile would come out a third bigger than the one the shopper
                     saved. Five columns here lands within a couple of pixels of
                     the shop's card at xl, and four lands on it at lg, where the
                     shop is still drawing three beside its sidebar. Below lg the
                     sidebar is stacked away and both grids are full width, so
                     the counts are the same there.

                     Each tile is wrapped rather than modified: the wrapper is
                     what disappears when the heart on the card takes the product
                     off the list, so the grid closes up on the spot instead of
                     leaving a hole until the next page load. --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
                    @foreach($products as $product)
                        <div x-show="$store.wishlist.has({{ $product->id }})">
                            <x-product-card :product="$product" :show-wishlist="true" />
                        </div>
                    @endforeach
                </div>

                {{-- Shown only once the last tile has been removed. Cloaked so it
                     is not on screen for the moment before Alpine reads the
                     cookie and finds the list is not empty after all. --}}
                <div x-show="$store.wishlist.count === 0" x-cloak class="max-w-md mx-auto text-center py-20">
                    @include('wishlist.partials.empty')
                </div>
            @else
                <div class="max-w-md mx-auto text-center py-20">
                    @include('wishlist.partials.empty')
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
