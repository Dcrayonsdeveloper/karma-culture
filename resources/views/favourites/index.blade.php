<x-layouts.app>
    <x-slot name="title">My Favourites - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            {{-- The same partial the wishlist draws, so the two pages cannot
                 drift into laying their tiles out differently. --}}
            @include('partials.saved-list', [
                'listStore' => 'favourites',
                'listHeading' => 'My Favourites',
                'listEmpty' => 'favourites.partials.empty',
            ])
        </div>
    </div>
</x-layouts.app>
