<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\User;
use App\Models\Seller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Date range filter. The two dates arrive in the query string, where
        // anything at all can be typed: ?start_date=lastweek used to reach
        // Carbon::parse() unchecked and come back as a 500 rather than as an
        // unusable filter, and an end before the start silently returned an
        // empty report.
        // Both inputs carry max="today", so the future rules are what keeps the
        // server from accepting by URL what the picker will not offer - not an
        // extra restriction on top of it.
        $filters = $request->validate([
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:start_date'],
        ], [
            'start_date.before_or_equal' => 'The start date cannot be in the future.',
            'end_date.before_or_equal' => 'The end date cannot be in the future.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        $startDate = empty($filters['start_date']) ? null : Carbon::parse($filters['start_date'])->startOfDay();
        $endDate = empty($filters['end_date']) ? null : Carbon::parse($filters['end_date'])->endOfDay();
        $hasDateFilter = $startDate && $endDate;

        // Helper closure to apply date filter
        $dateFilter = function ($query) use ($startDate, $endDate, $hasDateFilter) {
            if ($hasDateFilter) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
            return $query;
        };

        // What counts as revenue lives on the model, so this tile and the
        // Sales Report cannot drift apart - and so cash-on-delivery orders,
        // which stay payment_status = "pending" until they are delivered, are
        // not silently reported as zero rupees. See Order::applySaleFilter().
        $revenueFilter = fn ($query) => $query->countsAsSale();

        // Top-row stats: filtered when date filter active, otherwise today
        if ($hasDateFilter) {
            $topOrders = $dateFilter(Order::query())->count();
            $topRevenue = $revenueFilter($dateFilter(Order::query()))->sum('total');
        } else {
            $topOrders = Order::whereDate('created_at', today())->count();
            $topRevenue = $revenueFilter(Order::whereDate('created_at', today()))->sum('total');
        }

        // Filtered stats
        $totalOrders = $dateFilter(Order::query())->count();
        $totalRevenue = $revenueFilter($dateFilter(Order::query()))->sum('total');
        $totalCustomers = $dateFilter(User::where('role', 'customer'))->count();
        $totalProducts = Product::count();
        $totalSellers = Seller::count();
        $pendingOrders = $dateFilter(Order::where('status', 'confirmed'))->count();

        // Returns stats
        $totalReturns = $dateFilter(OrderReturn::query())->count();
        $pendingReturns = $dateFilter(OrderReturn::where('status', 'requested'))->count();

        // Recent orders (filtered)
        $recentOrders = $dateFilter(Order::with(['user', 'items']))
            ->latest()
            ->take(10)
            ->get();

        // Top selling products (from actual paid order data)
        $topProductsQuery = Order::applySaleFilter(
            DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
        )
            ->select('products.id', 'products.name', 'products.price', DB::raw('SUM(order_items.quantity) as total_sold'));

        if ($hasDateFilter) {
            $topProductsQuery->whereBetween('orders.created_at', [$startDate, $endDate]);
        }

        $topProductIds = $topProductsQuery->groupBy('products.id', 'products.name', 'products.price')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // Get full product models with images for display
        $topProducts = collect();
        if ($topProductIds->isNotEmpty()) {
            $productModels = Product::with('images')->whereIn('id', $topProductIds->pluck('id'))->get()->keyBy('id');
            foreach ($topProductIds as $tp) {
                $product = $productModels->get($tp->id);
                if ($product) {
                    $product->total_sold = $tp->total_sold;
                    $topProducts->push($product);
                }
            }
        }

        // Sales chart data (paid orders, exclude cancelled/returned)
        if ($hasDateFilter) {
            $daysDiff = $startDate->diffInDays($endDate);
            $salesData = Order::countsAsSale()
                ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as count')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $chartLabels = [];
            $chartRevenue = [];
            $chartOrders = [];

            if ($daysDiff <= 31) {
                // Show daily for ranges up to 31 days
                for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
                    $dateStr = $d->format('Y-m-d');
                    $chartLabels[] = $d->format($daysDiff <= 7 ? 'D' : 'M d');
                    $dayData = $salesData->firstWhere('date', $dateStr);
                    $chartRevenue[] = $dayData ? round($dayData->total, 2) : 0;
                    $chartOrders[] = $dayData ? $dayData->count : 0;
                }
            } else {
                // Show weekly for longer ranges
                $weeklyData = Order::countsAsSale()
                    ->selectRaw('YEARWEEK(created_at, 1) as yw, MIN(DATE(created_at)) as week_start, SUM(total) as total, COUNT(*) as count')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->groupBy('yw')
                    ->orderBy('yw')
                    ->get();
                foreach ($weeklyData as $week) {
                    $chartLabels[] = Carbon::parse($week->week_start)->format('M d');
                    $chartRevenue[] = round($week->total, 2);
                    $chartOrders[] = $week->count;
                }
            }
        } else {
            // Default: last 7 days
            $salesData = Order::countsAsSale()
                ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as count')
                ->whereDate('orders.created_at', '>=', now()->subDays(6))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $chartLabels = [];
            $chartRevenue = [];
            $chartOrders = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $chartLabels[] = now()->subDays($i)->format('D');
                $dayData = $salesData->firstWhere('date', $date);
                $chartRevenue[] = $dayData ? round($dayData->total, 2) : 0;
                $chartOrders[] = $dayData ? $dayData->count : 0;
            }
        }

        // Order status distribution (filtered)
        $orderStatusCounts = $dateFilter(Order::selectRaw('status, COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Monthly revenue (last 6 months or within filter range, paid only)
        $monthQuery = Order::countsAsSale()
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(total) as total');
        if ($hasDateFilter) {
            $monthQuery->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $monthQuery->whereDate('created_at', '>=', now()->subMonths(5)->startOfMonth());
        }
        $monthlyRevenue = $monthQuery->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $monthLabels = [];
        $monthData = [];
        if ($hasDateFilter) {
            $mStart = $startDate->copy()->startOfMonth();
            $mEnd = $endDate->copy()->startOfMonth();
            for ($m = $mStart; $m->lte($mEnd); $m->addMonth()) {
                $monthLabels[] = $m->format('M Y');
                $found = $monthlyRevenue->first(fn($r) => $r->month == $m->month && $r->year == $m->year);
                $monthData[] = $found ? round($found->total, 2) : 0;
            }
        } else {
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthLabels[] = $date->format('M');
                $found = $monthlyRevenue->first(fn($r) => $r->month == $date->month && $r->year == $date->year);
                $monthData[] = $found ? round($found->total, 2) : 0;
            }
        }

        // Circle progress metrics (filtered)
        $completedOrders = $dateFilter(Order::where('status', 'delivered'))->count();
        $cancelledOrders = $dateFilter(Order::where('status', 'cancelled'))->count();
        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;
        $cancellationRate = $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100) : 0;
        $activeProducts = Product::where('is_active', true)->count();
        $productActiveRate = $totalProducts > 0 ? round(($activeProducts / $totalProducts) * 100) : 0;

        // Abandoned carts. Read back from the abandoned_carts table rather than
        // recomputed here, so this block and the Abandoned Carts screen can
        // never quote different numbers. Deliberately NOT date-filtered: the
        // recovery rate is a lifetime figure and slicing it by the dashboard's
        // window would make it swing wildly on a quiet week.
        $abandonedCartStats = app(\App\Services\AbandonedCartService::class)->stats();

        return view('admin.dashboard.index', compact(
            'abandonedCartStats',
            'topOrders',
            'topRevenue',
            'totalOrders',
            'totalRevenue',
            'totalCustomers',
            'totalProducts',
            'totalSellers',
            'pendingOrders',
            'totalReturns',
            'pendingReturns',
            'recentOrders',
            'topProducts',
            'chartLabels',
            'chartRevenue',
            'chartOrders',
            'orderStatusCounts',
            'monthLabels',
            'monthData',
            'completionRate',
            'cancellationRate',
            'productActiveRate',
            'completedOrders',
            'cancelledOrders',
            'activeProducts',
            'startDate',
            'endDate',
            'hasDateFilter'
        ));
    }
}
