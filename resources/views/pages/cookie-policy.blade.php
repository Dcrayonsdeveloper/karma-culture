<x-layouts.app>
    <x-slot name="title">Cookie Policy - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Cookie policy for {{ config('app.name') }}. Learn how we use cookies to improve your shopping experience.">
        <link rel="canonical" href="{{ url('/cookie-policy') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Cookie Policy', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full bg-amber-50 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Cookie Policy</h1>
                <p class="text-[13px] text-neutral-600 mt-2">Last updated: {{ \Carbon\Carbon::now()->format('F Y') }} &middot; How we use cookies and similar technologies on our website.</p>
            </div>

            {{-- What Are Cookies --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">What Are Cookies?</h2>
                <div class="space-y-2.5 text-[13px] text-neutral-600 leading-relaxed">
                    <p>Cookies are small text files that are placed on your device when you visit a website. They are widely used to make websites work, improve user experience, and provide information to the website owner.</p>
                    <p>Cookies are not harmful — they cannot carry viruses or install malware. They simply store small pieces of information that help the website remember your preferences and activity.</p>
                </div>
            </div>

            {{-- Essential Cookies --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-[15px] font-bold text-neutral-900">Essential Cookies</h2>
                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-green-100 text-green-700">Always Active</span>
                </div>
                <p class="text-[13px] text-neutral-600 mb-3">Required for the website to function properly. These cannot be disabled.</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Session cookies — keeping you logged in during your visit
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Shopping cart contents — preserving your selected items
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        CSRF security tokens — protecting you from cross-site attacks
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Cookie consent preference — storing your cookie choices
                    </li>
                </ul>
            </div>

            {{-- Functional + Analytics side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-[15px] font-bold text-neutral-900">Functional Cookies</h2>
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-[#6F9CA2]/10 text-[#5B878D]">Optional</span>
                    </div>
                    <p class="text-[13px] text-neutral-600 mb-3">Enhance your experience by remembering preferences and settings.</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                            <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Language and region preferences
                        </li>
                        <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                            <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Recently viewed products
                        </li>
                        <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                            <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Wishlist between sessions
                        </li>
                    </ul>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-[15px] font-bold text-neutral-900">Analytics Cookies</h2>
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-[#6F9CA2]/10 text-[#5B878D]">Optional</span>
                    </div>
                    <p class="text-[13px] text-neutral-600 mb-3">Help us understand how visitors use our website so we can improve it.</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                            <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Pages visited and time on site
                        </li>
                        <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                            <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Traffic sources and conversion tracking
                        </li>
                        <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                            <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Error monitoring and diagnostics
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Marketing Cookies --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-[15px] font-bold text-neutral-900">Marketing Cookies</h2>
                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700">Optional</span>
                </div>
                <p class="text-[13px] text-neutral-600 mb-3">Used to show relevant advertisements and measure campaign effectiveness.</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Retargeting ads on third-party platforms
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Social media pixel tracking
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Ad campaign attribution and measurement
                    </li>
                </ul>
            </div>

            {{-- Third-Party + Managing side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Third-Party Cookies</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">Some cookies are set by third-party services on our pages. We do not control these cookies — please refer to each third party's privacy policy for details on how they collect and use data.</p>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Managing Cookies</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">You can control cookies through your browser settings. Most browsers allow you to block or delete cookies. You can also browse in private/incognito mode to prevent cookies being saved after your session.</p>
                </div>
            </div>

            {{-- Warning --}}
            <div class="bg-warning-50 border border-warning-200 rounded-xl p-5 sm:p-6 mb-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 bg-warning-100 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-neutral-900 mb-1">Impact of Disabling Cookies</h2>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">Disabling certain cookies may affect the functionality of our website. Features such as staying logged in, keeping items in your cart, and remembering your preferences may not work correctly without essential cookies enabled.</p>
                    </div>
                </div>
            </div>

            {{-- Questions --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 text-center">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Questions About Cookies?</h2>
                <p class="text-[13px] text-neutral-600 mb-4">If you have questions about how we use cookies, our support team is here to help.</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Contact Us
                    </a>
                    <a href="{{ route('privacy') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                        Privacy Policy
                    </a>
                    <a href="{{ route('gdpr') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                        GDPR Compliance
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
