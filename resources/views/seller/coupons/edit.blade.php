<x-layouts.seller>
    <x-slot name="title">Edit Coupon</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.coupons.index') }}" class="hover:text-primary-600">Coupons</a>
        <span>/</span>
        <span>Edit</span>
    </div>

    <h1 class="text-2xl font-bold text-neutral-900 mb-6">Edit Coupon</h1>

    <form action="{{ route('seller.coupons.update', $coupon) }}" method="POST" class="max-w-2xl">
        @csrf
        @method('PUT')

        <div class="card p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-neutral-700 mb-1">Coupon Code *</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $coupon->code) }}" required
                           class="form-input w-full uppercase @error('code') border-error-300 @enderror">
                    @error('code')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-neutral-700 mb-1">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $coupon->name) }}" required
                           class="form-input w-full @error('name') border-error-300 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-neutral-700 mb-1">Discount Type *</label>
                    <select name="type" id="type" required
                            class="form-input w-full @error('type') border-error-300 @enderror">
                        <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                        <option value="free_shipping" {{ old('type', $coupon->type) === 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="value" class="block text-sm font-medium text-neutral-700 mb-1">Discount Value *</label>
                    <input type="number" name="value" id="value" value="{{ old('value', $coupon->value) }}" required
                           step="0.01" min="0"
                           class="form-input w-full @error('value') border-error-300 @enderror">
                    @error('value')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="min_order_amount" class="block text-sm font-medium text-neutral-700 mb-1">Min. Order Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600">$</span>
                        <input type="number" name="min_order_amount" id="min_order_amount"
                               value="{{ old('min_order_amount', $coupon->min_order_amount) }}"
                               step="0.01" min="0"
                               class="form-input w-full pl-7 @error('min_order_amount') border-error-300 @enderror">
                    </div>
                    @error('min_order_amount')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_discount" class="block text-sm font-medium text-neutral-700 mb-1">Max. Discount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600">$</span>
                        <input type="number" name="max_discount" id="max_discount"
                               value="{{ old('max_discount', $coupon->max_discount) }}"
                               step="0.01" min="0"
                               class="form-input w-full pl-7 @error('max_discount') border-error-300 @enderror">
                    </div>
                    @error('max_discount')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="usage_limit" class="block text-sm font-medium text-neutral-700 mb-1">Usage Limit</label>
                <input type="number" name="usage_limit" id="usage_limit"
                       value="{{ old('usage_limit', $coupon->usage_limit) }}"
                       min="1"
                       class="form-input w-full @error('usage_limit') border-error-300 @enderror"
                       placeholder="Leave empty for unlimited">
                @error('usage_limit')
                    <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-neutral-700 mb-1">Start Date</label>
                    <input type="datetime-local" name="starts_at" id="starts_at"
                           value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"
                           class="form-input w-full @error('starts_at') border-error-300 @enderror">
                    @error('starts_at')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expires_at" class="block text-sm font-medium text-neutral-700 mb-1">Expiry Date</label>
                    <input type="datetime-local" name="expires_at" id="expires_at"
                           value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}"
                           class="form-input w-full @error('expires_at') border-error-300 @enderror">
                    @error('expires_at')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit" class="btn-primary">Update Coupon</button>
                <a href="{{ route('seller.coupons.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</x-layouts.seller>
