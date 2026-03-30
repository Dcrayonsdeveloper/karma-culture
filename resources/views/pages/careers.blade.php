<x-layouts.app>
    <x-slot name="title">Careers - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Join the {{ config('app.name') }} team. Explore career opportunities in kids' fashion and e-commerce.">
        <link rel="canonical" href="{{ url('/careers') }}">
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Careers', 'url' => null]]" />
        </div>
    </div>

    <section class="py-10 sm:py-14">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto text-center">

                <!-- Header -->
                <div class="mb-10">
                    <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 mb-3">Careers at ForeverKids</h1>
                    <p class="text-sm text-neutral-600 max-w-lg mx-auto">Join our team and help us bring the best kids' fashion to families across India.</p>
                </div>

                <!-- No Openings Card -->
                <div class="bg-white rounded-xl border border-neutral-100 p-8 sm:p-12">
                    <div class="w-16 h-16 mx-auto rounded-full bg-[#6F9CA2]/10 flex items-center justify-center mb-5">
                        <svg class="w-8 h-8 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-neutral-900 mb-2">No Open Positions Right Now</h2>
                    <p class="text-sm text-neutral-600 mb-6 max-w-md mx-auto">We don't have any active openings at the moment, but we're always looking for talented people. Send us your resume and we'll keep it on file.</p>

                    <div class="inline-flex items-center gap-2 bg-neutral-50 rounded-lg px-5 py-3 border border-neutral-100">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:careers@foreverkids.in" class="text-sm font-medium text-[#6F9CA2] hover:underline">careers@foreverkids.in</a>
                    </div>
                </div>

                <!-- Why Join Us -->
                <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6 text-left">
                    <div class="bg-white rounded-xl border border-neutral-100 p-5">
                        <div class="w-9 h-9 rounded-lg bg-[#F8931D]/10 flex items-center justify-center mb-3">
                            <svg class="w-4.5 h-4.5 text-[#F8931D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Fast-Growing Startup</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">Be part of a rapidly growing kids' fashion brand with big ambitions.</p>
                    </div>
                    <div class="bg-white rounded-xl border border-neutral-100 p-5">
                        <div class="w-9 h-9 rounded-lg bg-[#6F9CA2]/10 flex items-center justify-center mb-3">
                            <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Great Team</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">Work alongside passionate people who love what they do.</p>
                    </div>
                    <div class="bg-white rounded-xl border border-neutral-100 p-5">
                        <div class="w-9 h-9 rounded-lg bg-[#F8931D]/10 flex items-center justify-center mb-3">
                            <svg class="w-4.5 h-4.5 text-[#F8931D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-900 mb-1">Meaningful Work</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">Help bring quality, affordable fashion to kids across India.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>
</x-layouts.app>
