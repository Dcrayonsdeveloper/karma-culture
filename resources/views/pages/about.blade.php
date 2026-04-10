<x-layouts.app>
    <x-slot name="title">About Us - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Learn about {{ config('app.name') }} - your trusted online store for kids' clothing, dresses, and accessories. Quality fashion for boys and girls.">
        <link rel="canonical" href="{{ url('/about') }}">
        <meta property="og:title" content="About Us - {{ config('app.name') }}">
        <meta property="og:description" content="Learn about {{ config('app.name') }} - your trusted online store for kids' clothing, dresses, and accessories.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/about') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="About Us - {{ config('app.name') }}">
        <meta name="twitter:description" content="Learn about {{ config('app.name') }} - your trusted online store for kids' clothing, dresses, and accessories.">
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'About Us', 'url' => null]]" />
        </div>
    </div>

    <!-- ============================================
         ABOUT / OUR STORY
         ============================================ -->
    <section class="py-12 sm:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center max-w-6xl mx-auto">

                <!-- Image side -->
                <div class="relative" style="padding: 50px;">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden bg-neutral-100 shadow-sm">
                        <img src="https://images.unsplash.com/photo-1503919545889-aef636e10ad4?w=800&q=80" alt="Kids Clothing" class="w-full h-full object-cover">
                    </div>
                </div>

                <!-- Content side -->
                <div>
                    <span class="inline-block text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider mb-3">Our Story</span>
                    <h2 class="text-xl sm:text-2xl font-bold text-neutral-900 mb-5 leading-snug">
                        Bringing the Best Kids' Fashion to Your Doorstep
                    </h2>
                    <div class="space-y-4 text-[13px] sm:text-sm text-neutral-600 leading-relaxed">
                        <p>
                            Founded with a passion for kids' fashion and quality, ForeverKids started with a simple mission — to make adorable, comfortable, and stylish clothing accessible to every family.
                        </p>
                        <p>
                            Today, we've grown into a trusted destination for thousands of parents. We partner directly with brands and manufacturers to ensure every piece on our platform meets the highest quality and safety standards.
                        </p>
                        <p>
                            From everyday essentials to special occasion outfits, our curated collection spans over 200 brands and thousands of styles — all delivered with care, competitive pricing, and a satisfaction guarantee.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================
         SERVICES / WHY CHOOSE US
         ============================================ -->
    <section class="py-12 sm:py-16 bg-neutral-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8 sm:mb-10">
                <span class="inline-block text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider mb-2">Why Choose Us</span>
                <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">What Sets Us Apart</h2>
                <p class="text-[13px] text-neutral-600 mt-2 max-w-md mx-auto">We go the extra mile to make shopping for your little ones a joyful experience.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto">
                <!-- Authenticity -->
                <div class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-[#6F9CA2]/30 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-[#6F9CA2]/5 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-[#6F9CA2] transition-colors">100% Authentic</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Every product is sourced from authorized distributors and verified for authenticity.</p>
                </div>

                <!-- Free Shipping -->
                <div class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-info-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-info-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-info-600 transition-colors">Free Shipping</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Enjoy free delivery on orders over $50 with fast and reliable shipping partners.</p>
                </div>

                <!-- Secure Shopping -->
                <div class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-success-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-success-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-success-600 transition-colors">Secure Payments</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Shop with confidence. Your data is encrypted and payments are 100% secure.</p>
                </div>

                <!-- Easy Returns -->
                <div class="bg-white border border-neutral-100 rounded-xl p-5 hover:border-warning-200 hover:shadow-sm transition-all group">
                    <div class="w-10 h-10 bg-warning-50 rounded-lg flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-warning-600 transition-colors">Easy Returns</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">Not satisfied? Return your items hassle-free within 7 days of delivery.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         TESTIMONIALS
         ============================================ -->
    <section class="py-12 sm:py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8 sm:mb-10">
                <span class="inline-block text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider mb-2">Testimonials</span>
                <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">What Our Customers Say</h2>
                <p class="text-[13px] text-neutral-600 mt-2">Real reviews from real parents who trust us.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-5xl mx-auto">
                <!-- Testimonial 1 -->
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <!-- Stars -->
                    <div class="flex items-center gap-0.5 mb-3">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-[13px] text-neutral-600 leading-relaxed mb-4">
                        "Absolutely love shopping here! The clothes are always top quality, packaging is excellent, and delivery is super fast. My go-to store for all my kids' clothing needs."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#6F9CA2]/10 flex items-center justify-center">
                            <span class="text-sm font-semibold text-[#6F9CA2]">P</span>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-neutral-900">Priya S.</div>
                            <div class="text-xs text-neutral-600">Verified Buyer</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <div class="flex items-center gap-0.5 mb-3">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-[13px] text-neutral-600 leading-relaxed mb-4">
                        "Best prices I've found for quality kids' outfits. I've been ordering for 6 months now and never been disappointed. The fabrics are soft, sizes are accurate, and customer service is outstanding!"
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-info-100 flex items-center justify-center">
                            <span class="text-sm font-semibold text-info-600">A</span>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-neutral-900">Aisha M.</div>
                            <div class="text-xs text-neutral-600">Verified Buyer</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                    <div class="flex items-center gap-0.5 mb-3">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-[13px] text-neutral-600 leading-relaxed mb-4">
                        "I was skeptical at first, but ForeverKids exceeded my expectations. The packaging was secure, quality was amazing, and arrived within 3 days. My kids love their new outfits!"
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-success-100 flex items-center justify-center">
                            <span class="text-sm font-semibold text-success-600">R</span>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-neutral-900">Rahul K.</div>
                            <div class="text-xs text-neutral-600">Verified Buyer</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ============================================
         CTA SECTION
         ============================================ -->
    <section class="py-12 sm:py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto text-center bg-gradient-to-br from-[#F8931D] via-[#F8931D] to-[#D47200] rounded-2xl p-8 sm:p-12 relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute -top-12 -right-12 w-40 h-40 bg-white/5 rounded-full"></div>
                <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/5 rounded-full"></div>

                <div class="relative z-10">
                    <h2 class="text-xl sm:text-2xl font-bold text-white mb-3">Ready to Find the Perfect Outfit for Your Little One?</h2>
                    <p class="text-sm text-white mb-6 max-w-md mx-auto">Browse our curated collection of adorable kids' clothing and enjoy free shipping on your first order.</p>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('categories.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-[#5B878D] text-sm font-semibold rounded-lg hover:bg-[#6F9CA2]/5 transition-colors shadow-lg">
                            Shop Now
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white border border-white/30 rounded-lg hover:bg-white/10 transition-colors">
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>
