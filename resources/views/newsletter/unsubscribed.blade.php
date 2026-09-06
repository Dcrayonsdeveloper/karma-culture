<x-layouts.app>
    <x-slot name="title">Unsubscribed - {{ config('app.name') }}</x-slot>

    @push('meta')
        {{-- An unsubscribe confirmation has no business in search results, and
             a crawler that followed the link out of a leaked email should not
             be indexing it either. --}}
        <meta name="robots" content="noindex, nofollow">
    @endpush

    <div class="container mx-auto px-4 py-16 sm:py-24">
        <div class="max-w-md mx-auto text-center">
            <div class="w-14 h-14 mx-auto mb-6 rounded-full flex items-center justify-center"
                 style="background: var(--kk-cream-light, #f7eedb);">
                <svg class="w-7 h-7" fill="none" stroke="var(--kk-brown, #4a2d1a)" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-2xl font-semibold text-neutral-900 mb-3">You have been unsubscribed</h1>

            {{-- The same page whether or not the token matched, so this cannot
                 be used to find out which addresses are on the list. The line
                 below is the only part that differs, and only to confirm which
                 address was acted on when we know it. --}}
            <p class="text-neutral-600 leading-relaxed">
                @if($email)
                    <strong class="text-neutral-800">{{ $email }}</strong> has been taken off the
                    Karmaa Kulture newsletter. You will not receive any more of these emails.
                @else
                    You will not receive any more Karmaa Kulture newsletter emails.
                @endif
            </p>

            <p class="text-sm text-neutral-500 mt-4 leading-relaxed">
                Order updates and delivery notices are separate - those keep coming for
                anything you buy, and are not part of the newsletter.
            </p>

            <div class="mt-8 flex flex-wrap gap-3 justify-center">
                <a href="{{ route('home') }}"
                   class="px-5 py-2.5 text-sm font-semibold rounded-xl text-white transition-colors"
                   style="background: var(--kk-brown-dark, #2d1810);">
                    Back to the shop
                </a>
                <a href="{{ route('blog') }}"
                   class="px-5 py-2.5 text-sm font-medium rounded-xl border border-neutral-300 text-neutral-700 hover:bg-neutral-50 transition-colors">
                    Read the journal
                </a>
            </div>

            <p class="text-xs text-neutral-500 mt-8">
                Changed your mind? You can subscribe again from the footer of any page.
            </p>
        </div>
    </div>
</x-layouts.app>
