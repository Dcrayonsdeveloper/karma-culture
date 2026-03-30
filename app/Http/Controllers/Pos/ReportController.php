<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosReturn;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        // Restrict all reports to managers and supervisors only
        $this->middleware(function ($request, $next) {
            $role = $request->session()->get('pos_staff_role', '');
            if (! in_array($role, ['manager', 'supervisor'])) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Reports are restricted to managers and supervisors.'], 403)
                    : abort(403, 'Reports are restricted to managers and supervisors.');
            }
            return $next($request);
        });
    }

    /**
     * Reports dashboard — returns view for browser, JSON for AJAX.
     */
    public function index(Request $request)
    {
        $storeId = $request->session()->get('pos_store_id');
        $today = today();

        $todaySales = PosSale::where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today);

        $todayReturns = PosReturn::where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today);

        $stats = [
            'total_sales'    => (float) (clone $todaySales)->sum('total'),
            'total_bills'    => (clone $todaySales)->count(),
            'total_returns'  => (float) (clone $todayReturns)->sum('amount'),
            'return_count'   => (clone $todayReturns)->count(),
            'cash_sales'     => (float) (clone $todaySales)->where('payment_method', 'cash')->sum('total'),
            'card_sales'     => (float) (clone $todaySales)->where('payment_method', 'card')->sum('total'),
            'upi_sales'      => (float) (clone $todaySales)->where('payment_method', 'upi')->sum('total'),
            'avg_bill_value' => (float) (clone $todaySales)->avg('total'),
        ];

        // Hourly breakdown for today
        $hourly = PosSale::where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->selectRaw('HOUR(created_at) as hour, SUM(total) as total, COUNT(*) as count')
            ->groupByRaw('HOUR(created_at)')
            ->orderBy('hour')
            ->get()
            ->map(fn ($h) => [
                'hour'  => (int) $h->hour,
                'total' => (float) $h->total,
                'count' => (int) $h->count,
            ])
            ->toArray();

        if ($request->wantsJson()) {
            return response()->json([
                'today'  => $stats,
                'hourly' => $hourly,
            ]);
        }

        // Return view for non-AJAX
        $staff = \App\Models\Staff::find($request->session()->get('pos_staff_id'));
        $store = \App\Models\Store::find($storeId);
        $register = \App\Models\PosRegister::find($request->session()->get('pos_register_id'));

        return view('pos.reports', compact('staff', 'store', 'register'));
    }

    /**
     * Daily sales report.
     */
    public function daily(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $date = $request->input('date', today()->format('Y-m-d'));

        $sales = PosSale::with(['items', 'staff.user', 'customer'])
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PosSale $sale) => [
                'sale_number'    => $sale->sale_number,
                'time'           => $sale->created_at->format('g:i A'),
                'cashier'        => $sale->staff?->user?->first_name ?? 'Staff',
                'customer'       => $sale->customer?->first_name ?? $sale->customer?->name ?? 'Walk-in',
                'items_count'    => $sale->items->sum('quantity'),
                'subtotal'       => (float) $sale->subtotal,
                'discount'       => (float) $sale->discount,
                'tax'            => (float) $sale->tax,
                'total'          => (float) $sale->total,
                'payment_method' => $sale->payment_method,
            ]);

        $totals = [
            'gross'    => $sales->sum('subtotal'),
            'discount' => $sales->sum('discount'),
            'tax'      => $sales->sum('tax'),
            'net'      => $sales->sum('total'),
            'count'    => $sales->count(),
        ];

        return response()->json([
            'date'   => $date,
            'sales'  => $sales,
            'totals' => $totals,
        ]);
    }

    /**
     * GST report for the given date range.
     */
    public function gst(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $from = $request->input('from', today()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', today()->format('Y-m-d'));

        $items = PosSaleItem::whereHas('sale', function ($q) use ($storeId, $from, $to) {
                $q->where('store_id', $storeId)
                  ->where('status', 'completed')
                  ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
            })
            ->where('tax_rate', '>', 0)
            ->selectRaw('hsn_code, tax_rate, SUM(total) as taxable_value, SUM(cgst) as total_cgst, SUM(sgst) as total_sgst, SUM(igst) as total_igst, SUM(tax) as total_tax')
            ->groupBy('hsn_code', 'tax_rate')
            ->orderBy('hsn_code')
            ->get();

        return response()->json([
            'from'        => $from,
            'to'          => $to,
            'items'       => $items,
            'total_cgst'  => round((float) $items->sum('total_cgst'), 2),
            'total_sgst'  => round((float) $items->sum('total_sgst'), 2),
            'total_igst'  => round((float) $items->sum('total_igst'), 2),
            'total_tax'   => round((float) $items->sum('total_tax'), 2),
        ]);
    }

    /**
     * Staff performance report.
     */
    public function staff(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $from = $request->input('from', today()->format('Y-m-d'));
        $to = $request->input('to', today()->format('Y-m-d'));

        $staff = PosSale::where('pos_sales.store_id', $storeId)
            ->where('pos_sales.status', 'completed')
            ->whereBetween('pos_sales.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->join('staff', 'pos_sales.staff_id', '=', 'staff.id')
            ->join('users', 'staff.user_id', '=', 'users.id')
            ->selectRaw('users.first_name as name, staff.id as staff_id, COUNT(*) as bills, SUM(pos_sales.total) as total, AVG(pos_sales.total) as avg')
            ->groupBy('staff.id', 'users.first_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($s) => [
                'name'  => $s->name ?: 'Staff',
                'bills' => (int) $s->bills,
                'total' => (float) $s->total,
                'avg'   => (float) $s->avg,
            ]);

        return response()->json(['staff' => $staff]);
    }

    /**
     * Top selling products.
     */
    public function topProducts(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $from = $request->input('from', today()->format('Y-m-d'));
        $to = $request->input('to', today()->format('Y-m-d'));

        $products = PosSaleItem::whereHas('sale', function ($q) use ($storeId, $from, $to) {
                $q->where('store_id', $storeId)
                  ->where('status', 'completed')
                  ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
            })
            ->selectRaw('product_name as name, SUM(quantity) as qty, SUM(total) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'name'    => $p->name,
                'qty'     => (int) $p->qty,
                'revenue' => (float) $p->revenue,
            ]);

        return response()->json(['products' => $products]);
    }

    /**
     * Inventory alerts — low stock and out-of-stock products.
     */
    public function inventoryAlerts(): JsonResponse
    {
        $alerts = Product::query()
            ->where('status', 'approved')
            ->where('is_active', true)
            ->where('stock_quantity', '<=', 10)
            ->orderBy('stock_quantity')
            ->limit(30)
            ->get(['id', 'name', 'sku', 'stock_quantity'])
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'sku'   => $p->sku,
                'stock' => (int) $p->stock_quantity,
            ]);

        return response()->json(['alerts' => $alerts]);
    }

    /**
     * Monthly aggregation report.
     */
    public function monthly(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $year = $request->input('year', now()->year);

        $monthly = PosSale::where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as bills, SUM(total) as revenue, SUM(tax) as tax, SUM(discount) as discount')
            ->groupByRaw('MONTH(created_at)')
            ->orderBy('month')
            ->get()
            ->map(fn ($m) => [
                'month'    => (int) $m->month,
                'bills'    => (int) $m->bills,
                'revenue'  => (float) $m->revenue,
                'tax'      => (float) $m->tax,
                'discount' => (float) $m->discount,
            ]);

        return response()->json([
            'year'    => $year,
            'monthly' => $monthly,
            'total'   => [
                'bills'    => $monthly->sum('bills'),
                'revenue'  => $monthly->sum('revenue'),
                'tax'      => $monthly->sum('tax'),
                'discount' => $monthly->sum('discount'),
            ],
        ]);
    }

    /**
     * Payment method breakdown.
     */
    public function paymentBreakdown(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $from = $request->input('from', today()->format('Y-m-d'));
        $to = $request->input('to', today()->format('Y-m-d'));

        $breakdown = PosSale::where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($p) => [
                'method' => $p->payment_method,
                'count'  => (int) $p->count,
                'total'  => (float) $p->total,
            ]);

        return response()->json(['breakdown' => $breakdown]);
    }

    /**
     * Export report data as CSV.
     */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $type = $request->input('type', 'daily');
        $from = $request->input('from', today()->format('Y-m-d'));
        $to = $request->input('to', today()->format('Y-m-d'));

        $filename = "pos_{$type}_report_{$from}_to_{$to}.csv";

        return response()->streamDownload(function () use ($storeId, $type, $from, $to) {
            $handle = fopen('php://output', 'w');

            if ($type === 'daily') {
                fputcsv($handle, ['Sale #', 'Time', 'Cashier', 'Customer', 'Items', 'Subtotal', 'Discount', 'Tax', 'Total', 'Payment']);
                PosSale::with(['items', 'staff.user', 'customer'])
                    ->where('store_id', $storeId)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orderBy('created_at')
                    ->each(function (PosSale $sale) use ($handle) {
                        fputcsv($handle, [
                            $sale->sale_number,
                            $sale->created_at->format('Y-m-d g:i A'),
                            $sale->staff?->user?->first_name ?? 'Staff',
                            $sale->customer?->first_name ?? 'Walk-in',
                            $sale->items->sum('quantity'),
                            $sale->subtotal,
                            $sale->discount,
                            $sale->tax,
                            $sale->total,
                            $sale->payment_method,
                        ]);
                    });
            } elseif ($type === 'gst') {
                fputcsv($handle, ['HSN Code', 'Tax Rate %', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total Tax']);
                PosSaleItem::whereHas('sale', fn ($q) => $q->where('store_id', $storeId)
                        ->where('status', 'completed')
                        ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']))
                    ->where('tax_rate', '>', 0)
                    ->selectRaw('hsn_code, tax_rate, SUM(total) as taxable_value, SUM(cgst) as total_cgst, SUM(sgst) as total_sgst, SUM(igst) as total_igst, SUM(tax) as total_tax')
                    ->groupBy('hsn_code', 'tax_rate')
                    ->orderBy('hsn_code')
                    ->each(function ($item) use ($handle) {
                        fputcsv($handle, [$item->hsn_code, $item->tax_rate, $item->taxable_value, $item->total_cgst, $item->total_sgst, $item->total_igst, $item->total_tax]);
                    });
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
