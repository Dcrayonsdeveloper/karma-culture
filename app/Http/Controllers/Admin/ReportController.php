<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportExportService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);
        $excludedStatuses = ['cancelled', 'returned'];

        // Sales overview (paid orders, exclude cancelled/returned)
        $salesData = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Summary stats
        $paidOrdersQuery = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses);

        $stats = [
            'total_revenue' => (clone $paidOrdersQuery)->sum('total'),
            'total_orders' => (clone $paidOrdersQuery)->count(),
            'average_order' => (clone $paidOrdersQuery)->avg('total') ?? 0,
            'items_sold' => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.created_at', '>=', $startDate)
                ->where('orders.payment_status', 'paid')
                ->whereNotIn('orders.status', $excludedStatuses)
                ->sum('order_items.quantity'),
        ];

        // Previous period comparison
        $prevStartDate = now()->subDays($period * 2);
        $prevEndDate = now()->subDays($period);
        $prevRevenue = Order::whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->sum('total');

        $stats['revenue_change'] = $prevRevenue > 0
            ? (($stats['total_revenue'] - $prevRevenue) / $prevRevenue) * 100
            : ($stats['total_revenue'] > 0 ? 100 : 0);

        // Top selling products (by quantity sold)
        $topProducts = Product::withCount(['orderItems as sold' => function ($query) use ($startDate, $excludedStatuses) {
            $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->whereNotIn('status', $excludedStatuses));
        }])
            ->having('sold', '>', 0)
            ->orderByDesc('sold')
            ->take(10)
            ->get();

        // Sales by category
        $salesByCategory = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.status', $excludedStatuses)
            ->select('categories.name', DB::raw('SUM(order_items.total) as revenue'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();

        return view('admin.reports.sales', compact('salesData', 'stats', 'topProducts', 'salesByCategory', 'period'));
    }

    public function analytics(Request $request): View
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);
        $excludedStatuses = ['cancelled', 'returned'];

        // Real traffic data from product_views table
        $viewsData = ProductView::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as pageviews, COUNT(DISTINCT COALESCE(user_id, session_id)) as visitors')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trafficData = collect();
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $dayData = $viewsData->get($dateStr);
            $trafficData->push([
                'date' => $date->format('M d'),
                'pageviews' => $dayData->pageviews ?? 0,
                'visitors' => $dayData->visitors ?? 0,
            ]);
        }

        // Real conversion funnel from actual data
        $totalVisitors = ProductView::where('created_at', '>=', $startDate)
            ->distinct()
            ->count(DB::raw('COALESCE(user_id, session_id)'));

        $totalProductViews = ProductView::where('created_at', '>=', $startDate)->count();

        $addToCartUsers = CartItem::where('cart_items.created_at', '>=', $startDate)
            ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
            ->distinct()
            ->count(DB::raw('COALESCE(carts.user_id, carts.session_id)'));

        $checkoutOrders = Order::where('created_at', '>=', $startDate)->count();

        $completedOrders = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->count();

        $funnel = [
            'visitors' => $totalVisitors,
            'product_views' => $totalProductViews,
            'add_to_cart' => $addToCartUsers,
            'checkout' => $checkoutOrders,
            'completed' => $completedOrders,
        ];

        // Real traffic sources from referrer data
        $sourcesRaw = ProductView::where('created_at', '>=', $startDate)
            ->selectRaw("
                CASE
                    WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                    WHEN referrer LIKE '%google%' OR referrer LIKE '%bing%' OR referrer LIKE '%yahoo%' THEN 'Organic Search'
                    WHEN referrer LIKE '%facebook%' OR referrer LIKE '%instagram%' OR referrer LIKE '%twitter%' OR referrer LIKE '%youtube%' THEN 'Social Media'
                    WHEN referrer LIKE '%mail%' OR referrer LIKE '%email%' THEN 'Email'
                    ELSE 'Referral'
                END as source,
                COUNT(*) as visitors
            ")
            ->groupBy('source')
            ->orderByDesc('visitors')
            ->get();

        $totalSourceVisitors = $sourcesRaw->sum('visitors') ?: 1;
        $sources = $sourcesRaw->map(function ($item) use ($totalSourceVisitors) {
            return [
                'source' => $item->source,
                'visitors' => $item->visitors,
                'percentage' => round(($item->visitors / $totalSourceVisitors) * 100),
            ];
        });

        // Ensure all source types are present
        $sourceTypes = ['Organic Search', 'Direct', 'Social Media', 'Referral', 'Email'];
        foreach ($sourceTypes as $type) {
            if (!$sources->contains('source', $type)) {
                $sources->push(['source' => $type, 'visitors' => 0, 'percentage' => 0]);
            }
        }
        $sources = $sources->sortByDesc('visitors')->values();

        // Real order source data for device breakdown
        $orderSources = Order::where('created_at', '>=', $startDate)
            ->whereNotNull('user_agent')
            ->selectRaw("
                CASE
                    WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'mobile'
                    WHEN user_agent LIKE '%iPad%' OR user_agent LIKE '%Tablet%' THEN 'tablet'
                    ELSE 'desktop'
                END as device,
                COUNT(*) as total
            ")
            ->groupBy('device')
            ->pluck('total', 'device');

        $totalDevices = $orderSources->sum() ?: 1;
        $devices = [
            'mobile' => round(($orderSources->get('mobile', 0) / $totalDevices) * 100),
            'desktop' => round(($orderSources->get('desktop', 0) / $totalDevices) * 100),
            'tablet' => round(($orderSources->get('tablet', 0) / $totalDevices) * 100),
        ];

        // Ensure percentages sum to 100 if we have data
        if ($orderSources->sum() > 0) {
            $diff = 100 - array_sum($devices);
            $devices['desktop'] += $diff; // adjust rounding to desktop
        }

        return view('admin.reports.analytics', compact('trafficData', 'funnel', 'sources', 'devices', 'period'));
    }

    public function products(Request $request): View
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);
        $excludedStatuses = ['cancelled', 'returned'];

        // Product performance
        $products = Product::withCount(['orderItems as sold' => function ($query) use ($startDate, $excludedStatuses) {
            $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->whereNotIn('status', $excludedStatuses));
        }])
            ->withSum(['orderItems as revenue' => function ($query) use ($startDate, $excludedStatuses) {
                $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startDate)
                    ->where('payment_status', 'paid')
                    ->whereNotIn('status', $excludedStatuses));
            }], 'total')
            ->orderByDesc('sold')
            ->paginate($request->input('per_page', 10))->withQueryString();

        // Stats
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'out_of_stock' => Product::where('stock_quantity', 0)->count(),
            'low_stock' => Product::where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10)->count(),
        ];

        // Category breakdown
        $categoryBreakdown = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('products.deleted_at')
            ->select('categories.name', DB::raw('COUNT(products.id) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        return view('admin.reports.products', compact('products', 'stats', 'categoryBreakdown', 'period'));
    }

    public function customers(Request $request): View
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);
        $excludedStatuses = ['cancelled', 'returned'];

        // New vs returning
        $newCustomers = Customer::where('created_at', '>=', $startDate)->count();
        $returningCustomers = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->select('user_id')
            ->distinct()
            ->whereHas('user', fn ($q) => $q->where('created_at', '<', $startDate))
            ->count();

        // Top customers
        $topCustomers = Customer::withCount(['orders as order_count' => function ($query) use ($startDate, $excludedStatuses) {
            $query->where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->whereNotIn('status', $excludedStatuses);
        }])
            ->withSum(['orders as total_spent' => function ($query) use ($startDate, $excludedStatuses) {
                $query->where('created_at', '>=', $startDate)
                    ->where('payment_status', 'paid')
                    ->whereNotIn('status', $excludedStatuses);
            }], 'total')
            ->orderByDesc('total_spent')
            ->take(10)
            ->get();

        // Customer stats
        $stats = [
            'total_customers' => Customer::count(),
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'average_lifetime_value' => Customer::withSum(['orders' => fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('status', $excludedStatuses)], 'total')
                ->get()
                ->avg('orders_sum_total') ?? 0,
        ];

        // Customer growth
        $growth = Customer::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reports.customers', compact('stats', 'topCustomers', 'growth', 'period'));
    }

    public function sellers(Request $request): View
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);
        $excludedStatuses = ['cancelled', 'returned'];

        // Top sellers by order revenue (using DB query to avoid N+1)
        $topSellersData = DB::table('orders')
            ->join('sellers', 'orders.seller_id', '=', 'sellers.id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.status', $excludedStatuses)
            ->where('sellers.status', 'active')
            ->select('sellers.id', DB::raw('SUM(orders.total) as total_sales'))
            ->groupBy('sellers.id')
            ->orderByDesc('total_sales')
            ->take(10)
            ->get();

        $topSellers = collect();
        if ($topSellersData->isNotEmpty()) {
            $sellerModels = Seller::with('user')->withCount('products')
                ->whereIn('id', $topSellersData->pluck('id'))
                ->get()
                ->keyBy('id');

            foreach ($topSellersData as $ts) {
                $seller = $sellerModels->get($ts->id);
                if ($seller) {
                    $seller->total_sales = $ts->total_sales;
                    $topSellers->push($seller);
                }
            }
        }

        // Seller stats
        $stats = [
            'total_sellers' => Seller::count(),
            'active_sellers' => Seller::where('status', 'active')->count(),
            'pending_sellers' => Seller::where('status', 'pending')->count(),
            'new_sellers' => Seller::where('created_at', '>=', $startDate)->count(),
        ];

        // Seller performance (all active sellers)
        $sellers = Seller::where('status', 'active')
            ->withCount('products')
            ->with('user')
            ->paginate($request->input('per_page', 10))->withQueryString();

        return view('admin.reports.sellers', compact('topSellers', 'stats', 'sellers', 'period'));
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);
        $excludedStatuses = ['cancelled', 'returned'];

        $filename = "{$type}_report_" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($type, $startDate, $excludedStatuses) {
            $handle = fopen('php://output', 'w');

            switch ($type) {
                case 'sales':
                    fputcsv($handle, ['Date', 'Orders', 'Revenue']);
                    Order::where('created_at', '>=', $startDate)
                        ->where('payment_status', 'paid')
                        ->whereNotIn('status', $excludedStatuses)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->each(function ($row) use ($handle) {
                            fputcsv($handle, [$row->date, $row->orders, $row->revenue]);
                        });
                    break;

                case 'products':
                    fputcsv($handle, ['Product', 'SKU', 'Stock', 'Price', 'Sales', 'Revenue']);
                    Product::withCount(['orderItems as sold' => function ($query) use ($startDate, $excludedStatuses) {
                        $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startDate)
                            ->where('payment_status', 'paid')
                            ->whereNotIn('status', $excludedStatuses));
                    }])
                        ->withSum(['orderItems as revenue' => function ($query) use ($startDate, $excludedStatuses) {
                            $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startDate)
                                ->where('payment_status', 'paid')
                                ->whereNotIn('status', $excludedStatuses));
                        }], 'total')
                        ->each(function ($product) use ($handle) {
                            fputcsv($handle, [
                                $product->name,
                                $product->sku,
                                $product->stock_quantity,
                                $product->price,
                                $product->sold ?? 0,
                                $product->revenue ?? 0,
                            ]);
                        });
                    break;

                case 'customers':
                    fputcsv($handle, ['Name', 'Email', 'Orders', 'Total Spent', 'Joined']);
                    Customer::withCount(['orders' => fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('status', $excludedStatuses)])
                        ->withSum(['orders' => fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('status', $excludedStatuses)], 'total')
                        ->each(function ($customer) use ($handle) {
                            fputcsv($handle, [
                                $customer->name,
                                $customer->email,
                                $customer->orders_count,
                                $customer->orders_sum_total ?? 0,
                                $customer->created_at->format('Y-m-d'),
                            ]);
                        });
                    break;
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(Request $request, string $type): StreamedResponse
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);
        $excludedStatuses = ['cancelled', 'returned'];
        $exportService = new ReportExportService();

        switch ($type) {
            case 'sales':
                $headers = ['Date', 'Orders', 'Revenue'];
                $rows = Order::where('created_at', '>=', $startDate)
                    ->where('payment_status', 'paid')
                    ->whereNotIn('status', $excludedStatuses)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(fn ($row) => [$row->date, $row->orders, $row->revenue]);
                break;

            case 'products':
                $headers = ['Product', 'SKU', 'Stock', 'Price', 'Sales', 'Revenue'];
                $rows = Product::withCount(['orderItems as sold' => function ($query) use ($startDate, $excludedStatuses) {
                    $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startDate)
                        ->where('payment_status', 'paid')
                        ->whereNotIn('status', $excludedStatuses));
                }])
                    ->withSum(['orderItems as revenue' => function ($query) use ($startDate, $excludedStatuses) {
                        $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startDate)
                            ->where('payment_status', 'paid')
                            ->whereNotIn('status', $excludedStatuses));
                    }], 'total')
                    ->get()
                    ->map(fn ($p) => [$p->name, $p->sku, $p->stock_quantity, $p->price, $p->sold ?? 0, $p->revenue ?? 0]);
                break;

            case 'customers':
                $headers = ['Name', 'Email', 'Orders', 'Total Spent', 'Joined'];
                $rows = Customer::withCount(['orders' => fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('status', $excludedStatuses)])
                    ->withSum(['orders' => fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('status', $excludedStatuses)], 'total')
                    ->get()
                    ->map(fn ($c) => [$c->name, $c->email, $c->orders_count, $c->orders_sum_total ?? 0, $c->created_at->format('Y-m-d')]);
                break;

            default:
                abort(404);
        }

        return $exportService->exportExcel($headers, $rows, "{$type}_report_" . now()->format('Y-m-d') . '.xlsx', ucfirst($type));
    }
}
