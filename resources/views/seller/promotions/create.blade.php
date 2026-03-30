<x-layouts.seller>
    <x-slot name="title">New Promotion</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.promotions.index') }}" class="hover:text-primary-600">Promotions</a>
        <span>/</span>
        <span>New Promotion</span>
    </div>

    <h1 class="text-2xl font-bold text-neutral-900 mb-6">Create Promotion</h1>

    <form action="{{ route('seller.promotions.store') }}" method="POST" class="max-w-2xl">
        @csrf

        <div class="card p-6 space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-neutral-700 mb-1">Promotion Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="form-input w-full @error('name') border-error-300 @enderror"
                       placeholder="e.g., Summer Sale 20% Off">
                @error('name')
                    <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-neutral-700 mb-1">Discount Type *</label>
                    <select name="type" id="type" required
                            class="form-input w-full @error('type') border-error-300 @enderror">
                        <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="value" class="block text-sm font-medium text-neutral-700 mb-1">Discount Value *</label>
                    <input type="number" name="value" id="value" value="{{ old('value') }}" required
                           step="0.01" min="0"
                           class="form-input w-full @error('value') border-error-300 @enderror"
                           placeholder="e.g., 20">
                    @error('value')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-neutral-700 mb-1">Start Date *</label>
                    <input type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at') }}" required
                           class="form-input w-full @error('starts_at') border-error-300 @enderror">
                    @error('starts_at')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ends_at" class="block text-sm font-medium text-neutral-700 mb-1">End Date *</label>
                    <input type="datetime-local" name="ends_at" id="ends_at" value="{{ old('ends_at') }}" required
                           class="form-input w-full @error('ends_at') border-error-300 @enderror">
                    @error('ends_at')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit" class="btn-primary">Create Promotion</button>
                <a href="{{ route('seller.promotions.index') }}" class="btn-outline">Cancel</a>
            </div>
        </div>
    </form>
</x-layouts.seller>
