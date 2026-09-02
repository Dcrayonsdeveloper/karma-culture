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
use App\Models\User;
use App\Support\ReportRange;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * The reporting window.
     *
     * This was a day count off a fixed "Last 7 / 30 / 90 days" allowlist. The
     * screens now take two dates the admin picks, so the window is whatever the
     * query string carries - ReportRange does the parsing and clamping,
     * including the length cap that keeps the day-by-day loops below from
     * running once per day since 1970. That hang was reachable before as
     * ?period=99999999 and an unbounded date range would bring it straight back.
     */
    private function range(Request $request): ReportRange
    {
        return ReportRange::fromRequest($request);
    }

    /**
     * The order_items that belong to a sale made inside the window - the
     * constraint the product table and both product exports share, so a
     * product's "sold" figure means the same thing wherever it is printed.
     */
    private function soldInPeriod(ReportRange $range): Closure
    {
        return fn ($query) => $query->whereHas(
            'order',
            fn ($q) => $q->countsAsSale()->whereBetween('orders.created_at', [$range->start, $range->end])
        );
    }

    public function sales(Request $request): View
    {
        $range = $this->range($request);
        [$startDate, $endDate] = [$range->start, $range->end];

        // Day-by-day takings. Order::applySaleFilter() decides what counts as a
        // sale - see the model; the short version is that a cash-on-delivery
        // order is a sale the moment it is placed, not weeks later when the
        // courier hands the money over.
        $salesByDay = Order::countsAsSale()
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Days with no orders have no row, and plotting only the days that sold
        // something drew a chart whose axis skipped straight from the 3rd to the
        // 12th as if they were neighbours. Fill the window so the timeline is
        // continuous and a quiet week reads as a quiet week.
        $salesData = collect();
        foreach ($range->eachDay() as $date) {
            $day = $salesByDay->get($date->format('Y-m-d'));
            $salesData->push([
                'date' => $date->format('Y-m-d'),
                'orders' => (int) ($day->orders ?? 0),
                'revenue' => (float) ($day->revenue ?? 0),
            ]);
        }

        // Summary stats
        $salesQuery = fn () => Order::countsAsSale()->whereBetween('orders.created_at', [$startDate, $endDate]);

        $stats = [
            'total_revenue' => $salesQuery()->sum('total'),
            'total_orders' => $salesQuery()->count(),
            'average_order' => $salesQuery()->avg('total') ?? 0,
            'items_sold' => Order::applySaleFilter(
                DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id')
            )->whereBetween('orders.created_at', [$startDate, $endDate])->sum('order_items.quantity'),
            // Counted in the revenue above, but not yet banked. Shown next to it
            // so nobody reads the headline figure as cash in hand.
            'awaiting_collection' => Order::awaitingCollection()
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->sum('total'),
        ];

        // Previous period comparison
        $prevRevenue = Order::countsAsSale()
            ->whereBetween('orders.created_at', [$range->previous()->start, $range->previous()->end])
            ->sum('total');

        $stats['revenue_change'] = $prevRevenue > 0
            ? (($stats['total_revenue'] - $prevRevenue) / $prevRevenue) * 100
            : ($stats['total_revenue'] > 0 ? 100 : 0);

        // Top selling products (by quantity sold)
        $topProducts = Product::withCount(['orderItems as sold' => function ($query) use ($startDate, $endDate) {
            $query->whereHas('order', fn ($q) => $q->countsAsSale()->whereBetween('orders.created_at', [$startDate, $endDate]));
        }])
            ->having('sold', '>', 0)
            ->orderByDesc('sold')
            ->take(10)
            ->get();

        // Sales by category
        $salesByCategory = Order::applySaleFilter(
            DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
        )
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('categories.name', DB::raw('SUM(order_items.total) as revenue'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();

        return view('admin.reports.sales', compact('salesData', 'stats', 'topProducts', 'salesByCategory', 'range'));
    }

    public function analytics(Request $request): View
    {
        $range = $this->range($request);
        [$startDate, $endDate] = [$range->start, $range->end];

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

        $completedOrders = Order::countsAsSale()
            ->whereBetween('orders.created_at', [$startDate, $endDate])
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

        return view('admin.reports.analytics', compact('trafficData', 'funnel', 'sources', 'devices', 'range'));
    }

    public function products(Request $request): View
    {
        $range = $this->range($request);
        [$startDate, $endDate] = [$range->start, $range->end];

        // Product performance
        $soldInPeriod = $this->soldInPeriod($range);

        $products = Product::withCount(['orderItems as sold' => $soldInPeriod])
            ->withSum(['orderItems as revenue' => $soldInPeriod], 'total')
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

        return view('admin.reports.products', compact('products', 'stats', 'categoryBreakdown', 'range'));
    }

    public function customers(Request $request): View
    {
        $range = $this->range($request);
        [$startDate, $endDate] = [$range->start, $range->end];

        // New vs returning
        $newCustomers = Customer::where('created_at', '>=', $startDate)->count();
        $returningCustomers = Order::countsAsSale()
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('user_id')
            ->distinct()
            ->whereHas('user', fn ($q) => $q->where('created_at', '<', $startDate))
            ->count();

        // Top customers
        $spentInPeriod = fn ($query) => $query->countsAsSale()->whereBetween('orders.created_at', [$startDate, $endDate]);

        $topCustomers = Customer::withCount(['orders as order_count' => $spentInPeriod])
            ->withSum(['orders as total_spent' => $spentInPeriod], 'total')
            ->orderByDesc('total_spent')
            ->take(10)
            ->get();

        // Customer stats
        $stats = [
            'total_customers' => Customer::count(),
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'average_lifetime_value' => Customer::withSum(['orders' => fn ($q) => $q->countsAsSale()], 'total')
                ->get()
                ->avg('orders_sum_total') ?? 0,
        ];

        // Customer growth
        $growth = Customer::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reports.customers', compact('stats', 'topCustomers', 'growth', 'range'));
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $range = $this->range($request);
        [$startDate, $endDate] = [$range->start, $range->end];
        $soldInPeriod = $this->soldInPeriod($range);

        $filename = "{$type}_report_" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($type, $startDate, $endDate, $soldInPeriod) {
            $handle = fopen('php://output', 'w');

            switch ($type) {
                case 'sales':
                    fputcsv($handle, ['Date', 'Orders', 'Revenue']);
                    Order::countsAsSale()
                        ->whereBetween('orders.created_at', [$startDate, $endDate])
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->each(function ($row) use ($handle) {
                            fputcsv($handle, [$row->date, $row->orders, $row->revenue]);
                        });
                    break;

                case 'products':
                    fputcsv($handle, ['Product', 'SKU', 'Stock', 'Price', 'Sales', 'Revenue']);
                    Product::withCount(['orderItems as sold' => $soldInPeriod])
                        ->withSum(['orderItems as revenue' => $soldInPeriod], 'total')
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
                    Customer::withCount(['orders' => fn ($q) => $q->countsAsSale()])
                        ->withSum(['orders' => fn ($q) => $q->countsAsSale()], 'total')
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
        $range = $this->range($request);
        [$startDate, $endDate] = [$range->start, $range->end];
        $soldInPeriod = $this->soldInPeriod($range);
        $exportService = new ReportExportService();

        switch ($type) {
            case 'sales':
                $headers = ['Date', 'Orders', 'Revenue'];
                $rows = Order::countsAsSale()
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(fn ($row) => [$row->date, $row->orders, $row->revenue]);
                break;

            case 'products':
                $headers = ['Product', 'SKU', 'Stock', 'Price', 'Sales', 'Revenue'];
                $rows = Product::withCount(['orderItems as sold' => $soldInPeriod])
                    ->withSum(['orderItems as revenue' => $soldInPeriod], 'total')
                    ->get()
                    ->map(fn ($p) => [$p->name, $p->sku, $p->stock_quantity, $p->price, $p->sold ?? 0, $p->revenue ?? 0]);
                break;

            case 'customers':
                $headers = ['Name', 'Email', 'Orders', 'Total Spent', 'Joined'];
                $rows = Customer::withCount(['orders' => fn ($q) => $q->countsAsSale()])
                    ->withSum(['orders' => fn ($q) => $q->countsAsSale()], 'total')
                    ->get()
                    ->map(fn ($c) => [$c->name, $c->email, $c->orders_count, $c->orders_sum_total ?? 0, $c->created_at->format('Y-m-d')]);
                break;

            default:
                abort(404);
        }

        return $exportService->exportExcel($headers, $rows, "{$type}_report_" . now()->format('Y-m-d') . '.xlsx', ucfirst($type));
    }
}
