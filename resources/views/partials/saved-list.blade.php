{{--
    A saved list's page. The wishlist and favourites both draw this one.

    Included with:
      $products   - the server's render of the list, newest save first
      $listStore  - the Alpine store behind it ('wishlist' | 'favourites')
      $listHeading- the page's own <h1>
      $listEmpty  - the view that renders this list's empty state

    One template rather than two, because the grid reasoning below is the kind
    that gets worked out once and then quietly lost in a copy - and a saved list
    that lays its tiles out unlike the other saved list is exactly the drift the
    shared <x-product-card> exists to prevent, one level up.
--}}
<div class="text-center mb-8">
    <h1 class="text-2xl font-bold text-neutral-900">{{ $listHeading }}</h1>
    {{-- The server's count is the one on screen at first paint; Alpine takes
         the label over from there so removing a tile corrects it without a
         reload. --}}
    <p class="text-sm text-neutral-600 mt-1">
        <span x-text="$store.{{ $listStore }}.count">{{ $products->count() }}</span>
        <span x-text="$store.{{ $listStore }}.count === 1 ? 'item' : 'items'">{{ $products->count() === 1 ? 'item' : 'items' }}</span> saved
    </p>
</div>

@if($products->isNotEmpty())
    {{-- The listing grid's card, at the listing grid's size.

         One column more than the shop from lg up, and that is what makes them
         match rather than what makes them differ. The shop's grid shares its
         row with the filter sidebar - 240px of w-60 and a 24px gap - so it
         draws its four columns into about 984px. This page has no sidebar, so
         the same four columns would have the full 1248px to spread over and
         every tile would come out a third bigger than the one the shopper
         saved. Five columns here lands within a couple of pixels of the shop's
         card at xl, and four lands on it at lg, where the shop is still drawing
         three beside its sidebar. Below lg the sidebar is stacked away and both
         grids are full width, so the counts are the same there.

         Each tile is wrapped rather than modified: the wrapper is what
         disappears when the button on the card takes the product off the list,
         so the grid closes up on the spot instead of leaving a hole until the
         next page load.

         Only THIS list's button is forced on - the one the shopper needs to
         empty the page they are standing on. The other list's button is left to
         the admin setting, so turning favourites off on cards does not switch
         it back on here. --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
        @foreach($products as $product)
            <div x-show="$store.{{ $listStore }}.has({{ $product->id }})">
                <x-product-card :product="$product"
                                :show-wishlist="$listStore === 'wishlist' ? true : null"
                                :show-favourites="$listStore === 'favourites' ? true : null" />
            </div>
        @endforeach
    </div>

    {{-- Shown only once the last tile has been removed. Cloaked so it is not on
         screen for the moment before Alpine reads the cookie and finds the list
         is not empty after all. --}}
    <div x-show="$store.{{ $listStore }}.count === 0" x-cloak class="max-w-md mx-auto text-center py-20">
        @include($listEmpty)
    </div>
@else
    <div class="max-w-md mx-auto text-center py-20">
        @include($listEmpty)
    </div>
@endif
