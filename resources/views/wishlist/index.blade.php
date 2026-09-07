<x-layouts.app>
    <x-slot name="title">My Wishlist - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            {{-- Heading, grid and empty state all live in the shared saved-list
                 partial, which favourites draws too. --}}
            @include('partials.saved-list', [
                'listStore' => 'wishlist',
                'listHeading' => 'My Wishlist',
                'listEmpty' => 'wishlist.partials.empty',
            ])
        </div>
    </div>
</x-layouts.app>
