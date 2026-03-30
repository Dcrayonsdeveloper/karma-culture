<x-layouts.app>
    <x-slot name="title">Raise a Ticket</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'My Account', 'url' => route('account.dashboard')], ['label' => 'Support Tickets', 'url' => route('account.tickets.index')], ['label' => 'Raise Ticket', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-6">
            @include('account.partials.sidebar')

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-5">
                    <a href="{{ route('account.tickets.index') }}" class="text-neutral-600 hover:text-neutral-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <h1 class="text-xl font-bold text-neutral-900">Raise a Ticket</h1>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-7">
                    <form action="{{ route('account.tickets.store') }}" method="POST" class="space-y-5">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="category" class="block text-sm font-medium text-neutral-700 mb-1.5">Category <span class="text-red-400">*</span></label>
                                <select name="category" id="category" required
                                        class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all @error('category') border-red-300 @enderror">
                                    <option value="">Select category</option>
                                    <option value="general" @selected(old('category') === 'general')>General Inquiry</option>
                                    <option value="order" @selected(old('category') === 'order')>Order Issue</option>
                                    <option value="payment" @selected(old('category') === 'payment')>Payment Issue</option>
                                    <option value="product" @selected(old('category') === 'product')>Product Query</option>
                                    <option value="account" @selected(old('category') === 'account')>Account Issue</option>
                                    <option value="other" @selected(old('category') === 'other')>Other</option>
                                </select>
                                @error('category')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="priority" class="block text-sm font-medium text-neutral-700 mb-1.5">Priority <span class="text-red-400">*</span></label>
                                <select name="priority" id="priority" required
                                        class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all @error('priority') border-red-300 @enderror">
                                    <option value="low" @selected(old('priority', 'normal') === 'low')>Low</option>
                                    <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                                    <option value="high" @selected(old('priority') === 'high')>High</option>
                                </select>
                                @error('priority')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-neutral-700 mb-1.5">Subject <span class="text-red-400">*</span></label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                   class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all @error('subject') border-red-300 @enderror"
                                   placeholder="Brief description of your issue">
                            @error('subject')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-neutral-700 mb-1.5">Message <span class="text-red-400">*</span></label>
                            <textarea name="message" id="message" rows="6" required
                                      class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all resize-none @error('message') border-red-300 @enderror"
                                      placeholder="Describe your issue in detail...">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <button type="submit"
                                    class="px-6 py-2.5 bg-gradient-to-r from-[#F8931D] to-[#E07E0A] hover:from-[#E07E0A] hover:to-[#D47200] text-white text-sm font-semibold rounded-xl shadow-lg shadow-[#F8931D]/25 transition-all">
                                Submit Ticket
                            </button>
                            <a href="{{ route('account.tickets.index') }}" class="px-4 py-2.5 text-sm text-neutral-600 hover:text-neutral-900">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
