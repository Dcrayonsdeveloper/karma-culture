@php
    $offerEnabled  = (bool) \App\Models\Setting::get('offer_popup_enabled', true);
    $offerTitle    = \App\Models\Setting::get('offer_popup_title', 'Unlock Exciting Offers!');
    $offerSubtitle = \App\Models\Setting::get('offer_popup_subtitle', 'Join our list and be the first to hear about exclusive deals and new drops.');
@endphp

@if($offerEnabled)
<div
    x-data="offerPopup()"
    x-cloak
    x-show="open"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="offer-popup-title"
>
    <!-- Backdrop -->
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/60" @click="close()"></div>

    <!-- Card -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-trap.noscroll="open"
        class="relative w-full max-w-md bg-kk-cream-lighter rounded-2xl shadow-2xl overflow-hidden border border-kk-cream-dark"
    >
        <button type="button" @click="close()" aria-label="Close" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-kk-cream/80 hover:bg-kk-cream text-kk-brown flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <!-- Header band -->
        <div class="bg-kk-brown text-kk-cream px-6 pt-7 pb-6 text-center">
            <span class="inline-block text-[11px] tracking-[0.3em] uppercase text-kk-tan mb-2">Members Only</span>
            <h2 id="offer-popup-title" class="text-2xl font-semibold leading-tight" style="font-family: 'Playfair Display', Georgia, serif;">{{ $offerTitle }}</h2>
            <p class="text-sm text-kk-cream/80 mt-2">{{ $offerSubtitle }}</p>
        </div>

        <!-- Body -->
        <div class="px-6 py-6">
            {{-- Success state --}}
            <div x-show="done" x-cloak class="text-center py-6">
                <div class="w-14 h-14 mx-auto rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-kk-brown font-semibold">You're in! 🎉</p>
                <p class="text-sm text-kk-text-muted mt-1">We'll share exciting offers with you soon.</p>
            </div>

            {{-- Form --}}
            <form x-show="!done" @submit.prevent="submit()" novalidate class="space-y-3">
                <div>
                    <label for="offer-name" class="sr-only">Name</label>
                    <input id="offer-name" type="text" x-model="form.name" placeholder="Your name (optional)" autocomplete="name"
                        class="w-full rounded-lg border border-kk-cream-dark bg-white px-4 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan">
                </div>
                <div>
                    <label for="offer-email" class="sr-only">Email address</label>
                    <input id="offer-email" type="email" x-model="form.email" required placeholder="Email address *" autocomplete="email"
                        class="w-full rounded-lg border border-kk-cream-dark bg-white px-4 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan">
                </div>
                <div>
                    <label for="offer-phone" class="sr-only">Mobile number</label>
                    <input id="offer-phone" type="tel" x-model="form.phone" required inputmode="numeric" maxlength="10" placeholder="Mobile number *" autocomplete="tel"
                        class="w-full rounded-lg border border-kk-cream-dark bg-white px-4 py-2.5 text-sm text-kk-brown focus:outline-none focus:ring-2 focus:ring-kk-tan">
                </div>

                <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>

                <button type="submit" :disabled="submitting"
                    class="w-full bg-kk-brown hover:bg-kk-brown-dark text-kk-cream font-semibold py-3 rounded-lg text-sm tracking-[0.12em] uppercase transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Get Exciting Offers</span>
                    <span x-show="submitting" x-cloak>Submitting…</span>
                </button>

                <p class="text-[11px] text-kk-text-muted text-center leading-relaxed pt-1">
                    Offers will be shared via <strong>Email</strong> &amp; <strong>WhatsApp</strong>. No spam — unsubscribe anytime.
                </p>
            </form>
        </div>
    </div>
</div>

<script>
    function offerPopup() {
        return {
            open: false,
            submitting: false,
            done: false,
            error: '',
            form: { name: '', email: '', phone: '' },
            seenKey: 'kk_offer_popup_seen',
            init() {
                if (this.alreadySeen()) return;
                // Show shortly after first homepage visit.
                window.setTimeout(() => { this.open = true; this.markSeen(); }, 4000);
            },
            alreadySeen() {
                try { if (localStorage.getItem(this.seenKey)) return true; } catch (e) {}
                return document.cookie.split('; ').some((c) => c.startsWith(this.seenKey + '='));
            },
            markSeen() {
                try { localStorage.setItem(this.seenKey, '1'); } catch (e) {}
                document.cookie = this.seenKey + '=1; path=/; max-age=' + (60 * 60 * 24 * 30) + '; SameSite=Lax';
            },
            close() { this.open = false; },
            validPhone() { return /^[0-9]{10}$/.test((this.form.phone || '').replace(/\D/g, '')); },
            csrf() {
                const el = document.querySelector('meta[name="csrf-token"]');
                return el ? el.getAttribute('content') : '';
            },
            async submit() {
                this.error = '';
                if (!this.form.email) { this.error = 'Please enter your email address.'; return; }
                if (!this.validPhone()) { this.error = 'Please enter a valid 10-digit mobile number.'; return; }
                this.submitting = true;
                try {
                    const res = await fetch('{{ route('newsletter.subscribe') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({
                            name: this.form.name,
                            email: this.form.email,
                            phone: this.form.phone,
                            source: 'offer_popup',
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        this.done = true;
                        window.setTimeout(() => this.close(), 2800);
                    } else {
                        this.error = data.message || 'Something went wrong. Please try again.';
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
@endif
