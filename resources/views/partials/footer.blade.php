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
                    <!-- Follow Us (social profiles, admin-configurable) -->
                    @php
                        $kkSocials = [
                            'instagram' => \App\Models\Setting::get('social_instagram', '') ?: 'https://www.instagram.com/',
                            'facebook'  => \App\Models\Setting::get('social_facebook', ''),
                            'youtube'   => \App\Models\Setting::get('social_youtube', ''),
                        ];
                    @endphp
                    <div class="flex items-center gap-3 mt-1">
                        {{-- Social profiles (admin-configurable), clean icon row, no heading --}}
                        <a href="{{ $kkSocials['instagram'] }}" target="_blank" rel="noopener" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Follow us on Instagram">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.13 1.38C1.35 2.68.93 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.13.67.66 1.34 1.08 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.13-1.38.66-.67 1.08-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.93 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0Zm0 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84Zm0 10.15A4 4 0 1 1 16 12a4 4 0 0 1-4 4Zm6.41-10.4a1.44 1.44 0 1 0 1.44 1.44 1.44 1.44 0 0 0-1.44-1.44Z"/></svg>
                        </a>
                        @if($kkSocials['facebook'])
                        <a href="{{ $kkSocials['facebook'] }}" target="_blank" rel="noopener" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Follow us on Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82V14.706h-3.13v-3.622h3.13V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.464.099 2.795.143v3.24h-1.917c-1.504 0-1.795.715-1.795 1.764v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.593 1.323-1.324V1.325C24 .593 23.407 0 22.675 0z"/></svg>
                        </a>
                        @endif
                        @if($kkSocials['youtube'])
                        <a href="{{ $kkSocials['youtube'] }}" target="_blank" rel="noopener" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Follow us on YouTube">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.51A3.02 3.02 0 0 0 .5 6.2 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.8 3.02 3.02 0 0 0 2.12 2.14C4.5 20.45 12 20.45 12 20.45s7.5 0 9.38-.51a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.8ZM9.55 15.57V8.43L15.82 12l-6.27 3.57Z"/></svg>
                        </a>
                        @endif
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
