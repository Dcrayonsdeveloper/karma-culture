<x-layouts.app>
    <x-slot name="title">Shipping Information - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Shipping information for {{ config('app.name') }}. Learn about delivery times, shipping costs, and tracking your kids' clothing orders.">
        <link rel="canonical" href="{{ url('/shipping') }}">
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4 py-2.5">
            <x-breadcrumb :items="[['label' => 'Shipping Info', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            <!-- Header -->
            <div class="text-center mb-8 sm:mb-10">
                <div class="w-14 h-14 mx-auto rounded-full bg-[#6F9CA2]/5 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 mb-1.5">Shipping Information</h1>
                <p class="text-[13px] text-neutral-600">Everything you need to know about delivery & shipping.</p>
            </div>

            <!-- Delivery Options Cards -->
            <div class="mb-8">
                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pb-3">Delivery Options</p>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div class="bg-white border border-neutral-100 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-[#6F9CA2]/5 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-neutral-900">Standard Delivery</h3>
                                <p class="text-xs text-neutral-600 mt-0.5">5-7 business days</p>
                                <p class="text-sm font-bold text-[#6F9CA2] mt-1.5">FREE on orders above ₹499</p>
                                <p class="text-[11px] text-neutral-600 mt-0.5">₹49 for orders below ₹499</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-neutral-100 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-[#6F9CA2]/5 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-neutral-900">Express Delivery</h3>
                                <p class="text-xs text-neutral-600 mt-0.5">2-3 business days</p>
                                <p class="text-sm font-bold text-neutral-900 mt-1.5">₹99</p>
                                <p class="text-[11px] text-neutral-600 mt-0.5">Available in select cities</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-neutral-100 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-[#6F9CA2]/5 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-neutral-900">Same Day Delivery</h3>
                                <p class="text-xs text-neutral-600 mt-0.5">Order before 12 PM</p>
                                <p class="text-sm font-bold text-neutral-900 mt-1.5">₹149</p>
                                <p class="text-[11px] text-neutral-600 mt-0.5">Mumbai, Delhi, Bangalore only</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-neutral-100 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-success-50 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-neutral-900">Cash on Delivery</h3>
                                <p class="text-xs text-neutral-600 mt-0.5">Pay when you receive</p>
                                <p class="text-sm font-bold text-neutral-900 mt-1.5">Available</p>
                                <p class="text-[11px] text-neutral-600 mt-0.5">On orders up to ₹5,000</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Sections -->
            <div x-data="{ open: null }" class="space-y-3">

                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pt-2 pb-1">Order Processing</p>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 1 ? null : 1"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">When will my order be shipped?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 1" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Orders placed before 2:00 PM IST on business days are typically processed and shipped the same day. Orders placed after 2:00 PM IST or on weekends/holidays will be processed the next business day.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 2 ? null : 2"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">How can I track my order?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 2" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Once your order ships, you'll receive an SMS and email with tracking details. You can also:</p>
                            <ul class="mt-2 space-y-1 list-disc list-inside text-neutral-600">
                                <li>Log into your account and check your order history</li>
                                <li>Use our <a href="{{ route('track-order') }}" class="text-[#6F9CA2] hover:text-[#5B878D] font-medium">order tracking page</a></li>
                                <li>Click the tracking link in your shipping confirmation email</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pt-4 pb-1">Delivery Details</p>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 3 ? null : 3"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">Which areas do you deliver to?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 3" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">We deliver across all major cities and towns in India. You can check delivery availability for your pincode during checkout. Remote areas may take 1-2 additional business days.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 4 ? null : 4"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">What if I'm not available to receive the delivery?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 4" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Our delivery partner will attempt delivery up to 3 times. If you're unavailable, they'll contact you on your registered phone number. You can also request a reschedule by contacting our support team.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 5 ? null : 5"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">Can I change my delivery address after placing an order?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 5" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">You can change the delivery address before the order is shipped. Once shipped, address changes are not possible. To change the address, please contact our customer support immediately with your order number.</p>
                        </div>
                    </div>
                </div>

                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pt-4 pb-1">Issues & Support</p>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 6 ? null : 6"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">What if my package is damaged or lost?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 6 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 6" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">If your package arrives damaged or is lost in transit, please contact us within 7 days of the expected delivery date. We will work with the delivery partner to resolve the issue and arrange a replacement or refund as quickly as possible.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 7 ? null : 7"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">Are there any shipping restrictions?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 7 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 7" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Some oversized items or bundled sets may have shipping restrictions to certain pincodes. You'll be notified at checkout if any items in your cart cannot be shipped to your location.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Still Need Help -->
            <div class="mt-10 bg-white border border-neutral-100 rounded-xl p-6 sm:p-8 text-center">
                <div class="w-11 h-11 mx-auto rounded-full bg-[#6F9CA2]/5 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-[15px] font-bold text-neutral-900 mb-1">Need more help?</h3>
                <p class="text-[13px] text-neutral-600 mb-4">Our support team is available to assist with any delivery questions.</p>
                <a href="{{ route('contact') }}"
                   class="inline-flex items-center px-6 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-xl transition-colors">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
