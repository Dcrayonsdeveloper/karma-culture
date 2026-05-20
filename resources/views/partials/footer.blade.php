<footer class="bg-kk-cream text-kk-brown mt-auto border-t border-kk-cream-dark">
    <!-- Main footer -->
    <div class="py-10 lg:py-14">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 lg:gap-10">
                <!-- About -->
                <div class="col-span-2 lg:col-span-2">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 mb-4">
                        @php
                            $footerLogo = \App\Models\Setting::get('site_logo', '');
                            $footerAbout = \App\Models\Setting::get('footer_about', 'Curated fashion for the modern individual. Discover timeless pieces crafted with care and devotion to our culture.');
                        @endphp
                        @if($footerLogo)
                            <img src="{{ asset('storage/' . $footerLogo) }}" alt="{{ config('app.name', 'Karmaa Kulture') }}" class="h-12 object-contain">
                        @else
                            <img src="{{ asset('images/karmaa-kulture-logo.png') }}" alt="Karmaa Kulture" class="h-12 object-contain">
                        @endif
                    </a>
                    <p class="text-kk-text-muted text-sm mb-5 leading-relaxed max-w-sm">
                        {{ $footerAbout }}
                    </p>
                    <!-- Social Icons -->
                    <div class="flex gap-3">
                        @php
                            $socialFacebook  = \App\Models\Setting::get('social_facebook', '#');
                            $socialInstagram = \App\Models\Setting::get('social_instagram', '#');
                            $socialYoutube   = \App\Models\Setting::get('social_youtube', '#');
                            $socialTiktok    = \App\Models\Setting::get('social_tiktok', '');
                        @endphp
                        @if($socialFacebook)
                            <a href="{{ $socialFacebook }}" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Facebook" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                        @endif
                        @if($socialInstagram)
                            <a href="{{ $socialInstagram }}" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="Instagram" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                            </a>
                        @endif
                        @if($socialYoutube)
                            <a href="{{ $socialYoutube }}" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="YouTube" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        @endif
                        @if($socialTiktok)
                            <a href="{{ $socialTiktok }}" class="w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown hover:text-kk-cream rounded-full flex items-center justify-center transition-all border border-kk-cream-dark" aria-label="TikTok" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
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
                        <li><a href="{{ route('gdpr') }}" class="text-kk-text-muted hover:text-kk-tan-dark transition-colors">GDPR Compliance</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar with brand mark -->
    <div class="border-t border-kk-cream-dark py-6 bg-kk-cream-light">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-kk-text-muted">
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'Karmaa Kulture') }}. All rights reserved.
                </p>
                <p class="text-sm font-semibold tracking-[0.18em] uppercase text-kk-brown" style="font-family: 'Playfair Display', Georgia, serif; letter-spacing: 0.12em;">
                    {{ \App\Models\Setting::get('site_name', 'Karmaa Kulture') }}
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
