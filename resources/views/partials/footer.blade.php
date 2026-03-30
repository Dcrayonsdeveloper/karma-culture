<footer class="bg-[#222222] text-white mt-auto">
    <!-- Main footer -->
    <div class="py-10 lg:py-14">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 lg:gap-10">
                <!-- About -->
                <div class="col-span-2 lg:col-span-2">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 mb-4">
                        @php
                            $footerLogo = \App\Models\Setting::get('site_logo', '');
                            $footerAbout = \App\Models\Setting::get('footer_about', 'Adorable, comfortable, and stylish clothing for your little ones. Discover the perfect outfits for every occasion with ForeverKids.');
                        @endphp
                        @if($footerLogo)
                            <img src="{{ asset('storage/' . $footerLogo) }}" alt="{{ config('app.name') }}" class="h-10 object-contain brightness-0 invert">
                        @else
                            <img src="{{ asset('images/colorlogo.png') }}" alt="ForeverKids" class="h-10 object-contain brightness-0 invert">
                        @endif
                    </a>
                    <p class="text-neutral-300 text-sm mb-5 leading-relaxed max-w-sm">
                        {{ $footerAbout }}
                    </p>
                    <!-- Social Icons -->
                    <div class="flex gap-3">
                        @php
                            $socialFacebook = \App\Models\Setting::get('social_facebook', '#');
                            $socialInstagram = \App\Models\Setting::get('social_instagram', '#');
                            $socialTwitter = \App\Models\Setting::get('social_twitter', '#');
                            $socialYoutube = \App\Models\Setting::get('social_youtube', '#');
                            $socialTiktok = \App\Models\Setting::get('social_tiktok', '');
                            $socialPinterest = \App\Models\Setting::get('social_pinterest', '');
                        @endphp
                        @if($socialFacebook)
                            <a href="{{ $socialFacebook }}" class="w-9 h-9 bg-white/10 hover:bg-[#6F9CA2] text-white/70 hover:text-white rounded-full flex items-center justify-center transition-all" aria-label="Facebook" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                        @endif
                        @if($socialInstagram)
                            <a href="{{ $socialInstagram }}" class="w-9 h-9 bg-white/10 hover:bg-[#6F9CA2] text-white/70 hover:text-white rounded-full flex items-center justify-center transition-all" aria-label="Instagram" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                            </a>
                        @endif
                        @if($socialTwitter)
                            <a href="{{ $socialTwitter }}" class="w-9 h-9 bg-white/10 hover:bg-[#6F9CA2] text-white/70 hover:text-white rounded-full flex items-center justify-center transition-all" aria-label="X (Twitter)" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        @endif
                        @if($socialYoutube)
                            <a href="{{ $socialYoutube }}" class="w-9 h-9 bg-white/10 hover:bg-[#6F9CA2] text-white/70 hover:text-white rounded-full flex items-center justify-center transition-all" aria-label="YouTube" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        @endif
                        @if($socialTiktok)
                            <a href="{{ $socialTiktok }}" class="w-9 h-9 bg-white/10 hover:bg-[#6F9CA2] text-white/70 hover:text-white rounded-full flex items-center justify-center transition-all" aria-label="TikTok" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                            </a>
                        @endif
                        @if($socialPinterest)
                            <a href="{{ $socialPinterest }}" class="w-9 h-9 bg-white/10 hover:bg-[#6F9CA2] text-white/70 hover:text-white rounded-full flex items-center justify-center transition-all" aria-label="Pinterest" target="_blank">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641 0 12.017 0z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-semibold mb-4 text-white uppercase tracking-wider">Quick Links</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('about') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Contact Us</a></li>
                        <li><a href="{{ route('faq') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">FAQs</a></li>
                        <li><a href="{{ route('blog') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Blog</a></li>
                        <li><a href="{{ route('careers') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Careers</a></li>
                        <li><a href="{{ route('size-guide') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Size Guide</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="text-sm font-semibold mb-4 text-white uppercase tracking-wider">Customer Service</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('help') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Help Center</a></li>
                        <li><a href="{{ route('track-order') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Track Order</a></li>
                        <li><a href="{{ route('returns') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Returns & Refunds</a></li>
                        <li><a href="{{ route('shipping') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Shipping Info</a></li>
                    </ul>
                </div>

                <!-- Policies -->
                <div>
                    <h4 class="text-sm font-semibold mb-4 text-white uppercase tracking-wider">Policies</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('privacy') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Terms of Service</a></li>
                        <li><a href="{{ route('cookie-policy') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">Cookie Policy</a></li>
                        <li><a href="{{ route('gdpr') }}" class="text-neutral-300 hover:text-[#6F9CA2] transition-colors">GDPR Compliance</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsletter -->
    <div class="border-t border-white/10 py-8">
        <div class="container mx-auto px-4">
            <div class="max-w-xl mx-auto text-center" x-data="{ email: '', submitted: false, error: '' }">
                <h4 class="text-sm font-semibold text-white mb-1">Stay in the loop</h4>
                <p class="text-xs text-neutral-300 mb-3">New arrivals, exclusive deals & style tips — straight to your inbox.</p>
                <form x-show="!submitted" @submit.prevent="
                    error = '';
                    fetch('/newsletter/subscribe', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                        body: JSON.stringify({ email })
                    }).then(r => {
                        if (r.ok) { submitted = true; }
                        else { r.json().then(d => error = d.message || d.errors?.email?.[0] || 'Something went wrong'); }
                    }).catch(() => error = 'Something went wrong')
                " class="flex items-stretch gap-2 max-w-md mx-auto">
                    <input type="email" x-model="email" required placeholder="Your email address"
                           class="flex-1 min-w-0 text-sm px-4 py-2.5 bg-white/10 border border-white/10 rounded-lg text-white placeholder-neutral-500 focus:outline-none focus:border-[#6F9CA2]">
                    <button type="submit" class="shrink-0 text-sm font-semibold bg-[#F8931D] hover:bg-[#E07E0A] text-white px-5 py-2.5 rounded-lg transition-colors">
                        Subscribe
                    </button>
                </form>
                <p x-show="submitted" x-cloak class="text-sm text-[#6F9CA2] font-medium">Thanks for subscribing!</p>
                <p x-show="error" x-cloak class="text-xs text-red-400 mt-1" x-text="error"></p>
            </div>
        </div>
    </div>

    <!-- Bottom bar -->
    <div class="border-t border-white/10 py-5">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                <p class="text-xs text-neutral-300">
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'ForeverKids') }}. All rights reserved.
                </p>
                <div class="flex items-center gap-3">
                    {{-- Visa --}}
                    <svg class="h-6 w-auto opacity-30 hover:opacity-70 transition-opacity" viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="32" rx="4" fill="#fff"/><path d="M19.5 21h-2.7l1.7-10.5h2.7L19.5 21Zm11.2-10.2c-.5-.2-1.4-.4-2.4-.4-2.7 0-4.5 1.4-4.5 3.4 0 1.5 1.4 2.3 2.4 2.8 1 .5 1.4.8 1.4 1.3 0 .7-.8 1-1.6 1-1.1 0-1.6-.2-2.5-.5l-.3-.2-.4 2.2c.6.3 1.8.5 3 .5 2.8 0 4.7-1.4 4.7-3.5 0-1.2-.7-2.1-2.3-2.8-.9-.5-1.5-.8-1.5-1.3 0-.4.5-.9 1.5-.9.9 0 1.5.2 2 .4l.2.1.3-2.1ZM35 10.5h-2.1c-.7 0-1.1.2-1.4.8L27.8 21h2.8l.6-1.5h3.5l.3 1.5H37L35 10.5Zm-3.4 7 1.1-3 .3-.8.2.7.6 3.1h-2.2ZM16 10.5l-2.5 7.2-.3-1.3c-.5-1.6-2-3.4-3.7-4.3l2.4 9h2.9l4.3-10.5H16Z" fill="#1A1F71"/><path d="M12 10.5H7.8l-.1.3c3.4.9 5.7 3 6.6 5.5l-1-4.8c-.1-.7-.6-.9-1.3-1Z" fill="#F9A533"/></svg>
                    {{-- Mastercard --}}
                    <svg class="h-6 w-auto opacity-30 hover:opacity-70 transition-opacity" viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="32" rx="4" fill="#fff"/><circle cx="20" cy="16" r="9" fill="#EB001B"/><circle cx="28" cy="16" r="9" fill="#F79E1B"/><path d="M24 9.3a9 9 0 0 1 3.3 6.7A9 9 0 0 1 24 22.7 9 9 0 0 1 20.7 16 9 9 0 0 1 24 9.3Z" fill="#FF5F00"/></svg>
                    {{-- UPI --}}
                    <span class="text-[10px] font-bold text-neutral-300 bg-white/10 px-2 py-1 rounded">UPI</span>
                    {{-- COD --}}
                    <span class="text-[10px] font-bold text-neutral-300 bg-white/10 px-2 py-1 rounded">COD</span>
                </div>
            </div>
        </div>
    </div>
</footer>
