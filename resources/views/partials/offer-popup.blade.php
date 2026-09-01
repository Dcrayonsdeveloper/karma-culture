@php
    $offerEnabled  = (bool) \App\Models\Setting::get('offer_popup_enabled', true);
    $offerTitle    = \App\Models\Setting::get('offer_popup_title', 'Unlock 10% Off Your First Order');
    $offerSubtitle = \App\Models\Setting::get('offer_popup_subtitle', 'Join the Karmaa Kulture list for early access to new drops, private sales and styling notes.');
    $offerImage    = \App\Models\Setting::get('offer_popup_image', '');
    if ($offerImage && !str_starts_with($offerImage, 'http') && !str_starts_with($offerImage, '/')) {
        $offerImage = asset('storage/' . $offerImage);
    }
@endphp

@if($offerEnabled)
<div
    x-data="offerPopup()"
    x-cloak
    x-show="open"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="offer-popup-title"
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-trap.noscroll="open"
        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 max-h-[88vh]"
    >
        <button type="button" @click="close()" aria-label="Close" class="absolute top-3 right-3 z-20 w-9 h-9 rounded-full bg-white/85 hover:bg-white text-kk-brown flex items-center justify-center shadow transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        {{-- Image / brand side (top band on mobile, left column on desktop) --}}
        <div class="relative min-h-[110px] md:min-h-[330px] overflow-hidden" style="background: linear-gradient(150deg, #4a2d1a 0%, #2d1810 55%, #1f1109 100%);">
            @if($offerImage)
                <img src="{{ $offerImage }}" alt="Karmaa Kulture" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-black/55 to-transparent"></div>
            @endif
            <div class="relative h-full flex flex-col justify-end p-5 md:p-6 text-kk-cream">
                <span class="text-[11px] tracking-[0.32em] uppercase text-kk-tan mb-2">Members Only</span>
                <p class="hidden md:block text-3xl leading-none font-semibold" style="font-family:'Playfair Display',Georgia,serif;">10<span class="text-2xl align-top">%</span><br><span class="text-lg tracking-[0.2em] uppercase">Off</span></p>
            </div>
        </div>

        {{-- Form side --}}
        <div class="p-5 sm:p-6 flex flex-col justify-center overflow-y-auto">
            <div x-show="done" x-cloak class="text-center py-6">
                <div class="w-12 h-12 mx-auto rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-kk-brown font-semibold text-lg" style="font-family:'Playfair Display',Georgia,serif;">You're in! 🎉</p>
                <p class="text-sm text-kk-text-muted mt-1">Check your inbox &amp; WhatsApp for exciting offers.</p>
            </div>

            <div x-show="!done">
                <h2 id="offer-popup-title" class="text-xl sm:text-[22px] leading-tight text-kk-brown font-semibold" style="font-family:'Playfair Display',Georgia,serif;">{{ $offerTitle }}</h2>
                <p class="text-sm text-kk-text-muted mt-2 mb-5 leading-relaxed">{{ $offerSubtitle }}</p>

                <form @submit.prevent="submit()" novalidate class="space-y-3">
                    <div>
                        <label for="offer-name" class="sr-only">Name</label>
                        <input id="offer-name" type="text" x-model="form.name" placeholder="Your name (optional)" autocomplete="name"
                            class="w-full rounded-lg border border-kk-cream-dark bg-kk-cream-lighter px-3.5 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan focus:bg-white transition">
                    </div>
                    <div>
                        <label for="offer-email" class="sr-only">Email address</label>
                        <input id="offer-email" type="email" x-model="form.email" required placeholder="Email address *" autocomplete="email"
                            class="w-full rounded-lg border border-kk-cream-dark bg-kk-cream-lighter px-3.5 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan focus:bg-white transition">
                    </div>
                    <div>
                        <label for="offer-phone" class="sr-only">Mobile number</label>
                        <input id="offer-phone" type="tel" x-model="form.phone" required inputmode="numeric" maxlength="10" placeholder="Mobile number *" autocomplete="tel"
                            class="w-full rounded-lg border border-kk-cream-dark bg-kk-cream-lighter px-3.5 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan focus:bg-white transition">
                    </div>

                    <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>

                    <button type="submit" :disabled="submitting"
                        class="w-full bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-2.5 rounded-lg text-sm tracking-[0.14em] uppercase transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Get Exciting Offers</span>
                        <span x-show="submitting" x-cloak>Submitting…</span>
                    </button>

                    <p class="text-[11px] text-kk-text-muted text-center leading-relaxed pt-1">
                        Offers shared via <strong>Email</strong> &amp; <strong>WhatsApp</strong>. No spam - unsubscribe anytime.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
