<x-layouts.app>
    <x-slot name="title">GDPR Compliance - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="GDPR compliance information for {{ config('app.name') }}. Learn about your data rights and how we protect your privacy.">
        <link rel="canonical" href="{{ url('/gdpr') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'GDPR Compliance', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full bg-primary-50 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">GDPR Compliance</h1>
                <p class="text-[13px] text-neutral-600 mt-2">General Data Protection Regulation — Your rights under EU/UK law.</p>
            </div>

            {{-- What is GDPR --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">What is GDPR?</h2>
                <p class="text-[13px] text-neutral-600 leading-relaxed">
                    The General Data Protection Regulation (GDPR) is a regulation in EU law on data protection and privacy. It gives individuals control over their personal data and simplifies the regulatory environment for international business. {{ \App\Models\Setting::get('site_name', 'ForeverKids') }} is committed to full compliance with all GDPR obligations.
                </p>
            </div>

            {{-- Data We Process --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Personal Data We Process</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Identification data</strong> — name, email, phone number</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Address data</strong> — billing and shipping addresses</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Transaction data</strong> — order history, payment records</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Technical data</strong> — IP address, browser type, cookies</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Communications</strong> — support tickets, enquiry messages</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Preference data</strong> — wishlist, product preferences</span>
                    </div>
                </div>
            </div>

            {{-- Legal Basis --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-4">Legal Basis for Processing</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Contract</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">Processing your orders and managing your account</p>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 bg-[#6F9CA2]/5 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Legal Obligation</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">Tax laws, accounting requirements and court orders</p>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Consent</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">Marketing emails (you can withdraw consent at any time)</p>
                    </div>
                </div>
            </div>

            {{-- Your Rights --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Your GDPR Rights</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Right to Access</strong> — request a copy of all personal data we hold about you</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Right to Rectification</strong> — correct inaccurate or incomplete data</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Right to Erasure</strong> — request deletion ("right to be forgotten")</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Right to Restrict</strong> — limit how we process your data</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Right to Portability</strong> — receive your data in a machine-readable format</span>
                    </div>
                    <div class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Right to Object</strong> — object to processing for direct marketing or legitimate interests</span>
                    </div>
                </div>
                <p class="text-[13px] text-neutral-600">To exercise any right, contact us via the form below. We will respond within <strong>30 days</strong>. You also have the right to lodge a complaint with your local data protection authority.</p>
            </div>

            {{-- Data Retention --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">Data Retention</h2>
                <p class="text-[13px] text-neutral-600 mb-3 leading-relaxed">We retain personal data only for as long as necessary for the purposes for which it was collected.</p>
                <div class="overflow-hidden rounded-xl border border-neutral-100">
                    <table class="w-full text-[13px]">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-neutral-600 text-xs uppercase">Data Type</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-neutral-600 text-xs uppercase">Retention Period</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr>
                                <td class="px-4 py-2.5 text-neutral-700 font-medium">Account data</td>
                                <td class="px-4 py-2.5 text-neutral-600">Duration of account + 2 years after closure</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 text-neutral-700 font-medium">Order records</td>
                                <td class="px-4 py-2.5 text-neutral-600">7 years (tax / accounting compliance)</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 text-neutral-700 font-medium">Payment data</td>
                                <td class="px-4 py-2.5 text-neutral-600">Not stored (processed by payment provider)</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 text-neutral-700 font-medium">Marketing consent</td>
                                <td class="px-4 py-2.5 text-neutral-600">Until you unsubscribe</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2.5 text-neutral-700 font-medium">Support communications</td>
                                <td class="px-4 py-2.5 text-neutral-600">3 years</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Third Parties + International side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Third-Party Processors</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">We use carefully selected service providers bound by GDPR-compliant data processing agreements — including payment gateways, shipping carriers, email providers, analytics services, and cloud infrastructure.</p>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <h2 class="text-[15px] font-bold text-neutral-900 mb-2">International Transfers</h2>
                    <p class="text-[13px] text-neutral-600 leading-relaxed">Where we transfer data outside the EEA or UK, we ensure appropriate safeguards are in place — such as standard contractual clauses or adequacy decisions.</p>
                </div>
            </div>

            {{-- Submit a request --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 text-center">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Submit a Data Request</h2>
                <p class="text-[13px] text-neutral-600 mb-4">To exercise your GDPR rights, contact us below. Please include <strong>"GDPR Request"</strong> in your message and specify which right you are exercising. We aim to respond within 30 days.</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Contact Us
                    </a>
                    <a href="{{ route('privacy') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                        Privacy Policy
                    </a>
                    <a href="{{ route('cookie-policy') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                        Cookie Policy
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
