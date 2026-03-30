<x-pos.layout>
<div class="pos-container" x-data="shiftOpen()" @keydown.window="handleKeydown($event)">
    <div class="flex items-center justify-center h-full" style="background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);">
        <div class="pos-card p-8 w-full max-w-md pos-fade-in" style="box-shadow: 0 8px 32px rgba(0,0,0,0.08);">

            {{-- Header --}}
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-full mx-auto mb-3 flex items-center justify-center" style="background: #F0FDFA;">
                    <svg class="w-8 h-8" style="color: var(--pos-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-semibold" style="color: var(--pos-text);">Open Shift</h1>
                <p class="text-sm mt-1" style="color: var(--pos-text-muted);">Welcome, <span class="font-medium">{{ $staffName }}</span></p>
            </div>

            {{-- Last shift info --}}
            @if($lastShift)
            <div class="mb-5 p-3 rounded-lg" style="background: #F8FAFC; border: 1px solid var(--pos-border);">
                <div class="text-xs font-medium mb-1" style="color: var(--pos-text-muted);">Last Shift</div>
                <div class="flex justify-between text-sm">
                    <span>{{ $lastShift->staff?->user?->first_name ?? 'Staff' }}</span>
                    <span class="pos-mono" style="color: var(--pos-text-muted);">{{ $lastShift->shift_end?->format('d M, g:i A') }}</span>
                </div>
                @if($lastShift->closing_cash !== null)
                <div class="flex justify-between text-sm mt-1">
                    <span style="color: var(--pos-text-muted);">Closing Cash</span>
                    <span class="pos-mono font-medium">₹{{ number_format($lastShift->closing_cash, 2) }}</span>
                </div>
                @endif
            </div>
            @endif

            {{-- Opening Cash Input --}}
            <div class="mb-5">
                <label class="block text-sm font-medium mb-2" style="color: var(--pos-text-muted);">
                    Opening Cash in Drawer
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-medium" style="color: var(--pos-text-muted);">₹</span>
                    <input
                        type="text"
                        x-ref="cashInput"
                        x-model="openingCash"
                        @input="formatCash()"
                        placeholder="0.00"
                        aria-label="Opening cash in drawer"
                        class="w-full pl-10 pr-4 py-4 rounded-lg border text-xl pos-mono font-medium text-right focus:outline-none focus:ring-2"
                        style="border-color: var(--pos-border); --tw-ring-color: var(--pos-primary);"
                        inputmode="decimal"
                    >
                </div>
            </div>

            {{-- Quick amounts --}}
            <div class="flex gap-2 mb-5">
                <button @click="setAmount(0)" class="flex-1 py-2 rounded-lg text-sm font-medium border transition-colors"
                        :style="openingCash === '0.00' ? 'background: var(--pos-primary); color: white; border-color: var(--pos-primary);' : 'border-color: var(--pos-border); color: var(--pos-text-muted);'"
                >₹0</button>
                <button @click="setAmount(500)" class="flex-1 py-2 rounded-lg text-sm font-medium border transition-colors"
                        :style="openingCash === '500.00' ? 'background: var(--pos-primary); color: white; border-color: var(--pos-primary);' : 'border-color: var(--pos-border); color: var(--pos-text-muted);'"
                >₹500</button>
                <button @click="setAmount(1000)" class="flex-1 py-2 rounded-lg text-sm font-medium border transition-colors"
                        :style="openingCash === '1000.00' ? 'background: var(--pos-primary); color: white; border-color: var(--pos-primary);' : 'border-color: var(--pos-border); color: var(--pos-text-muted);'"
                >₹1,000</button>
                <button @click="setAmount(2000)" class="flex-1 py-2 rounded-lg text-sm font-medium border transition-colors"
                        :style="openingCash === '2000.00' ? 'background: var(--pos-primary); color: white; border-color: var(--pos-primary);' : 'border-color: var(--pos-border); color: var(--pos-text-muted);'"
                >₹2,000</button>
            </div>

            {{-- Error --}}
            <p x-show="error" x-text="error" x-transition class="text-sm mb-3" style="color: var(--pos-danger);"></p>

            {{-- Open Shift Button --}}
            <button @click="openShift()" :disabled="loading"
                    class="pos-btn pos-btn-primary w-full text-base py-3.5 gap-2" style="font-size: 15px;">
                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span x-show="!loading">Start Shift</span>
                <span x-show="loading">Starting...</span>
            </button>

            {{-- Logout --}}
            <button @click="logout()" class="w-full text-center text-xs mt-5 py-2" style="color: var(--pos-text-muted);">
                ← Back to Login
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function shiftOpen() {
    return {
        openingCash: '0.00',
        loading: false,
        error: '',

        init() {
            this.$nextTick(() => {
                this.$refs.cashInput?.focus();
                this.$refs.cashInput?.select();
            });
        },

        setAmount(amount) {
            this.openingCash = amount.toFixed(2);
            this.error = '';
        },

        formatCash() {
            // Allow only numbers and one decimal point
            let val = this.openingCash.replace(/[^0-9.]/g, '');
            const parts = val.split('.');
            if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
            if (parts[1] && parts[1].length > 2) val = parts[0] + '.' + parts[1].substring(0, 2);
            this.openingCash = val;
        },

        async openShift() {
            const amount = parseFloat(this.openingCash) || 0;
            if (amount < 0) {
                this.error = 'Opening cash cannot be negative.';
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const response = await axios.post('{{ route("pos.shift.open") }}', {
                    opening_cash: amount
                });

                if (response.data.success) {
                    window.location.href = response.data.redirect;
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to open shift.';
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                const response = await axios.post('{{ route("pos.logout") }}');
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                }
            } catch (e) {
                window.location.href = '{{ route("pos.login") }}';
            }
        },

        handleKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.openShift();
            }
        },
    };
}
</script>
@endpush
</x-pos.layout>
