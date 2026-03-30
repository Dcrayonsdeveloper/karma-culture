<x-layouts.app>
    <x-slot name="title">FAQ - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Frequently asked questions about {{ config('app.name') }}. Find answers about shipping, returns, sizing, orders, and more.">
        <link rel="canonical" href="{{ url('/faq') }}">
        <meta property="og:title" content="FAQ - {{ config('app.name') }}">
        <meta property="og:description" content="Frequently asked questions about {{ config('app.name') }}. Find answers about shipping, returns, sizing, orders, and more.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/faq') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="FAQ - {{ config('app.name') }}">
        <meta name="twitter:description" content="Find answers about shipping, returns, sizing, orders, and more at {{ config('app.name') }}.">
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'FAQ', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            <!-- Header -->
            <div class="text-center mb-8 sm:mb-10">
                <div class="w-14 h-14 mx-auto rounded-full bg-[#6F9CA2]/5 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 mb-1.5">Frequently Asked Questions</h1>
                <p class="text-[13px] text-neutral-600">Find answers to common questions about shopping with us.</p>
            </div>

            <!-- FAQ Accordion -->
            <div x-data="{ open: null }" class="space-y-3">

                <!-- Section: Ordering -->
                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pt-2 pb-1">Ordering</p>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 1 ? null : 1"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">How do I place an order?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 1" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Simply browse our products, add items to your cart, and proceed to checkout. You'll need to create an account or sign in, enter your shipping details, and complete the payment. You'll receive an order confirmation email once your order is placed.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 2 ? null : 2"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">What payment methods do you accept?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 2" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers. All payments are processed securely through our payment partners.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 3 ? null : 3"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">How can I track my order?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 3" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Once your order ships, you'll receive an email with tracking information. You can also track your order by logging into your account and visiting the Orders section, or by using our <a href="{{ route('track-order') }}" class="text-[#6F9CA2] hover:text-[#5B878D] font-medium">order tracking page</a>.</p>
                        </div>
                    </div>
                </div>

                <!-- Section: Shipping -->
                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pt-4 pb-1">Shipping</p>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 4 ? null : 4"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">How long does shipping take?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 4" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Shipping times vary depending on your location and chosen shipping method. Standard shipping typically takes 5-7 business days, while express shipping takes 2-3 business days. International shipping may take 7-14 business days.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 5 ? null : 5"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">Do you ship internationally?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 5" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Yes, we ship to over 100 countries worldwide. Shipping costs and delivery times vary by destination. You can see the exact shipping costs at checkout.</p>
                        </div>
                    </div>
                </div>

                <!-- Section: Returns & Refunds -->
                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pt-4 pb-1">Returns & Refunds</p>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 6 ? null : 6"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">What is your return policy?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 6 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 6" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">We offer a 7-day return policy for most items. Products must be unused and in their original packaging. Please visit our <a href="{{ route('returns') }}" class="text-[#6F9CA2] hover:text-[#5B878D] font-medium">Returns Policy</a> page for full details.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 7 ? null : 7"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">How do I request a refund?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 7 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 7" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">To request a refund, log into your account, go to your Orders, and select the order you wish to return. Click "Request Return" and follow the instructions. Once we receive and inspect the returned item, your refund will be processed within 5-7 business days.</p>
                        </div>
                    </div>
                </div>

                <!-- Section: Account -->
                <p class="text-xs font-semibold text-[#6F9CA2] uppercase tracking-wider pt-4 pb-1">Account</p>

                <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    <button @click="open = open === 8 ? null : 8"
                            class="w-full px-5 py-3.5 flex items-center justify-between text-left gap-3 hover:bg-neutral-50/50 transition-colors">
                        <span class="text-sm font-medium text-neutral-900">How do I create an account?</span>
                        <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open === 8 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 8" x-collapse>
                        <div class="px-5 pb-4 text-[13px] text-neutral-600 leading-relaxed border-t border-neutral-50">
                            <p class="pt-3">Click the "Sign Up" button at the top of the page and fill in your details. You can also create an account during checkout. Having an account allows you to track orders, save addresses, and earn rewards.</p>
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
                <h3 class="text-[15px] font-bold text-neutral-900 mb-1">Still have questions?</h3>
                <p class="text-[13px] text-neutral-600 mb-4">Can't find what you're looking for? We're here to help.</p>
                <a href="{{ route('contact') }}"
                   class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-[#F8931D] via-[#F8931D] to-[#E07E0A] hover:from-[#E07E0A] hover:via-[#E07E0A] hover:to-[#D47200] text-white text-sm font-semibold rounded-xl shadow-lg shadow-[#F8931D]/25 hover:shadow-[#F8931D]/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
