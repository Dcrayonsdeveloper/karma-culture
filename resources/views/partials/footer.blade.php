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
                    <!-- Follow Us - Instagram, Facebook, Twitter, LinkedIn (admin-configurable) -->
                    @php
                        // Only the accounts the owner has actually set, and every platform
                        // the admin offers. Unset links used to fall back to the network's
                        // own home page, so "Follow us on Instagram" opened instagram.com
                        // rather than this shop — worse than showing no icon at all. And
                        // YouTube, TikTok and Pinterest were saveable in the admin but
                        // never rendered here, so setting them did nothing.
                        $kkSocialIcons = [
                            'instagram' => ['label' => 'Instagram', 'box' => '0 0 24 24', 'size' => 'w-4 h-4', 'path' => 'M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.13 1.38C1.35 2.68.93 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.13.67.66 1.34 1.08 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.13-1.38.66-.67 1.08-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.93 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0Zm0 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84Zm0 10.15A4 4 0 1 1 16 12a4 4 0 0 1-4 4Zm6.41-10.4a1.44 1.44 0 1 0 1.44 1.44 1.44 1.44 0 0 0-1.44-1.44Z'],
                            'facebook'  => ['label' => 'Facebook', 'box' => '0 0 24 24', 'size' => 'w-4 h-4', 'path' => 'M22.675 0h-21.35C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82V14.706h-3.13v-3.622h3.13V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.464.099 2.795.143v3.24h-1.917c-1.504 0-1.795.715-1.795 1.764v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.593 1.323-1.324V1.325C24 .593 23.407 0 22.675 0z'],
                            'twitter'   => ['label' => 'X', 'box' => '0 0 24 24', 'size' => 'w-3.5 h-3.5', 'path' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
                            'linkedin'  => ['label' => 'LinkedIn', 'box' => '0 0 24 24', 'size' => 'w-4 h-4', 'path' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
                            'youtube'   => ['label' => 'YouTube', 'box' => '0 0 24 24', 'size' => 'w-4 h-4', 'path' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'],
                            'tiktok'    => ['label' => 'TikTok', 'box' => '0 0 24 24', 'size' => 'w-3.5 h-3.5', 'path' => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z'],
                            'pinterest' => ['label' => 'Pinterest', 'box' => '0 0 24 24', 'size' => 'w-4 h-4', 'path' => 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z'],
                        ];

                        $kkSocials = [];
                        foreach ($kkSocialIcons as $kkKey => $kkIcon) {
                            $kkUrl = trim((string) \App\Models\Setting::get('social_' . $kkKey, ''));
                            if ($kkUrl !== '' && $kkUrl !== '#') {
                                $kkSocials[$kkKey] = $kkIcon + ['url' => $kkUrl];
                            }
                        }
                    @endphp
                    @php
                        // kk-social-link carries the hover colour in app.css. Tailwind's
                        // hover:text-* loses to the storefront's generic `a:hover` rule on
                        // specificity, which left the icon dark on the dark hover circle.
                        $kkSocialClass = 'kk-social-link w-9 h-9 bg-kk-cream-lighter hover:bg-kk-brown text-kk-brown rounded-full flex items-center justify-center transition-all border border-kk-cream-dark';
                    @endphp
                    @if(!empty($kkSocials))
                    <div class="flex items-center gap-3 mt-1 flex-wrap">
                        @foreach($kkSocials as $kkSocial)
                            <a href="{{ $kkSocial['url'] }}" target="_blank" rel="noopener"
                               class="{{ $kkSocialClass }}" aria-label="Follow us on {{ $kkSocial['label'] }}">
                                <svg class="{{ $kkSocial['size'] }}" fill="currentColor" viewBox="{{ $kkSocial['box'] }}" aria-hidden="true"><path d="{{ $kkSocial['path'] }}"/></svg>
                            </a>
                        @endforeach
                    </div>
                    @endif

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
