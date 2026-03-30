<x-layouts.app>
    <x-slot name="title">Help Center - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Need help? Visit the {{ config('app.name') }} help center for answers about orders, shipping, returns, and account management.">
        <link rel="canonical" href="{{ url('/help') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Help Center', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8 sm:mb-10">
                <div class="w-14 h-14 mx-auto rounded-full bg-primary-50 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Help Center</h1>
                <p class="text-[13px] text-neutral-600 mt-2">How can we help you today?</p>
            </div>

            <!-- Search -->
            <div class="max-w-lg mx-auto mb-8 sm:mb-10">
                <form action="{{ route('faq') }}" method="GET" class="relative">
                    <input type="text" name="q" placeholder="Search for help..."
                           class="w-full pl-10 pr-4 py-2.5 text-sm border border-neutral-200 rounded-xl bg-white text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </form>
            </div>

            <!-- Help Topics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-8 sm:mb-10">
                <a href="{{ route('faq') }}" class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-primary-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-primary-600 transition-colors">FAQ</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Find answers to frequently asked questions.</p>
                </a>

                <a href="{{ route('account.orders.index') }}" class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-info-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-info-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-info-600 transition-colors">Orders</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Track orders, view history, and manage returns.</p>
                </a>

                <a href="{{ route('shipping') }}" class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-success-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-success-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-success-600 transition-colors">Shipping</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Shipping options, delivery times, and costs.</p>
                </a>

                <a href="{{ route('returns') }}" class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-warning-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-warning-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-warning-600 transition-colors">Returns</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">How to return items and get refunds.</p>
                </a>

                <a href="{{ route('account.profile') }}" class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-neutral-300 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-neutral-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-neutral-700 transition-colors">Account</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Manage your profile and account settings.</p>
                </a>

                <a href="{{ route('contact') }}" class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-danger-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-danger-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-danger-600 transition-colors">Contact Us</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Get in touch with our support team.</p>
                </a>
            </div>

            <!-- Contact Methods -->
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-8">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-6 text-center">Still need help?</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                    <div class="text-center">
                        <div class="w-11 h-11 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Email Support</h3>
                        <p class="text-xs text-neutral-600 mb-3">We'll respond within 24 hours.</p>
                        <a href="{{ route('contact') }}" class="inline-flex items-center px-4 py-2 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">Send Email</a>
                    </div>
                    <div class="text-center">
                        <div class="w-11 h-11 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Phone Support</h3>
                        <p class="text-xs text-neutral-600 mb-3">Mon-Fri, 9am-5pm EST</p>
                        <a href="tel:+15551234567" class="inline-flex items-center px-4 py-2 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">+1 (555) 123-4567</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
