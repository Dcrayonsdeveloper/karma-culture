<x-layouts.app>
    <x-slot name="title">Privacy Policy - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Read the privacy policy of {{ config('app.name') }}. Learn how we collect, use, and protect your personal information.">
        <link rel="canonical" href="{{ url('/privacy-policy') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Privacy Policy', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full bg-[#6F9CA2]/5 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Privacy Policy</h1>
                <p class="text-[13px] text-neutral-600 mt-2">Last updated: {{ \Carbon\Carbon::now()->format('F Y') }} &middot; We are committed to protecting your personal data.</p>
            </div>

            {{-- Information We Collect --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Information We Collect</h2>
                <p class="text-[13px] text-neutral-600 mb-3">We collect information you provide directly and data collected automatically when you use our services:</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Account information</strong> — name, email address, password, phone number</span>
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Order information</strong> — billing/shipping addresses, payment details, products purchased</span>
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Usage data</strong> — pages visited, time spent, device type, IP address, browser type</span>
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Communications</strong> — messages sent via contact forms or support tickets</span>
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Preference data</strong> — wishlist, product reviews, marketing preferences</span>
                    </li>
                </ul>
            </div>

            {{-- How We Use It --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">How We Use Your Information</h2>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Process and fulfil orders, send order confirmations and shipping updates
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Manage your account and provide customer support
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Send promotional emails and newsletters (you may unsubscribe at any time)
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Detect and prevent fraud, abuse, and security incidents
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Analyse and improve website performance and user experience
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Comply with legal obligations and enforce our terms
                    </li>
                </ul>
            </div>

            {{-- Sharing --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Information Sharing & Disclosure</h2>
                <p class="text-[13px] text-neutral-600 mb-3 leading-relaxed">We do not sell, trade, or rent your personal information to third parties. We may share data only in these circumstances:</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Service providers</strong> — payment processors, shipping carriers, email services (all under data processing agreements)</span>
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Legal requirements</strong> — when required by law, court order, or government authority</span>
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Business transfers</strong> — in the event of a merger, acquisition, or sale of assets</span>
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>With your consent</strong> — for any other purpose with your explicit permission</span>
                    </li>
                </ul>
            </div>

            {{-- Security + Retention side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Data Security</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">We implement SSL/TLS encryption, secure storage, and regular security audits. Payment information is handled by PCI-DSS compliant providers and never stored on our servers.</p>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Data Retention</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">Data is retained while your account is active or as needed for services. Order records are kept for 7 years for legal compliance. You can request deletion at any time.</p>
                </div>
            </div>

            {{-- Your Rights --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Your Rights & Choices</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Access</strong> — request a copy of your data</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Correction</strong> — fix inaccurate data</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Deletion</strong> — request erasure of your account and data</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Portability</strong> — receive your data in a usable format</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Opt-out</strong> — unsubscribe from marketing emails at any time</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-[#6F9CA2] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Object</strong> — to certain processing activities</span>
                    </div>
                </div>
                <p class="text-[13px] text-neutral-600 mt-3">See our <a href="{{ route('gdpr') }}" class="text-primary-600 hover:text-primary-700 font-medium">GDPR Compliance</a> page for full details on your rights.</p>
            </div>

            {{-- Children notice --}}
            <div class="bg-[#6F9CA2]/5 border border-[#6F9CA2]/20 rounded-xl p-5 sm:p-6 mb-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 bg-[#6F9CA2]/10 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-neutral-900 mb-1">Children's Privacy</h2>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">Our services are not directed to children under 13. We do not knowingly collect personal information from children. If you believe a child has provided us with personal data, please contact us and we will promptly delete it.</p>
                    </div>
                </div>
            </div>

            {{-- Questions --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 text-center">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Questions About This Policy?</h2>
                <p class="text-[13px] text-neutral-600 mb-4">If you have any questions or want to exercise your rights, contact us and we will respond promptly.</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Contact Us
                    </a>
                    <a href="{{ route('cookie-policy') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                        Cookie Policy
                    </a>
                    <a href="{{ route('gdpr') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                        GDPR Compliance
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
