<x-layouts.seller>
    <x-slot name="title">Contact Support</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-2">
            <a href="{{ route('seller.help') }}" class="hover:text-primary-600">Help Center</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Contact Support</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Contact Support</h1>
        <p class="text-neutral-600">Submit a ticket and our team will respond as soon as possible.</p>
    </div>

    <div class="card">
        <form action="{{ route('seller.help.contact.submit') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div>
                <label for="category" class="block text-sm font-medium text-neutral-700 mb-1">Category</label>
                <select name="category" id="category" class="form-select w-full" required>
                    <option value="">Select a category</option>
                    <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General Inquiry</option>
                    <option value="products" {{ old('category') === 'products' ? 'selected' : '' }}>Products & Listings</option>
                    <option value="orders" {{ old('category') === 'orders' ? 'selected' : '' }}>Orders & Shipping</option>
                    <option value="payments" {{ old('category') === 'payments' ? 'selected' : '' }}>Payments & Payouts</option>
                    <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Account & Settings</option>
                    <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('category')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-neutral-700 mb-1">Subject</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject') }}"
                       class="form-input w-full" placeholder="Brief description of your issue" required>
                @error('subject')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-neutral-700 mb-1">Message</label>
                <textarea name="message" id="message" rows="6"
                          class="form-textarea w-full" placeholder="Describe your issue in detail..." required>{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary">Submit Ticket</button>
                <a href="{{ route('seller.help') }}" class="btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.seller>
