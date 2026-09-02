@php
    use App\Support\PopupSettings;

    $exit = PopupSettings::all(PopupSettings::EXIT);
@endphp

@if($exit['enabled'])
<div
    x-data="exitPopup(@js($exit['code']), {{ $exit['minutes'] }})"
    x-cloak
    x-show="open"
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
        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:grid md:grid-cols-2 max-h-[90vh]"
    >
        <button type="button" @click="close()" aria-label="Close" class="absolute top-2.5 right-2.5 z-20 w-10 h-10 sm:w-8 sm:h-8 rounded-full bg-white/85 hover:bg-white text-kk-brown flex items-center justify-center shadow transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        {{-- Banner / image side --}}
        <div class="relative shrink-0 min-h-[110px] md:min-h-[330px] overflow-hidden" style="background: linear-gradient(150deg, #8c5c34 0%, #4a2d1a 55%, #2d1810 100%);">
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
        {{-- my-auto on the visible child, not justify-center here: a centred flex
             child that overflows is clipped at the top with no way to scroll to it,
             auto margins collapse to zero and let it scroll from the title. --}}
        <div class="p-5 sm:p-6 flex flex-col overflow-y-auto">
            <div x-show="done" x-cloak class="my-auto text-center py-4">
                <div class="w-12 h-12 mx-auto rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-2.5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-kk-brown font-semibold text-base" style="font-family:'Playfair Display',Georgia,serif;">Your code is ready 🎉</p>
                <p class="text-sm text-kk-text-muted mt-1">Use <strong x-text="code"></strong> at checkout.</p>
                <button type="button" @click="close()" class="mt-3.5 bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-2.5 px-5 rounded-lg text-sm tracking-[0.12em] uppercase transition">Continue Shopping</button>
            </div>

            <div x-show="!done" class="my-auto">
                <h2 id="exit-popup-title" class="text-xl leading-tight text-kk-brown font-semibold" style="font-family:'Playfair Display',Georgia,serif;">{{ $exit['title'] }}</h2>
                <p class="text-[13px] text-kk-text-muted mt-1.5 mb-3 leading-relaxed">{{ $exit['subtitle'] }}</p>

                {{-- Countdown --}}
                <div class="flex items-center gap-2 mb-3 text-kk-brown">
                    <svg class="w-4 h-4 text-kk-tan-dark" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                    <span class="text-sm">Offer expires in</span>
                    <span class="font-mono font-bold text-base tabular-nums" x-text="timeLeft">{{ $exit['minutes'] }}:00</span>
                </div>

                {{-- Discount code --}}
                <div class="flex flex-wrap items-center justify-between gap-2 border-2 border-dashed border-kk-tan rounded-lg px-3.5 py-2.5 mb-3 bg-kk-cream-lighter">
                    <span class="text-base font-bold tracking-[0.16em] text-kk-brown" x-text="code">{{ $exit['code'] }}</span>
                    <span class="text-[10px] uppercase tracking-widest text-kk-text-muted">Apply at checkout</span>
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
