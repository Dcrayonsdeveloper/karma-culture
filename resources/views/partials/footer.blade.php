<footer class="bg-kk-cream text-kk-brown mt-auto border-t border-kk-cream-dark">
    <!-- Main footer -->
    <div class="py-6 lg:py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6 lg:gap-8">
                <!-- About -->
                <div class="col-span-2 lg:col-span-2">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 mb-3">
                        @php
                            $footerLogo = \App\Models\Setting::get('site_logo', '');
                            $footerAbout = \App\Models\Setting::get('footer_about', 'Curated fashion for the modern individual. Discover timeless pieces crafted with care and devotion to our culture.');
                        @endphp
                        @if($footerLogo)
                            <img src="{{ asset('storage/' . $footerLogo) }}" alt="{{ config('app.name', 'Karmaa Kulture') }}" class="h-14 object-contain">
                        @else
                            <img src="{{ asset('images/karmaa-kulture-logo.png') }}" alt="Karmaa Kulture" class="h-14 object-contain">
                        @endif
                    </a>
                    <p class="text-kk-text-muted text-sm mb-4 leading-relaxed max-w-sm">
                        {{ $footerAbout }}
                    </p>
                    <!-- Share this page -->
                    @php
                        $kkShareUrl  = urlencode(url()->current());
                        $kkShareText = urlencode(\App\Models\Setting::get('site_name', config('app.name', 'Karmaa Kulture')));
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold uppercase tracking-widest text-kk-brown/70">Share</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $kkShareUrl }}" target="_blank" rel="noopener" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Share on Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82V14.706h-3.13v-3.622h3.13V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.464.099 2.795.143v3.24h-1.917c-1.504 0-1.795.715-1.795 1.764v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.593 1.323-1.324V1.325C24 .593 23.407 0 22.675 0z"/></svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ $kkShareUrl }}&text={{ $kkShareText }}" target="_blank" rel="noopener" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Share on Twitter">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723 10.054 10.054 0 01-3.127 1.184 4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.937 4.937 0 004.604 3.417 9.868 9.868 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.054 0 13.999-7.496 13.999-13.985 0-.21 0-.42-.015-.63A9.936 9.936 0 0024 4.59z"/></svg>
                        </a>
                        <a href="https://wa.me/?text={{ $kkShareText }}%20{{ $kkShareUrl }}" target="_blank" rel="noopener" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Share on WhatsApp">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        <a href="mailto:?subject={{ $kkShareText }}&body={{ $kkShareText }}%20{{ $kkShareUrl }}" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Share by Email">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xs font-semibold mb-4 text-kk-brown uppercase tracking-[0.18em]">Quick Links</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('about') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Contact Us</a></li>
                        <li><a href="{{ route('faq') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">FAQs</a></li>
                        <li><a href="{{ route('blog') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Blog</a></li>
                        <li><a href="{{ route('size-guide') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Size Guide</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="text-xs font-semibold mb-4 text-kk-brown uppercase tracking-[0.18em]">Customer Service</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('help') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Help Center</a></li>
                        <li><a href="{{ route('track-order') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Track Order</a></li>
                        <li><a href="{{ route('returns') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Returns &amp; Refunds</a></li>
                        <li><a href="{{ route('shipping') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Shipping Info</a></li>
                    </ul>
                </div>

                <!-- Policies -->
                <div>
                    <h4 class="text-xs font-semibold mb-4 text-kk-brown uppercase tracking-[0.18em]">Policies</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('privacy') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Terms of Service</a></li>
                        <li><a href="{{ route('cookie-policy') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar with brand mark -->
    <div class="border-t border-kk-cream-dark py-4 bg-kk-cream-light">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-kk-text-muted">
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'Karmaa Kulture') }}. All rights reserved.
                </p>
                <div class="flex items-center gap-3">
                    {{-- Visa --}}
                    <svg class="h-6 w-auto opacity-40 hover:opacity-80 transition-opacity" viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="32" rx="4" fill="#fff"/><path d="M19.5 21h-2.7l1.7-10.5h2.7L19.5 21Zm11.2-10.2c-.5-.2-1.4-.4-2.4-.4-2.7 0-4.5 1.4-4.5 3.4 0 1.5 1.4 2.3 2.4 2.8 1 .5 1.4.8 1.4 1.3 0 .7-.8 1-1.6 1-1.1 0-1.6-.2-2.5-.5l-.3-.2-.4 2.2c.6.3 1.8.5 3 .5 2.8 0 4.7-1.4 4.7-3.5 0-1.2-.7-2.1-2.3-2.8-.9-.5-1.5-.8-1.5-1.3 0-.4.5-.9 1.5-.9.9 0 1.5.2 2 .4l.2.1.3-2.1ZM35 10.5h-2.1c-.7 0-1.1.2-1.4.8L27.8 21h2.8l.6-1.5h3.5l.3 1.5H37L35 10.5Zm-3.4 7 1.1-3 .3-.8.2.7.6 3.1h-2.2ZM16 10.5l-2.5 7.2-.3-1.3c-.5-1.6-2-3.4-3.7-4.3l2.4 9h2.9l4.3-10.5H16Z" fill="#1A1F71"/></svg>
                    {{-- Mastercard --}}
                    <svg class="h-6 w-auto opacity-40 hover:opacity-80 transition-opacity" viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="32" rx="4" fill="#fff"/><circle cx="20" cy="16" r="9" fill="#EB001B"/><circle cx="28" cy="16" r="9" fill="#F79E1B"/><path d="M24 9.3a9 9 0 0 1 3.3 6.7A9 9 0 0 1 24 22.7 9 9 0 0 1 20.7 16 9 9 0 0 1 24 9.3Z" fill="#FF5F00"/></svg>
                    {{-- UPI --}}
                    <span class="text-[10px] font-bold text-kk-brown bg-kk-cream-lighter border border-kk-cream-dark px-2 py-1 rounded">UPI</span>
                    {{-- COD --}}
                    <span class="text-[10px] font-bold text-kk-brown bg-kk-cream-lighter border border-kk-cream-dark px-2 py-1 rounded">COD</span>
                </div>
            </div>
        </div>
    </div>
</footer>
