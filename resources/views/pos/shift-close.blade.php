<x-pos.layout>
<div class="pos-container" x-data="shiftClose()" @keydown.enter="closeShift()" role="main" aria-label="Close Shift">
    <div class="flex items-start justify-center pos-scroll" style="background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%); padding: 1rem; min-height: 100%; overflow-y: auto;">
        <div class="pos-card w-full max-w-lg pos-fade-in" style="box-shadow: 0 8px 32px rgba(0,0,0,0.08);">

            {{-- Header --}}
            <div class="px-4 sm:px-6 py-4 flex items-center justify-between flex-wrap gap-2" style="border-bottom: 1px solid var(--pos-border);">
                <div>
                    <h1 class="text-lg font-semibold" style="color: var(--pos-text);">Close Shift</h1>
                    <p class="text-xs mt-0.5" style="color: var(--pos-text-muted);">
                        {{ $shift->staff?->user?->first_name ?? 'Staff' }} ·
                        {{ $shift->shift_start->format('d M Y, g:i A') }} — Now
                    </p>
                </div>
                <a href="{{ route('pos.dashboard') }}" class="pos-btn pos-btn-ghost text-sm px-3 py-1.5">← Back</a>
            </div>

            {{-- Z-Report Summary --}}
            <div class="px-4 sm:px-6 py-4 space-y-4">

                {{-- Sales Overview --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--pos-text-muted);">Sales Summary</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--pos-text-muted);">Total Bills</span>
                            <span class="font-medium pos-mono">{{ $summary['total_bills'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--pos-text-muted);">Gross Sales</span>
                            <span class="font-medium pos-mono">₹{{ number_format($summary['gross_sales'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--pos-text-muted);">Discounts</span>
                            <span class="font-medium pos-mono" style="color: var(--pos-success);">-₹{{ number_format($summary['total_discount'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--pos-text-muted);">Tax (GST)</span>
                            <span class="font-medium pos-mono">₹{{ number_format($summary['total_tax'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm pt-1.5" style="border-top: 1px solid var(--pos-border);">
                            <span class="font-semibold">Net Sales</span>
                            <span class="font-bold pos-mono" style="color: var(--pos-primary);">₹{{ number_format($summary['net_sales'], 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Payment Breakdown --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--pos-text-muted);">Payments Received</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2.5 rounded-lg text-center" style="background: #DCFCE7;">
                            <div class="text-xs" style="color: #14532D;">Cash</div>
                            <div class="text-sm font-bold pos-mono" style="color: #14532D;">₹{{ number_format($summary['payments']['cash'], 2) }}</div>
                        </div>
                        <div class="p-2.5 rounded-lg text-center" style="background: #DBEAFE;">
                            <div class="text-xs" style="color: #1E3A5F;">Card</div>
                            <div class="text-sm font-bold pos-mono" style="color: #1E3A5F;">₹{{ number_format($summary['payments']['card'], 2) }}</div>
                        </div>
                        <div class="p-2.5 rounded-lg text-center" style="background: #F3E8FF;">
                            <div class="text-xs" style="color: #4A1D72;">UPI</div>
                            <div class="text-sm font-bold pos-mono" style="color: #4A1D72;">₹{{ number_format($summary['payments']['upi'], 2) }}</div>
                        </div>
                        <div class="p-2.5 rounded-lg text-center" style="background: #FEF3C7;">
                            <div class="text-xs" style="color: #92400E;">Split</div>
                            <div class="text-sm font-bold pos-mono" style="color: #92400E;">₹{{ number_format($summary['payments']['split'], 2) }}</div>
                        </div>
                    </div>
                </div>

                {{-- Returns --}}
                @if($summary['total_returns'] > 0)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--pos-text-muted);">Returns</h3>
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--pos-text-muted);">Returns Processed</span>
                        <span class="font-medium pos-mono">{{ $summary['total_returns'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--pos-text-muted);">Total Refunds</span>
                        <span class="font-medium pos-mono" style="color: var(--pos-danger);">₹{{ number_format($summary['total_refunds'], 2) }}</span>
                    </div>
                </div>
                @endif

                {{-- Bills / Transactions --}}
                @if($sales->count() > 0)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--pos-text-muted);">Bills ({{ $sales->count() }})</h3>
                    <div class="rounded-lg overflow-hidden" style="border: 1px solid var(--pos-border); max-height: 200px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                        <div style="overflow-x: auto;">
                        <table class="w-full text-xs" style="min-width: 400px;" role="table" aria-label="Bills this shift">
                            <thead>
                                <tr style="background: #F8FAFC; border-bottom: 1px solid var(--pos-border);">
                                    <th class="text-left px-3 py-2 font-semibold" style="color: var(--pos-text-muted);">Bill #</th>
                                    <th class="text-left px-3 py-2 font-semibold" style="color: var(--pos-text-muted);">Time</th>
                                    <th class="text-left px-3 py-2 font-semibold" style="color: var(--pos-text-muted);">Customer</th>
                                    <th class="text-right px-3 py-2 font-semibold" style="color: var(--pos-text-muted);">Total</th>
                                    <th class="text-center px-3 py-2 font-semibold" style="color: var(--pos-text-muted);">Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sales as $sale)
                                <tr style="border-bottom: 1px solid #F1F5F9;">
                                    <td class="px-3 py-1.5 pos-mono font-medium">{{ $sale->sale_number }}</td>
                                    <td class="px-3 py-1.5" style="color: var(--pos-text-muted);">{{ $sale->created_at->format('g:i A') }}</td>
                                    <td class="px-3 py-1.5">{{ $sale->customer?->first_name ?? 'Walk-in' }}</td>
                                    <td class="px-3 py-1.5 text-right font-bold pos-mono">₹{{ number_format($sale->total, 2) }}</td>
                                    <td class="px-3 py-1.5 text-center">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium capitalize"
                                              style="{{ $sale->payment_method === 'cash' ? 'background:#DCFCE7;color:#14532D;' : ($sale->payment_method === 'card' ? 'background:#DBEAFE;color:#1E3A5F;' : ($sale->payment_method === 'upi' ? 'background:#F3E8FF;color:#4A1D72;' : 'background:#FEF3C7;color:#78350F;')) }}">{{ $sale->payment_method }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Cash Reconciliation --}}
                <div class="p-4 rounded-lg" style="background: #F8FAFC; border: 1px solid var(--pos-border);">
                    <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--pos-text-muted);">Cash Reconciliation</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--pos-text-muted);">Opening Cash</span>
                            <span class="pos-mono">₹{{ number_format($summary['cash_reconciliation']['opening_cash'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--pos-text-muted);">+ Cash Sales</span>
                            <span class="pos-mono" style="color: var(--pos-success);">₹{{ number_format($summary['cash_reconciliation']['cash_sales'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--pos-text-muted);">- Cash Refunds</span>
                            <span class="pos-mono" style="color: var(--pos-danger);">₹{{ number_format($summary['cash_reconciliation']['cash_refunds'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold pt-1.5" style="border-top: 1px solid var(--pos-border);">
                            <span>Expected Cash</span>
                            <span class="pos-mono" style="color: var(--pos-primary);">₹{{ number_format($summary['cash_reconciliation']['expected_cash'], 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Closing Cash Input --}}
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--pos-text-muted);">
                        Actual Cash in Drawer
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-medium" style="color: var(--pos-text-muted);">₹</span>
                        <input
                            type="text"
                            x-ref="closingCashInput"
                            x-model="closingCash"
                            placeholder="0.00"
                            aria-label="Actual cash in drawer"
                            class="w-full pl-10 pr-4 py-3.5 rounded-lg border text-xl pos-mono font-medium text-right focus:outline-none focus:ring-2"
                            style="border-color: var(--pos-border); --tw-ring-color: var(--pos-primary);"
                            inputmode="decimal"
                        >
                    </div>

                    {{-- Variance --}}
                    <div x-show="closingCash !== ''" class="mt-2 p-2.5 rounded-lg text-center"
                         :style="variance() === 0 ? 'background: #DCFCE7;' : (variance() > 0 ? 'background: #DBEAFE;' : 'background: #FEF2F2;')">
                        <span class="text-xs" :style="variance() === 0 ? 'color: #14532D;' : (variance() > 0 ? 'color: #1E3A5F;' : 'color: #7F1D1D;')">
                            <span x-text="variance() === 0 ? 'Cash balanced ✓' : (variance() > 0 ? 'Cash OVER by ₹' + Math.abs(variance()).toFixed(2) : 'Cash SHORT by ₹' + Math.abs(variance()).toFixed(2))"></span>
                        </span>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--pos-text-muted);">Notes (optional)</label>
                    <textarea x-model="notes" rows="2" placeholder="Any observations..."
                              aria-label="Shift closing notes"
                              class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 resize-none"
                              style="border-color: var(--pos-border); --tw-ring-color: var(--pos-primary);"></textarea>
                </div>

                {{-- Error --}}
                <p x-show="error" x-text="error" x-transition class="text-sm" style="color: var(--pos-danger);"></p>

                {{-- Close Shift Button --}}
                <button @click="closeShift()" :disabled="loading || closingCash === ''"
                        class="pos-btn pos-btn-danger w-full text-base py-3.5 gap-2" style="font-size: 15px;"
                        :style="(loading || closingCash === '') ? 'opacity: 0.5;' : ''">
                    <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span x-show="!loading">Close Shift & Logout</span>
                    <span x-show="loading">Closing...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function shiftClose() {
    return {
        closingCash: '',
        notes: '',
        loading: false,
        error: '',
        expectedCash: {{ $summary['cash_reconciliation']['expected_cash'] }},

        init() {
            this.$nextTick(() => this.$refs.closingCashInput?.focus());
        },

        variance() {
            const actual = parseFloat(this.closingCash) || 0;
            return parseFloat((actual - this.expectedCash).toFixed(2));
        },

        async closeShift() {
            if (this.closingCash === '' || this.loading) return;

            const amount = parseFloat(this.closingCash) || 0;
            if (amount < 0) {
                this.error = 'Closing cash cannot be negative.';
                return;
            }

            const v = this.variance();
            if (Math.abs(v) > 100) {
                if (!confirm(`Cash variance is ₹${Math.abs(v).toFixed(2)} ${v > 0 ? 'OVER' : 'SHORT'}. Are you sure?`)) {
                    return;
                }
            }

            this.loading = true;
            this.error = '';

            try {
                const response = await axios.post('{{ route("pos.shift.close") }}', {
                    closing_cash: amount,
                    notes: this.notes || null,
                });

                if (response.data.success) {
                    window.location.href = response.data.redirect;
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to close shift.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
</x-pos.layout>
