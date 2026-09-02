@php
    use App\Support\PopupSettings;

    $exit = PopupSettings::all(PopupSettings::EXIT);
@endphp

@if($exit['enabled'])
<div
    x-data="exitPopup(@js($exit['code']), {{ $exit['minutes'] }}, @js(auth()->user()?->email ?? ''))"
    x-cloak
    x-show="open"
    {{-- See the offer popup: this subtree is the popup's own chrome, so clicks
         inside it are not the engagement signal that stops the cycle. --}}
    data-kk-popup="exit"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-[75] flex items-center justify-center p-3 sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="exit-popup-title"
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/65 backdrop-blur-sm" @click="close()"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-trap.noscroll="open"
        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 max-h-[90vh]"
    >
        <button type="button" @click="close()" aria-label="Close" class="absolute top-2.5 right-2.5 z-20 w-8 h-8 rounded-full bg-white/85 hover:bg-white text-kk-brown flex items-center justify-center shadow transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        {{-- Banner / image side --}}
        <div class="relative min-h-[110px] md:min-h-[330px] overflow-hidden" style="background: linear-gradient(150deg, #8c5c34 0%, #4a2d1a 55%, #2d1810 100%);">
            @if($exit['image'])
                {{-- Same as the offer popup: the banner is contained over a blurred copy
                     of itself so the whole artwork shows, positioned and kept transparent
                     inline so .kk-media does not override it and so a missing file degrades
                     to the gradient behind, which stands on its own. --}}
                <div class="kk-media" style="position: absolute; inset: 0; background: transparent;">
                    <img class="kk-media__fill" src="{{ $exit['image'] }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                    <img src="{{ $exit['image'] }}" alt="Karmaa Kulture offer" loading="lazy" decoding="async">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            @endif
            <div class="relative h-full flex flex-col justify-center items-center text-center p-5 text-kk-cream">
                <span class="text-[10px] tracking-[0.32em] uppercase text-kk-cream/80">Limited Time</span>
                <p class="text-5xl md:text-6xl font-semibold leading-none mt-1.5" style="font-family:'Playfair Display',Georgia,serif;">10%</p>
                <p class="text-base tracking-[0.28em] uppercase mt-1">Off</p>
            </div>
        </div>

        {{-- Content side --}}
        <div class="p-5 sm:p-6 flex flex-col justify-center overflow-y-auto">
            {{-- Three outcomes, and the copy has to tell them apart. "Use CODE at
                 checkout" is a false instruction in the one case this feature exists
                 to create - the discount is already on the cart and there is nothing
                 to type. The two "saved" branches keep the code chip on screen as the
                 manual escape hatch, so a claim that cannot apply yet (empty cart,
                 minimum not met, someone else's address) still leaves the customer
                 something they can act on rather than a promise and then silence. --}}
            <div x-show="done" x-cloak class="text-center py-4">
                <div class="w-12 h-12 mx-auto rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-2.5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>

                <template x-if="state === 'applied'">
                    <div>
                        <p class="text-kk-brown font-semibold text-base" style="font-family:'Playfair Display',Georgia,serif;">Discount applied 🎉</p>
                        <p class="text-sm text-kk-text-muted mt-1">
                            <strong x-text="discountLabel"></strong> is already off your bag - nothing to type at checkout.
                        </p>
                    </div>
                </template>

                {{-- 'none' means record() wrote nothing at all - the popup was switched
                     off, or its code blanked, between this page rendering and the
                     click. Telling that customer their offer is saved would be two
                     lies in one sentence, so it gets its own branch. --}}
                <template x-if="state === 'none'">
                    <div>
                        <p class="text-kk-brown font-semibold text-base" style="font-family:'Playfair Display',Georgia,serif;">You are on the list</p>
                        <p class="text-sm text-kk-text-muted mt-1">This offer has closed, but we will let you know about the next one.</p>
                    </div>
                </template>

                <template x-if="state === 'saved'">
                    <div>
                        <p class="text-kk-brown font-semibold text-base" style="font-family:'Playfair Display',Georgia,serif;">Your offer is saved 🎉</p>
                        <p class="text-sm text-kk-text-muted mt-1" x-show="matchesAccount">
                            Saved to your account - applied automatically at checkout once your bag qualifies.
                        </p>
                        <p class="text-sm text-kk-text-muted mt-1" x-show="!matchesAccount">
                            Saved to <strong x-text="claimedEmail"></strong>. Sign in with that address at checkout and it applies automatically.
                        </p>
                        <p class="text-xs text-kk-text-muted mt-2">Or enter <strong x-text="code"></strong> yourself at checkout.</p>
                    </div>
                </template>

                <button type="button" @click="close()" class="mt-3.5 bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-2.5 px-5 rounded-lg text-sm tracking-[0.12em] uppercase transition">Continue Shopping</button>
            </div>

            {{-- The countdown used to tick to 0:00 and change nothing - the Claim
                 button stayed live, which teaches customers to ignore every timer on
                 the site. Closing the form is the honest fix, and it is client-side
                 only on purpose: the server must not reject a claim over a clock the
                 customer cannot audit, and the horizon that really governs a claim is
                 offer_claims.expires_at. --}}
            <div x-show="expired && !done" x-cloak class="text-center py-4">
                <p class="text-kk-brown font-semibold text-base" style="font-family:'Playfair Display',Georgia,serif;">This offer has closed</p>
                <p class="text-sm text-kk-text-muted mt-1">The countdown ran out - keep an eye out, we run these often.</p>
                <button type="button" @click="close()" class="mt-3.5 bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-2.5 px-5 rounded-lg text-sm tracking-[0.12em] uppercase transition">Continue Shopping</button>
            </div>

            <div x-show="!done && !expired">
                <h2 id="exit-popup-title" class="text-xl leading-tight text-kk-brown font-semibold" style="font-family:'Playfair Display',Georgia,serif;">{{ $exit['title'] }}</h2>
                <p class="text-[13px] text-kk-text-muted mt-1.5 mb-3 leading-relaxed">{{ $exit['subtitle'] }}</p>

                {{-- Countdown --}}
                <div class="flex items-center gap-2 mb-3 text-kk-brown">
                    <svg class="w-4 h-4 text-kk-tan-dark" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                    <span class="text-sm">Offer closes in</span>
                    <span class="font-mono font-bold text-base tabular-nums" x-text="timeLeft">{{ $exit['minutes'] }}:00</span>
                </div>

                {{-- Discount code --}}
                <div class="flex items-center justify-between gap-2 border-2 border-dashed border-kk-tan rounded-lg px-3.5 py-2.5 mb-3 bg-kk-cream-lighter">
                    <span class="text-base font-bold tracking-[0.16em] text-kk-brown" x-text="code">{{ $exit['code'] }}</span>
                    {{-- Driven by the address in the box, not by @auth. The field is
                         seeded with the account email but stays editable, so a signed-in
                         customer typing somebody else's address would otherwise be
                         promised "applied automatically" here and told the opposite by
                         the success panel a moment later. --}}
                    <span class="text-[10px] uppercase tracking-widest text-kk-text-muted"
                          x-text="willApplyAutomatically ? 'Applied automatically' : 'Apply at checkout'">Apply at checkout</span>
                </div>

                <form @submit.prevent="claim()" novalidate class="space-y-2.5">
                    <input type="email" x-model="form.email" required placeholder="Email address *" autocomplete="email" aria-label="Email address"
                        class="w-full rounded-lg border border-kk-cream-dark bg-kk-cream-lighter px-3.5 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan focus:bg-white transition">
                    <input type="tel" x-model="form.phone" inputmode="numeric" maxlength="10" placeholder="Mobile number" autocomplete="tel" aria-label="Mobile number"
                        class="w-full rounded-lg border border-kk-cream-dark bg-kk-cream-lighter px-3.5 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan focus:bg-white transition">

                    <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>

                    {{-- Stacked: side by side, the secondary label wrapped to two lines
                         and left the pair uneven once the panel narrowed. --}}
                    <div class="flex flex-col gap-2 pt-0.5">
                        <button type="submit" :disabled="submitting"
                            class="w-full bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-2.5 rounded-lg text-[13px] tracking-[0.12em] uppercase transition disabled:opacity-60">
                            <span x-show="!submitting">Claim Offer</span>
                            <span x-show="submitting" x-cloak>…</span>
                        </button>
                        <button type="button" @click="close()"
                            class="w-full bg-white border border-kk-brown text-kk-brown font-semibold py-2.5 rounded-lg text-[13px] tracking-[0.12em] uppercase hover:bg-kk-cream-lighter transition">
                            Continue Shopping
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
