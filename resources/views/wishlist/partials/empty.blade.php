{{--
    "Nothing saved yet."

    Its own partial because the page has two ways of arriving at it: the list
    was already empty when the page was built, and the shopper has just removed
    the last tile without leaving the page. Both draw this, so the second cannot
    quietly become a different screen from the first.
--}}
<div class="w-20 h-20 mx-auto mb-6 bg-neutral-100 rounded-full flex items-center justify-center">
    <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
    </svg>
</div>
<h2 class="text-xl font-bold text-neutral-900 mb-2">Your wishlist is empty</h2>
<p class="text-neutral-600 mb-6">Tap the heart on any product to save it here.</p>
<a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-white text-sm font-semibold rounded-lg transition-colors" style="background:#4a2d1a;" onmouseover="this.style.background='#2d1810'" onmouseout="this.style.background='#4a2d1a'">
    Continue Shopping
</a>
