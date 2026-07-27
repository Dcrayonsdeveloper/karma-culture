@php
    $exitEnabled  = (bool) \App\Models\Setting::get('exit_popup_enabled', true);
    $exitCode     = \App\Models\Setting::get('exit_popup_code', 'KARMAA10');
    $exitMinutes  = (int) \App\Models\Setting::get('exit_popup_minutes', 10);
    $exitTitle    = \App\Models\Setting::get('exit_popup_title', "Wait — Don't Miss 10% Off");
    $exitSubtitle = \App\Models\Setting::get('exit_popup_subtitle', 'Complete your order now and save. Apply the code below at checkout before it expires.');
    $exitImage    = \App\Models\Setting::get('exit_popup_image', '');
    if ($exitImage && !str_starts_with($exitImage, 'http') && !str_starts_with($exitImage, '/')) {
        $exitImage = asset('storage/' . $exitImage);
    }
@endphp

@if($exitEnabled)
<div
    x-data="exitPopup('{{ $exitCode }}', {{ $exitMinutes }})"
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
        class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 max-h-[94vh]"
    >
        <button type="button" @click="close()" aria-label="Close" class="absolute top-3 right-3 z-20 w-9 h-9 rounded-full bg-white/85 hover:bg-white text-kk-brown flex items-center justify-center shadow transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        {{-- Banner / image side --}}
        <div class="relative min-h-[150px] md:min-h-[460px] overflow-hidden" style="background: linear-gradient(150deg, #8c5c34 0%, #4a2d1a 55%, #2d1810 100%);">
            @if($exitImage)
                <img src="{{ $exitImage }}" alt="Karmaa Kulture offer" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            @endif
            <div class="relative h-full flex flex-col justify-center items-center text-center p-6 text-kk-cream">
                <span class="text-[11px] tracking-[0.32em] uppercase text-kk-cream/80">Limited Time</span>
                <p class="text-6xl md:text-7xl font-semibold leading-none mt-2" style="font-family:'Playfair Display',Georgia,serif;">10%</p>
                <p class="text-lg tracking-[0.28em] uppercase mt-1">Off</p>
            </div>
        </div>

        {{-- Content side --}}
        <div class="p-6 sm:p-8 flex flex-col justify-center overflow-y-auto">
            <div x-show="done" x-cloak class="text-center py-6">
                <div class="w-14 h-14 mx-auto rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-kk-brown font-semibold text-lg" style="font-family:'Playfair Display',Georgia,serif;">Your code is ready 🎉</p>
                <p class="text-sm text-kk-text-muted mt-1">Use <strong x-text="code"></strong> at checkout.</p>
                <button type="button" @click="close()" class="mt-4 bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-3 px-6 rounded-lg text-sm tracking-[0.12em] uppercase transition">Continue Shopping</button>
            </div>

            <div x-show="!done">
                <h2 id="exit-popup-title" class="text-2xl leading-tight text-kk-brown font-semibold" style="font-family:'Playfair Display',Georgia,serif;">{{ $exitTitle }}</h2>
                <p class="text-sm text-kk-text-muted mt-2 mb-4 leading-relaxed">{{ $exitSubtitle }}</p>

                {{-- Countdown --}}
                <div class="flex items-center gap-2 mb-4 text-kk-brown">
                    <svg class="w-4 h-4 text-kk-tan-dark" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                    <span class="text-sm">Offer expires in</span>
                    <span class="font-mono font-bold text-base tabular-nums" x-text="timeLeft">{{ $exitMinutes }}:00</span>
                </div>

                {{-- Discount code --}}
                <div class="flex items-center justify-between gap-2 border-2 border-dashed border-kk-tan rounded-lg px-4 py-3 mb-4 bg-kk-cream-lighter">
                    <span class="text-lg font-bold tracking-[0.18em] text-kk-brown" x-text="code">{{ $exitCode }}</span>
                    <span class="text-[11px] uppercase tracking-widest text-kk-text-muted">Apply at checkout</span>
                </div>

                <form @submit.prevent="claim()" novalidate class="space-y-3">
                    <input type="email" x-model="form.email" required placeholder="Email address *" autocomplete="email" aria-label="Email address"
                        class="w-full rounded-lg border border-kk-cream-dark bg-kk-cream-lighter px-4 py-3 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan focus:bg-white transition">
                    <input type="tel" x-model="form.phone" inputmode="numeric" maxlength="10" placeholder="Mobile number (optional)" autocomplete="tel" aria-label="Mobile number"
                        class="w-full rounded-lg border border-kk-cream-dark bg-kk-cream-lighter px-4 py-3 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan focus:bg-white transition">

                    <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <button type="submit" :disabled="submitting"
                            class="flex-1 bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-3.5 rounded-lg text-sm tracking-[0.12em] uppercase transition disabled:opacity-60">
                            <span x-show="!submitting">Claim Offer</span>
                            <span x-show="submitting" x-cloak>…</span>
                        </button>
                        <button type="button" @click="close()"
                            class="flex-1 bg-white border border-kk-brown text-kk-brown font-semibold py-3.5 rounded-lg text-sm tracking-[0.12em] uppercase hover:bg-kk-cream-lighter transition">
                            Continue Shopping
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
