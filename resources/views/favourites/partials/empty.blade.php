{{--
    "Nothing starred yet."

    Its own partial for the same reason the wishlist's is: the page has two ways
    of arriving here - the list was already empty when the page was built, and
    the shopper has just removed the last tile without leaving the page. Both
    draw this, so the second cannot quietly become a different screen from the
    first.
--}}
<div class="w-20 h-20 mx-auto mb-6 bg-neutral-100 rounded-full flex items-center justify-center">
    <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
    </svg>
</div>
<h2 class="text-xl font-bold text-neutral-900 mb-2">You have no favourites yet</h2>
<p class="text-neutral-600 mb-6">Tap the star on any product to keep it here, alongside your wishlist.</p>
<a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-white text-sm font-semibold rounded-lg transition-colors" style="background:#4a2d1a;" onmouseover="this.style.background='#2d1810'" onmouseout="this.style.background='#4a2d1a'">
    Continue Shopping
</a>
