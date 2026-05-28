<x-pos.layout>
<div class="pos-container" x-data="posReports()" x-init="init()">

    {{-- ═══════ HEADER ═══════ --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-4 sm:px-6 py-3" style="background: var(--pos-sidebar); color: white;" role="banner">
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.dashboard') }}" class="p-2 rounded-lg transition-colors" style="color: #CBD5E1;"
               @mouseenter="$el.style.background='rgba(255,255,255,0.1)'" @mouseleave="$el.style.background='transparent'"
               aria-label="Back to Dashboard">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-sm sm:text-base font-semibold">Reports</h1>
        </div>
        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <input type="date" x-model="dateFrom" @change="loadAll()" class="px-2 sm:px-3 py-1.5 rounded text-xs sm:text-sm" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.15);" aria-label="Date from">
                <span class="text-xs" style="color: #CBD5E1;">to</span>
                <input type="date" x-model="dateTo" @change="loadAll()" class="px-2 sm:px-3 py-1.5 rounded text-xs sm:text-sm" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.15);" aria-label="Date to">
            </div>
            <button @click="exportCSV()" class="px-3 py-1.5 rounded text-sm font-medium transition-colors" style="background: rgba(255,255,255,0.15); color: white;"
                    @mouseenter="$el.style.background='rgba(255,255,255,0.25)'" @mouseleave="$el.style.background='rgba(255,255,255,0.15)'"
                    aria-label="Export CSV">
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden sm:inline">Export CSV</span>
                </span>
            </button>
        </div>
    </div>

    {{-- ═══════ CONTENT ═══════ --}}
    <div class="flex-1 pos-scroll p-3 sm:p-6 space-y-4 sm:space-y-6" style="background: #F1F5F9;">

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4">
            <div class="pos-card p-3 sm:p-4">
                <div class="text-xs font-medium mb-1" style="color: var(--pos-text-muted);">Total Sales</div>
                <div class="text-lg sm:text-2xl font-bold pos-mono" style="color: var(--pos-text);" x-text="'₹' + formatNum(stats.total_sales)"></div>
                <div class="text-xs mt-1" style="color: var(--pos-success);" x-text="stats.total_bills + ' bills'"></div>
            </div>
            <div class="pos-card p-3 sm:p-4">
                <div class="text-xs font-medium mb-1" style="color: var(--pos-text-muted);">Avg Bill Value</div>
                <div class="text-lg sm:text-2xl font-bold pos-mono" style="color: var(--pos-text);" x-text="'₹' + formatNum(stats.avg_bill_value)"></div>
                <div class="text-xs mt-1" style="color: var(--pos-primary);">per transaction</div>
            </div>
            <div class="pos-card p-3 sm:p-4">
                <div class="text-xs font-medium mb-1" style="color: var(--pos-text-muted);">Returns</div>
                <div class="text-lg sm:text-2xl font-bold pos-mono" style="color: var(--pos-danger);" x-text="'₹' + formatNum(stats.total_returns)"></div>
                <div class="text-xs mt-1" style="color: var(--pos-danger);" x-text="stats.return_count + ' returns'"></div>
            </div>
            <div class="pos-card p-3 sm:p-4">
                <div class="text-xs font-medium mb-1" style="color: var(--pos-text-muted);">Net Revenue</div>
                <div class="text-lg sm:text-2xl font-bold pos-mono" style="color: var(--pos-success);" x-text="'₹' + formatNum(stats.total_sales - stats.total_returns)"></div>
                <div class="text-xs mt-1" style="color: var(--pos-text-muted);">after returns</div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Payment Breakdown --}}
            <div class="pos-card p-5">
                <h3 class="text-sm font-semibold mb-4" style="color: var(--pos-text);">Payment Breakdown</h3>
                <div class="space-y-3">
                    <template x-for="pm in paymentBreakdown" :key="pm.method">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-medium capitalize" x-text="pm.method"></span>
                                <span class="pos-mono" x-text="'₹' + formatNum(pm.total) + ' (' + pm.count + ')'"></span>
                            </div>
                            <div class="w-full h-2 rounded-full" style="background: #E2E8F0;">
                                <div class="h-full rounded-full transition-all" :style="'width:' + pm.pct + '%; background:' + pm.color"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Hourly Sales --}}
            <div class="pos-card p-5">
                <h3 class="text-sm font-semibold mb-4" style="color: var(--pos-text);">Hourly Sales (Today)</h3>
                <div class="flex items-end gap-1" style="height: 140px;">
                    <template x-for="(h, idx) in hourlySales" :key="idx">
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full rounded-t transition-all" :style="'height:' + h.pct + '%; background: var(--pos-primary); min-height: 2px;'"></div>
                            <span class="text-[8px] pos-mono" style="color: var(--pos-text-muted);" x-text="h.label"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Staff Performance & Top Products --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Staff Performance --}}
            <div class="pos-card p-5">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--pos-text);">Staff Performance</h3>
                <div class="space-y-2">
                    <template x-for="s in staffPerf" :key="s.name">
                        <div class="flex items-center justify-between p-2.5 rounded-lg" style="background: #F8FAFC;">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background: var(--pos-primary); color: white;"
                                     x-text="s.name[0].toUpperCase()"></div>
                                <div>
                                    <div class="text-sm font-medium" x-text="s.name"></div>
                                    <div class="text-xs" style="color: var(--pos-text-muted);" x-text="s.bills + ' bills'"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold pos-mono" x-text="'₹' + formatNum(s.total)"></div>
                                <div class="text-xs" style="color: var(--pos-text-muted);">Avg ₹<span x-text="formatNum(s.avg)" class="pos-mono"></span></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="staffPerf.length === 0" class="text-sm text-center py-4" style="color: var(--pos-text-muted);">No data for selected period.</div>
                </div>
            </div>

            {{-- Top Products --}}
            <div class="pos-card p-5">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--pos-text);">Top Selling Products</h3>
                <div class="space-y-1.5">
                    <template x-for="(p, idx) in topProducts" :key="idx">
                        <div class="flex items-center gap-3 p-2 rounded-lg" style="background: #F8FAFC;">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold"
                                  :style="idx < 3 ? 'background: var(--pos-accent); color: white;' : 'background: #E2E8F0; color: var(--pos-text-muted);'"
                                  x-text="idx + 1"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium truncate" x-text="p.name"></div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold pos-mono" x-text="p.qty + ' sold'"></span>
                                <span class="text-xs pos-mono ml-2" style="color: var(--pos-text-muted);" x-text="'₹' + formatNum(p.revenue)"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="topProducts.length === 0" class="text-sm text-center py-4" style="color: var(--pos-text-muted);">No data for selected period.</div>
                </div>
            </div>
        </div>

        {{-- Inventory Alerts --}}
        <div class="pos-card p-5">
            <h3 class="text-sm font-semibold mb-3" style="color: var(--pos-text);">Inventory Alerts</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                <template x-for="item in inventoryAlerts" :key="item.id">
                    <div class="flex items-center justify-between p-2.5 rounded-lg"
                         :style="item.stock <= 0 ? 'background: #FEF2F2; border: 1px solid #FECACA;' : 'background: #FFFBEB; border: 1px solid #FDE68A;'">
                        <div class="flex-1 min-w-0 mr-2">
                            <div class="text-xs font-medium truncate" x-text="item.name"></div>
                            <div class="text-[10px]" style="color: var(--pos-text-muted);" x-text="item.sku"></div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full whitespace-nowrap"
                              :style="item.stock <= 0 ? 'background: #FEE2E2; color: #7F1D1D;' : 'background: #FEF3C7; color: #78350F;'"
                              x-text="item.stock <= 0 ? 'Out of Stock' : item.stock + ' left'"></span>
                    </div>
                </template>
                <div x-show="inventoryAlerts.length === 0" class="col-span-full text-sm text-center py-4" style="color: var(--pos-text-muted);">All products well stocked.</div>
            </div>
        </div>

        {{-- Daily Transaction Table --}}
        <div class="pos-card p-5">
            <h3 class="text-sm font-semibold mb-3" style="color: var(--pos-text);">Transaction Log</h3>
            <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                <table class="w-full text-sm" style="min-width: 600px;" role="table" aria-label="Transaction log">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--pos-border);">
                            <th class="text-left py-2 px-2 text-xs font-semibold" style="color: var(--pos-text-muted);">Bill #</th>
                            <th class="text-left py-2 px-2 text-xs font-semibold" style="color: var(--pos-text-muted);">Time</th>
                            <th class="text-left py-2 px-2 text-xs font-semibold" style="color: var(--pos-text-muted);">Cashier</th>
                            <th class="text-left py-2 px-2 text-xs font-semibold" style="color: var(--pos-text-muted);">Customer</th>
                            <th class="text-right py-2 px-2 text-xs font-semibold" style="color: var(--pos-text-muted);">Items</th>
                            <th class="text-right py-2 px-2 text-xs font-semibold" style="color: var(--pos-text-muted);">Total</th>
                            <th class="text-center py-2 px-2 text-xs font-semibold" style="color: var(--pos-text-muted);">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="sale in dailySales" :key="sale.sale_number">
                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                <td class="py-2 px-2 pos-mono text-xs font-medium" x-text="sale.sale_number"></td>
                                <td class="py-2 px-2 text-xs" style="color: var(--pos-text-muted);" x-text="sale.time"></td>
                                <td class="py-2 px-2 text-xs" x-text="sale.cashier"></td>
                                <td class="py-2 px-2 text-xs" x-text="sale.customer"></td>
                                <td class="py-2 px-2 text-xs text-right pos-mono" x-text="sale.items_count"></td>
                                <td class="py-2 px-2 text-xs text-right font-bold pos-mono" x-text="'₹' + sale.total.toFixed(2)"></td>
                                <td class="py-2 px-2 text-center">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-medium capitalize"
                                          :style="{'cash':'background:#DCFCE7;color:#14532D;','card':'background:#DBEAFE;color:#1E3A5F;','upi':'background:#F3E8FF;color:#4A1D72;','split':'background:#FEF3C7;color:#78350F;'}[sale.payment_method]"
                                          x-text="sale.payment_method"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="dailySales.length === 0" class="text-sm text-center py-6" style="color: var(--pos-text-muted);">No transactions for selected date.</div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function posReports() {
    return {
        dateFrom: new Date().toISOString().split('T')[0],
        dateTo: new Date().toISOString().split('T')[0],
        stats: { total_sales: 0, total_bills: 0, total_returns: 0, return_count: 0, cash_sales: 0, card_sales: 0, upi_sales: 0, avg_bill_value: 0 },
        dailySales: [],
        paymentBreakdown: [],
        hourlySales: [],
        staffPerf: [],
        topProducts: [],
        inventoryAlerts: [],

        async init() {
            await this.loadAll();
        },

        async loadAll() {
            await Promise.all([
                this.loadStats(),
                this.loadDaily(),
                this.loadStaffPerformance(),
                this.loadTopProducts(),
                this.loadInventoryAlerts(),
            ]);
        },

        async loadStats() {
            try {
                const res = await axios.get('{{ route("pos.reports") }}', { headers: { 'Accept': 'application/json' } });
                this.stats = res.data.today || this.stats;
                this.buildPaymentBreakdown();
                this.buildHourlySales(res.data.hourly || []);
            } catch (e) { console.error('Stats load failed', e); }
        },

        async loadDaily() {
            try {
                const res = await axios.get('{{ route("pos.reports.daily") }}', { params: { date: this.dateTo } });
                this.dailySales = res.data.sales || [];
            } catch (e) { console.error('Daily load failed', e); }
        },

        async loadStaffPerformance() {
            try {
                const res = await axios.get('{{ url("/pos/reports/staff") }}', { params: { from: this.dateFrom, to: this.dateTo } });
                this.staffPerf = res.data.staff || [];
            } catch (e) { console.error('Staff perf failed', e); }
        },

        async loadTopProducts() {
            try {
                const res = await axios.get('{{ url("/pos/reports/top-products") }}', { params: { from: this.dateFrom, to: this.dateTo } });
                this.topProducts = res.data.products || [];
            } catch (e) { console.error('Top products failed', e); }
        },

        async loadInventoryAlerts() {
            try {
                const res = await axios.get('{{ url("/pos/reports/inventory-alerts") }}');
                this.inventoryAlerts = res.data.alerts || [];
            } catch (e) { console.error('Inventory alerts failed', e); }
        },

        buildPaymentBreakdown() {
            const colors = { cash: '#22C55E', card: '#3B82F6', upi: '#A855F7', split: '#F59E0B' };
            const total = this.stats.total_sales || 1;
            this.paymentBreakdown = ['cash', 'card', 'upi'].map(m => ({
                method: m,
                total: this.stats[m + '_sales'] || 0,
                count: '',
                pct: Math.round(((this.stats[m + '_sales'] || 0) / total) * 100),
                color: colors[m],
            }));
        },

        buildHourlySales(data) {
            const hours = [];
            const maxVal = Math.max(1, ...data.map(h => h.total));
            for (let i = 9; i <= 21; i++) {
                const found = data.find(h => h.hour === i);
                hours.push({
                    label: i >= 12 ? (i === 12 ? 12 : i-12) + 'P' : i + 'A',
                    total: found ? found.total : 0,
                    pct: found ? Math.round((found.total / maxVal) * 100) : 0,
                });
            }
            this.hourlySales = hours;
        },

        formatNum(n) {
            return (n || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
        },

        exportCSV() {
            if (this.dailySales.length === 0) { alert('No data to export.'); return; }
            let csv = 'Bill #,Time,Cashier,Customer,Items,Subtotal,Discount,Tax,Total,Payment\n';
            this.dailySales.forEach(s => {
                csv += `${s.sale_number},${s.time},${s.cashier},${s.customer},${s.items_count},${s.subtotal},${s.discount},${s.tax},${s.total},${s.payment_method}\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `pos-report-${this.dateTo}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        },
    };
}
</script>
@endpush
</x-pos.layout>
