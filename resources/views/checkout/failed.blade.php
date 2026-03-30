<x-layouts.app>
    <x-slot name="title">Payment Failed</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-10">
            <div class="max-w-2xl mx-auto">

                <!-- Failed Header -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-neutral-900 mb-1">Payment Failed</h1>
                    <p class="text-[14px] text-neutral-600">Your payment could not be processed. No amount has been charged.</p>
                </div>

                <!-- Info Card -->
                <div class="bg-white rounded-xl border border-neutral-100 p-5 mb-6">
                    <h2 class="text-sm font-semibold text-neutral-900 mb-3">What happened?</h2>
                    <ul class="space-y-2 text-sm text-neutral-600">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-neutral-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            The payment was declined by your bank or payment provider.
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-neutral-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Your cart items are still saved. You can try again anytime.
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-neutral-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            If any amount was deducted, it will be refunded within 5-7 business days.
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('checkout.index') }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 py-3 px-6 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Try Again
                    </a>
                    <a href="{{ route('cart.index') }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 py-3 px-6 border border-neutral-200 text-neutral-700 text-sm font-semibold rounded-xl hover:bg-neutral-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Back to Cart
                    </a>
                </div>

                <!-- Help Section -->
                <div class="mt-8 text-center">
                    <p class="text-xs text-neutral-600">
                        Need help? <a href="{{ route('contact') }}" class="text-[#6F9CA2] hover:underline">Contact our support team</a>
                    </p>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
