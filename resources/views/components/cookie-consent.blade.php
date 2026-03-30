{{--
    Cookie Consent Banner
    - Stores consent in localStorage under key 'fk_cookie_consent'
    - Values: 'all' | 'essential' | null (not yet decided)
    - GA4 and FB Pixel are only initialised after 'all' consent is given
      (or immediately on page load if already consented)
--}}
<div x-data="cookieConsent()" x-cloak>
    {{-- Banner --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-0 left-0 right-0 z-[100] bg-white border-t border-neutral-200 shadow-lg"
         role="dialog" aria-label="Cookie consent">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-neutral-700 leading-relaxed">
                        We use cookies to personalise content, run analytics, and show relevant ads.
                        Read our <a href="{{ route('cookie-policy') }}" class="underline text-[#6F9CA2] hover:text-[#5B878D]">Cookie Policy</a>
                        and <a href="{{ route('privacy') }}" class="underline text-[#6F9CA2] hover:text-[#5B878D]">Privacy Policy</a>.
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="acceptEssential()"
                            class="text-sm text-neutral-600 hover:text-neutral-900 px-4 py-2 border border-neutral-300 rounded-lg transition-colors whitespace-nowrap">
                        Essential only
                    </button>
                    <button @click="acceptAll()"
                            class="text-sm font-semibold text-white bg-[#F8931D] hover:bg-[#E07E0A] px-5 py-2 rounded-lg transition-colors whitespace-nowrap">
                        Accept all
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        show: false,

        init() {
            const consent = localStorage.getItem('fk_cookie_consent');
            if (!consent) {
                // Delay slightly so banner doesn't flash on initial paint
                setTimeout(() => { this.show = true; }, 800);
            } else if (consent === 'all') {
                this.loadAnalytics();
            }
        },

        acceptAll() {
            localStorage.setItem('fk_cookie_consent', 'all');
            this.show = false;
            this.loadAnalytics();
        },

        acceptEssential() {
            localStorage.setItem('fk_cookie_consent', 'essential');
            this.show = false;
        },

        loadAnalytics() {
            @php
                // DB settings take priority; fall back to .env/config
                $ga4Id   = \App\Models\Setting::get('google_analytics_id')   ?: config('services.ga4.measurement_id');
                $gtmId   = \App\Models\Setting::get('google_tag_manager_id') ?: config('services.gtm.id');
                $fbPixel = \App\Models\Setting::get('facebook_pixel_id')     ?: config('services.facebook.pixel_id');
                // Sanitize — only allow safe identifier characters
                $ga4Id   = preg_replace('/[^A-Z0-9\-]/i', '', (string) $ga4Id);
                $gtmId   = preg_replace('/[^A-Z0-9\-]/i', '', (string) $gtmId);
                $fbPixel = preg_replace('/[^0-9]/', '', (string) $fbPixel);
            @endphp

            @if(!empty($ga4Id))
            if (!window._ga4Loaded) {
                window._ga4Loaded = true;
                const s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}';
                document.head.appendChild(s);
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                window.gtag = gtag;
                gtag('js', new Date());
                gtag('config', '{{ $ga4Id }}');
            }
            @endif

            @if(!empty($gtmId))
            if (!window._gtmLoaded) {
                window._gtmLoaded = true;
                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
                j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
                f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','{{ $gtmId }}');
            }
            @endif

            @if(!empty($fbPixel))
            if (!window._fbLoaded) {
                window._fbLoaded = true;
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window,document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{{ $fbPixel }}');
                fbq('track', 'PageView');
            }
            @endif

            // Notify page-level tracking scripts that analytics are now available
            window.dispatchEvent(new Event('analytics:loaded'));
        }
    }
}
</script>
