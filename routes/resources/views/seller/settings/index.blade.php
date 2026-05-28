<x-layouts.seller>
    <x-slot name="title">Settings</x-slot>

    <h1 class="text-2xl font-bold text-neutral-900 mb-6">Settings</h1>

    <div class="space-y-6">
        <!-- Store Profile -->
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 mb-4">Store Profile</h2>

            <form action="{{ route('seller.settings.profile') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="store_name" class="block text-sm font-medium text-neutral-700 mb-1">Store Name *</label>
                    <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $seller->store_name) }}" required
                           class="form-input w-full max-w-md @error('store_name') border-error-300 @enderror">
                    @error('store_name')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="store_description" class="block text-sm font-medium text-neutral-700 mb-1">Store Description</label>
                    <textarea name="store_description" id="store_description" rows="4"
                              class="form-input w-full max-w-md @error('store_description') border-error-300 @enderror"
                              placeholder="Tell customers about your store...">{{ old('store_description', $seller->store_description) }}</textarea>
                    @error('store_description')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1">Business Phone</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone', $seller->phone) }}"
                           class="form-input w-full max-w-md @error('phone') border-error-300 @enderror">
                    @error('phone')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-neutral-700 mb-1">Business Address</label>
                    <textarea name="address" id="address" rows="2"
                              class="form-input w-full max-w-md @error('address') border-error-300 @enderror">{{ old('address', $seller->address) }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>

        <!-- Payout Settings -->
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 mb-4">Payout Settings</h2>

            <form action="{{ route('seller.settings.payout') }}" method="POST" class="space-y-4"
                  x-data="{ method: '{{ old('payout_method', $seller->payout_method ?? 'bank_transfer') }}' }">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Payout Method *</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="payout_method" value="bank_transfer" x-model="method"
                                   class="text-primary-600 focus:ring-primary-500">
                            <span class="ml-2">Bank Transfer</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="payout_method" value="paypal" x-model="method"
                                   class="text-primary-600 focus:ring-primary-500">
                            <span class="ml-2">PayPal</span>
                        </label>
                    </div>
                </div>

                <!-- Bank Transfer Fields -->
                <div x-show="method === 'bank_transfer'" class="space-y-4">
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-neutral-700 mb-1">Bank Name *</label>
                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $seller->bank_name) }}"
                               class="form-input w-full max-w-md @error('bank_name') border-error-300 @enderror">
                        @error('bank_name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bank_account" class="block text-sm font-medium text-neutral-700 mb-1">Account Number *</label>
                        <input type="text" name="bank_account" id="bank_account" value="{{ old('bank_account', $seller->bank_account) }}"
                               class="form-input w-full max-w-md @error('bank_account') border-error-300 @enderror">
                        @error('bank_account')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bank_routing" class="block text-sm font-medium text-neutral-700 mb-1">Routing Number *</label>
                        <input type="text" name="bank_routing" id="bank_routing" value="{{ old('bank_routing', $seller->bank_routing) }}"
                               class="form-input w-full max-w-md @error('bank_routing') border-error-300 @enderror">
                        @error('bank_routing')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- PayPal Fields -->
                <div x-show="method === 'paypal'" class="space-y-4">
                    <div>
                        <label for="payout_email" class="block text-sm font-medium text-neutral-700 mb-1">PayPal Email *</label>
                        <input type="email" name="payout_email" id="payout_email" value="{{ old('payout_email', $seller->payout_email) }}"
                               class="form-input w-full max-w-md @error('payout_email') border-error-300 @enderror">
                        @error('payout_email')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn-primary">Save Payout Settings</button>
            </form>
        </div>

        <!-- Notification Settings -->
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 mb-4">Notification Settings</h2>

            <form action="{{ route('seller.settings.notifications') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="email_notifications" value="1"
                               {{ old('email_notifications', $seller->email_notifications ?? true) ? 'checked' : '' }}
                               class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-neutral-700">Email notifications</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="order_notifications" value="1"
                               {{ old('order_notifications', $seller->order_notifications ?? true) ? 'checked' : '' }}
                               class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-neutral-700">New order notifications</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="review_notifications" value="1"
                               {{ old('review_notifications', $seller->review_notifications ?? true) ? 'checked' : '' }}
                               class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-neutral-700">New review notifications</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary">Save Notification Settings</button>
            </form>
        </div>
    </div>
</x-layouts.seller>
