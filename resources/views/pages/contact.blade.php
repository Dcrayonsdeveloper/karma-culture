<x-layouts.app>
    <x-slot name="title">Contact Us - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Get in touch with {{ config('app.name') }}. We're here to help with orders, returns, and any questions about kids' clothing.">
        <link rel="canonical" href="{{ url('/contact') }}">
        <meta property="og:title" content="Contact Us - {{ config('app.name') }}">
        <meta property="og:description" content="Get in touch with {{ config('app.name') }}. We're here to help with orders, returns, and any questions about kids' clothing.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/contact') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Contact Us - {{ config('app.name') }}">
        <meta name="twitter:description" content="Get in touch with {{ config('app.name') }}. We're here to help with orders, returns, and any questions.">

        {{-- LocalBusiness Schema --}}
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ClothingStore',
            'name' => config('app.name'),
            'description' => "Kids' clothing store offering fashionable and comfortable outfits for children.",
            'url' => url('/'),
            'telephone' => '+919311796900',
            'email' => 'support@foreverkids.com',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'D-12/140, Rohini Sector-7',
                'addressLocality' => 'Delhi',
                'postalCode' => '110085',
                'addressCountry' => 'IN',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => 28.7041,
                'longitude' => 77.1025,
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'opens' => '09:00',
                    'closes' => '18:00',
                ],
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => 'Saturday',
                    'opens' => '10:00',
                    'closes' => '16:00',
                ],
            ],
            'sameAs' => array_values(array_filter([
                \App\Models\Setting::get('social_facebook'),
                \App\Models\Setting::get('social_instagram'),
                \App\Models\Setting::get('social_youtube'),
            ])),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Contact Us', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-6xl mx-auto">

            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 mb-1.5">Contact Us</h1>
                <p class="text-[13px] text-neutral-600">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                <!-- Left Column: Contact Form (50%) -->
                <div>
                    <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-7 h-full">
                        <h2 class="text-[15px] font-bold text-neutral-900 mb-5">Send us a message</h2>

                        <form action="{{ route('contact.send') }}" method="POST" class="flex flex-col h-[calc(100%-2rem)] space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-neutral-700 mb-1.5">Your Name <span class="text-red-400">*</span></label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all @error('name') border-red-300 bg-red-50 @enderror"
                                           placeholder="John Doe">
                                    @error('name')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all @error('email') border-red-300 bg-red-50 @enderror"
                                           placeholder="you@example.com">
                                    @error('email')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1.5">Phone Number</label>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                                       class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all @error('phone') border-red-300 bg-red-50 @enderror"
                                       placeholder="+91 93117 96900">
                                @error('phone')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="subject" class="block text-sm font-medium text-neutral-700 mb-1.5">Subject <span class="text-red-400">*</span></label>
                                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                       class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all @error('subject') border-red-300 bg-red-50 @enderror"
                                       placeholder="How can we help?">
                                @error('subject')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex-1">
                                <label for="message" class="block text-sm font-medium text-neutral-700 mb-1.5">Message <span class="text-red-400">*</span></label>
                                <textarea name="message" id="message" rows="6" required
                                          class="w-full h-[calc(100%-1.75rem)] min-h-[140px] px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all resize-none @error('message') border-red-300 bg-red-50 @enderror"
                                          placeholder="Tell us more about your inquiry...">{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-1">
                                <button type="submit"
                                        class="w-full sm:w-auto px-8 py-2.5 bg-gradient-to-r from-[#F8931D] via-[#F8931D] to-[#E07E0A] hover:from-[#E07E0A] hover:via-[#E07E0A] hover:to-[#D47200] text-white text-sm font-semibold rounded-xl shadow-lg shadow-[#F8931D]/25 hover:shadow-[#F8931D]/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/50 focus:ring-offset-2">
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Contact Details + Map (50%) -->
                <div class="flex flex-col gap-5">

                    <!-- Contact Details -->
                    <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                        <h3 class="text-[15px] font-bold text-neutral-900 mb-4">Get in touch</h3>

                        <div class="space-y-4">
                            <!-- Address -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-[#6F9CA2]/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">Address</p>
                                    <p class="text-[13px] text-neutral-600 leading-relaxed">D-12/140, Rohini Sector-7, Delhi 110085</p>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-[#6F9CA2]/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">Phone</p>
                                    <a href="tel:+919311796900" class="text-[13px] text-[#6F9CA2] hover:text-[#5B878D] transition-colors">+91 93117 96900</a>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-[#6F9CA2]/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">Email</p>
                                    <a href="mailto:support@foreverkids.com" class="text-[13px] text-[#6F9CA2] hover:text-[#5B878D] transition-colors">support@foreverkids.com</a>
                                </div>
                            </div>

                            <!-- Business Hours -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-[#6F9CA2]/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">Business Hours</p>
                                    <p class="text-[13px] text-neutral-600 leading-relaxed">Mon - Fri: 9:00 AM - 6:00 PM<br>Sat: 10:00 AM - 4:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Google Map -->
                    <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden flex-1">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3499.123456789!2d77.1025!3d28.7041!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d013e2d2075c1%3A0x2b5a4b2e8b8b8b8b!2sRohini+Sector+7%2C+Delhi+110085!5e0!3m2!1sen!2sin!4v1700000000000"
                            width="100%"
                            height="280"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            class="w-full h-full min-h-[280px] block">
                        </iframe>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
