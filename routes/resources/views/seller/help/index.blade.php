<x-layouts.seller>
    <x-slot name="title">Help Center</x-slot>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Help Center</h1>
        <p class="text-neutral-600">Find answers and get support</p>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card p-6 text-center">
            <svg class="w-10 h-10 mx-auto text-primary-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <h3 class="font-semibold text-neutral-900 mb-1">Managing Products</h3>
            <p class="text-sm text-neutral-600">Learn how to add, edit, and manage your product listings.</p>
        </div>
        <div class="card p-6 text-center">
            <svg class="w-10 h-10 mx-auto text-primary-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="font-semibold text-neutral-900 mb-1">Orders & Shipping</h3>
            <p class="text-sm text-neutral-600">How to process orders, update shipping, and handle returns.</p>
        </div>
        <div class="card p-6 text-center">
            <svg class="w-10 h-10 mx-auto text-primary-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="font-semibold text-neutral-900 mb-1">Payments & Payouts</h3>
            <p class="text-sm text-neutral-600">Understand how payments are processed and when you get paid.</p>
        </div>
    </div>

    <!-- FAQ -->
    <div class="card">
        <div class="p-4 border-b border-neutral-200">
            <h2 class="font-semibold text-neutral-900">Frequently Asked Questions</h2>
        </div>
        <div class="divide-y divide-neutral-200" x-data="{ open: null }">
            <div class="p-4">
                <button @click="open = open === 1 ? null : 1" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-neutral-900">How do I add a new product?</span>
                    <svg class="w-5 h-5 text-neutral-600 transition-transform" :class="{ 'rotate-180': open === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 1" x-collapse class="mt-2 text-sm text-neutral-600">
                    Go to Products > Add Product. Fill in the required fields including name, SKU, category, description, price, stock quantity, and upload at least one image. Click "Create Product" to save.
                </div>
            </div>
            <div class="p-4">
                <button @click="open = open === 2 ? null : 2" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-neutral-900">How do I process an order?</span>
                    <svg class="w-5 h-5 text-neutral-600 transition-transform" :class="{ 'rotate-180': open === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 2" x-collapse class="mt-2 text-sm text-neutral-600">
                    Go to Orders and click on the order you want to process. Update the status to "Processing" or "Shipped" and enter the tracking number and carrier details when shipping.
                </div>
            </div>
            <div class="p-4">
                <button @click="open = open === 3 ? null : 3" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-neutral-900">When do I receive my payouts?</span>
                    <svg class="w-5 h-5 text-neutral-600 transition-transform" :class="{ 'rotate-180': open === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 3" x-collapse class="mt-2 text-sm text-neutral-600">
                    Payouts are processed on a regular schedule. Go to Payouts to view your pending balance, request a payout, and see your payout history.
                </div>
            </div>
            <div class="p-4">
                <button @click="open = open === 4 ? null : 4" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-neutral-900">How do I handle a return request?</span>
                    <svg class="w-5 h-5 text-neutral-600 transition-transform" :class="{ 'rotate-180': open === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 4" x-collapse class="mt-2 text-sm text-neutral-600">
                    Go to Returns to view return requests. Click on a return to see the details, then approve or reject the request. Once approved, the customer will receive refund instructions.
                </div>
            </div>
            <div class="p-4">
                <button @click="open = open === 5 ? null : 5" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-neutral-900">How do I create a coupon or promotion?</span>
                    <svg class="w-5 h-5 text-neutral-600 transition-transform" :class="{ 'rotate-180': open === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 5" x-collapse class="mt-2 text-sm text-neutral-600">
                    Go to Coupons or Promotions from the sidebar. Click "New Coupon" or "New Promotion" to create a discount. Set the type, value, and validity period, then save.
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="card p-6 mt-6 text-center">
        <h3 class="font-semibold text-neutral-900 mb-2">Still need help?</h3>
        <p class="text-sm text-neutral-600 mb-4">Contact our support team and we'll get back to you as soon as possible.</p>
        <a href="{{ route('seller.help.contact') }}" class="btn-primary">Contact Support</a>
    </div>
</x-layouts.seller>
