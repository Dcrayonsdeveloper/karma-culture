<x-layouts.app>
    <x-slot name="title">Terms of Service - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Terms of service for {{ config('app.name') }}. Read our terms and conditions for shopping kids' clothing online.">
        <link rel="canonical" href="{{ url('/terms-of-service') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Terms of Service', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Terms of Service</h1>
                <p class="text-[13px] text-neutral-600 mt-2">Last updated: {{ \Carbon\Carbon::now()->format('F Y') }} &middot; Please read these terms carefully before using our services.</p>
            </div>

            {{-- Acceptance --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Acceptance of Terms</h2>
                <p class="text-[13px] text-neutral-600 leading-relaxed">
                    By accessing or using the {{ \App\Models\Setting::get('site_name', 'ForeverKids') }} website or placing an order, you confirm that you have read, understood, and agree to be bound by these Terms of Service and our Privacy Policy. If you do not agree, please do not use our services.
                </p>
            </div>

            {{-- Use of Website --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Use of Our Website</h2>
                <p class="text-[13px] text-neutral-600 mb-3">You are granted a limited, non-exclusive, non-transferable licence to access and use our website for personal, non-commercial purposes, subject to these terms. You agree to:</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Provide accurate, current, and complete information when creating an account or placing an order
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Maintain the security of your account credentials and notify us immediately of any unauthorised use
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Use the website in compliance with all applicable laws and regulations
                    </li>
                </ul>
            </div>

            {{-- Prohibited Conduct --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Prohibited Conduct</h2>
                <p class="text-[13px] text-neutral-600 mb-3">You must not use our website to:</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-danger-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Violate any applicable law, regulation, or third-party rights
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-danger-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Submit false, misleading, or fraudulent orders or information
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-danger-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Attempt to gain unauthorised access to our systems or other users' accounts
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-danger-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Transmit spam, malware, or any harmful or disruptive content
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-danger-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Scrape, copy, or reproduce our content without written permission
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-danger-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Impersonate any person or entity, or falsely claim affiliation with us
                    </li>
                </ul>
            </div>

            {{-- Orders & Payments --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Orders & Payments</h2>
                <div class="space-y-2.5 text-[13px] text-neutral-600 leading-relaxed">
                    <p>All orders are subject to availability and confirmation. We reserve the right to refuse or cancel any order for any reason, including pricing errors or suspected fraudulent activity. Full payment is required before items are dispatched. Prices are inclusive of applicable taxes unless stated otherwise.</p>
                </div>
            </div>

            {{-- IP + Disclaimer side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Intellectual Property</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">All content on this website — including text, images, logos, and design — is the property of {{ \App\Models\Setting::get('site_name', 'ForeverKids') }} and is protected by copyright and trademark laws. Unauthorised use is strictly prohibited.</p>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Product Information</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">We strive for accuracy in all product descriptions and pricing. However, we do not warrant that all information is error-free. We reserve the right to correct any errors and update information at any time without prior notice.</p>
                </div>
            </div>

            {{-- Disclaimer warning --}}
            <div class="bg-warning-50 border border-warning-200 rounded-xl p-5 sm:p-6 mb-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 bg-warning-100 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-neutral-900 mb-1">Disclaimer of Warranties</h2>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">Our website and services are provided "as is" without warranties of any kind, either express or implied. We do not warrant that the website will be uninterrupted, error-free, or free of viruses. To the fullest extent permitted by law, we disclaim all warranties.</p>
                    </div>
                </div>
            </div>

            {{-- Limitation of Liability --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Limitation of Liability</h2>
                <p class="text-[13px] text-neutral-600 leading-relaxed">To the maximum extent permitted by law, {{ \App\Models\Setting::get('site_name', 'ForeverKids') }} shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our website or services. Our total liability shall not exceed the amount paid by you for the specific product or service giving rise to the claim.</p>
            </div>

            {{-- Governing Law + Changes side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Governing Law</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">These Terms shall be governed by and construed in accordance with applicable law. Any disputes shall be resolved through the courts of the applicable jurisdiction, unless otherwise agreed.</p>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Changes to Terms</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">We reserve the right to modify these Terms at any time. Changes take effect immediately upon posting. Continued use of our website after changes constitutes your acceptance of the new terms.</p>
                </div>
            </div>

            {{-- Contact --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 text-center">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Questions?</h2>
                <p class="text-[13px] text-neutral-600 mb-4">If you have questions about these Terms of Service, please contact our support team.</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Contact Us
                    </a>
                    <a href="{{ route('privacy') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                        Privacy Policy
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
