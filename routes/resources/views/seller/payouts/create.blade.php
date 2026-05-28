<x-layouts.seller>
    <x-slot name="title">Request Payout</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.payouts.index') }}" class="hover:text-primary-600">Payouts</a>
        <span>/</span>
        <span>Request Payout</span>
    </div>

    <div class="max-w-xl">
        <h1 class="text-2xl font-bold text-neutral-900 mb-6">Request Payout</h1>

        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-neutral-600">Available Balance</span>
                <span class="text-2xl font-bold text-primary-600">@price($availableBalance)</span>
            </div>
            <p class="text-sm text-neutral-600">Minimum payout amount is {{ currency_symbol() }}10.00</p>
        </div>

        <form action="{{ route('seller.payouts.store') }}" method="POST" class="card p-6 space-y-6">
            @csrf

            <div>
                <label for="amount" class="block text-sm font-medium text-neutral-700 mb-1">Payout Amount *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600">{{ currency_symbol() }}</span>
                    <input type="number" name="amount" id="amount" value="{{ old('amount', $availableBalance) }}"
                           step="0.01" min="10" max="{{ $availableBalance }}" required
                           class="form-input w-full pl-7 @error('amount') border-error-300 @enderror">
                </div>
                @error('amount')
                    <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="payout_method" class="block text-sm font-medium text-neutral-700 mb-1">Payout Method *</label>
                <select name="payout_method" id="payout_method" required
                        class="form-input w-full @error('payout_method') border-error-300 @enderror">
                    <option value="">Select method</option>
                    <option value="bank_transfer" {{ old('payout_method', $seller->payout_method) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="paypal" {{ old('payout_method', $seller->payout_method) === 'paypal' ? 'selected' : '' }}>PayPal</option>
                </select>
                @error('payout_method')
                    <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-neutral-50 p-4 rounded-lg">
                <h3 class="font-medium text-neutral-900 mb-2">Payout Information</h3>
                @if($seller->payout_method === 'bank_transfer')
                    <dl class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Bank Name</dt>
                            <dd class="font-medium">{{ $seller->bank_name ?? 'Not set' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Account Number</dt>
                            <dd class="font-medium">{{ $seller->bank_account ? '****' . substr($seller->bank_account, -4) : 'Not set' }}</dd>
                        </div>
                    </dl>
                @elseif($seller->payout_method === 'paypal')
                    <dl class="text-sm">
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">PayPal Email</dt>
                            <dd class="font-medium">{{ $seller->payout_email ?? 'Not set' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-neutral-600">Please update your payout settings first.</p>
                    <a href="{{ route('seller.settings.index') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Update Settings</a>
                @endif
            </div>

            <div class="bg-warning-50 border border-warning-200 p-4 rounded-lg">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-warning-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h4 class="font-medium text-warning-800">Processing Time</h4>
                        <p class="text-sm text-warning-700">Payouts typically take 3-5 business days to process. You will be notified once the payout is complete.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-primary">Submit Payout Request</button>
                <a href="{{ route('seller.payouts.index') }}" class="btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.seller>
