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
        $viewsData = ProductView::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as pageviews, COUNT(DISTINCT COALESCE(user_id, session_id)) as visitors')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // A window may now span more than a year, and "Sep 03" on its own would
        // then appear twice on the axis for two different years.
        $axisFormat = $range->start->year === $range->end->year ? 'M d' : 'M d, y';

        $trafficData = collect();
        foreach ($range->eachDay() as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayData = $viewsData->get($dateStr);
            $trafficData->push([
                'date' => $date->format($axisFormat),
                'pageviews' => $dayData->pageviews ?? 0,
                'visitors' => $dayData->visitors ?? 0,
            ]);
        }

        $totalProductViews = ProductView::whereBetween('created_at', [$startDate, $endDate])->count();

        // The funnel counts *people*, named the same way at every stage.
        //
        // It used to compare populations that were not comparable: visitors
        // came from product_views, cart activity from carts, and "checkout" was
        // a count of orders rather than of the people who placed them. Dividing
        // one by another is how the live report came to show a 137.5%
        // view-to-cart rate and a 154.5% cart-to-order rate - percentages whose
        // denominator did not contain their numerator. Each stage below is a
        // set of visitor keys instead, so the denominators genuinely contain
        // the numerators and no rate can exceed 100%.
        $keysFrom = fn ($query, string $keySql) => $query
            ->selectRaw("{$keySql} as visitor_key")
            ->distinct()
            ->toBase()
            ->get()
            ->pluck('visitor_key')
            ->filter()
            ->unique()
            ->values();

        $viewerKeys = $keysFrom(
            ProductView::whereBetween('created_at', [$startDate, $endDate]),
            ProductView::visitorKeySql()
        );

        $cartKeys = $keysFrom(
            CartItem::whereBetween('cart_items.created_at', [$startDate, $endDate])
                ->join('carts', 'cart_items.cart_id', '=', 'carts.id'),
            ProductView::visitorKeySql('carts')
        );

        // Orders carry no session id, so a guest order cannot be tied back to
        // the session that browsed; it is keyed to itself and still counts as
        // one person who reached the end.
        $orderKeySql = "CASE WHEN orders.user_id IS NOT NULL
                             THEN CONCAT('u:', orders.user_id)
                             ELSE CONCAT('o:', orders.id) END";

        $ordererKeys = $keysFrom(
            Order::whereBetween('orders.created_at', [$startDate, $endDate]),
            $orderKeySql
        );

        $buyerKeys = $keysFrom(
            Order::countsAsSale()->whereBetween('orders.created_at', [$startDate, $endDate]),
            $orderKeySql
        );

        // Anyone who did any of these was on the site, so the union is the
        // visitor population every rate divides by. Defining it as a union is
        // what makes each later stage a subset rather than a separate
        // population - and so what bounds the percentages.
        $visitorKeys = $viewerKeys->merge($cartKeys)->merge($ordererKeys)->unique();

        $funnel = [
            'visitors' => $visitorKeys->count(),
            'product_views' => $totalProductViews,
            'viewers' => $viewerKeys->count(),
            'add_to_cart' => $cartKeys->count(),
            'checkout' => $ordererKeys->count(),
            'completed' => $buyerKeys->count(),
        ];

        // Orders are also worth showing as orders: one customer placing three
        // is three orders but one converted visitor, and the funnel above only
        // answers the second question.
        $ordersPlaced = Order::whereBetween('orders.created_at', [$startDate, $endDate])->count();

        // Every rate shares the visitor denominator, which each numerator is a
        // subset of. Cart-to-order is deliberately not shown as its own rate:
        // orders hold no session, so a guest order cannot be matched to the
        // guest cart it came from, and any such figure would be wrong in
        // whichever direction the store's guest checkout share pushed it.
        $rate = fn (int $part, int $whole) => $whole > 0 ? round(($part / $whole) * 100, 1) : 0.0;

        $rates = [
            'visitor_to_cart' => $rate($funnel['add_to_cart'], $funnel['visitors']),
            'visitor_to_order' => $rate($funnel['checkout'], $funnel['visitors']),
            'overall' => $rate($funnel['completed'], $funnel['visitors']),
        ];

        // One row per visitor: the first product view they made in the window.
        //
        // Sources and devices are attributes of a person, not of a page view.
        // Counting rows made a visitor who browsed twenty pages read as twenty
        // "visitors" from whatever referrer they happened to arrive with, so
        // the busiest browser drowned out everyone else.
        $firstTouch = fn () => ProductView::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw(ProductView::visitorKeySql() . ' as visitor_key, MIN(id) as first_id')
            ->groupBy('visitor_key')
            ->toBase();

        // Internal navigation arrives with our own domain in the referrer. That
        // is not a traffic source; without this it lands in "Referral" and
        // invents an inbound channel out of customers clicking around the shop.
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $internalClause = $host !== '' ? "WHEN referrer LIKE ? THEN 'Direct'" : '';
        $internalBinding = $host !== '' ? ['%' . $host . '%'] : [];

        $sourcesRaw = DB::table('product_views')
            ->joinSub($firstTouch(), 'ft', 'ft.first_id', '=', 'product_views.id')
            ->selectRaw("
                CASE
                    WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                    {$internalClause}
                    WHEN referrer LIKE '%google%' OR referrer LIKE '%bing%' OR referrer LIKE '%yahoo%' OR referrer LIKE '%duckduckgo%' THEN 'Organic Search'
                    WHEN referrer LIKE '%facebook%' OR referrer LIKE '%instagram%' OR referrer LIKE '%twitter%' OR referrer LIKE '%t.co/%' OR referrer LIKE '%youtube%' OR referrer LIKE '%pinterest%' THEN 'Social Media'
                    WHEN referrer LIKE '%mail%' OR referrer LIKE '%email%' THEN 'Email'
                    ELSE 'Referral'
                END as source,
                COUNT(*) as visitors
            ", $internalBinding)
            ->groupBy('source')
            ->orderByDesc('visitors')
            ->get();

        $totalSourceVisitors = $sourcesRaw->sum('visitors') ?: 1;
        $sources = $sourcesRaw->map(fn ($item) => [
            'source' => $item->source,
            'visitors' => (int) $item->visitors,
            'percentage' => round(($item->visitors / $totalSourceVisitors) * 100),
        ]);

        // Ensure all source types are present
        $sourceTypes = ['Organic Search', 'Direct', 'Social Media', 'Referral', 'Email'];
        foreach ($sourceTypes as $type) {
            if (!$sources->contains('source', $type)) {
                $sources->push(['source' => $type, 'visitors' => 0, 'percentage' => 0]);
            }
        }
        $sources = $sources->sortByDesc('visitors')->values();

        // Device split across all traffic, not just the handful of people who
        // reached checkout. Reading it off orders meant the card sat empty
        // until a sale landed, and then described buyers rather than visitors.
        $deviceCounts = DB::table('product_views')
            ->joinSub($firstTouch(), 'ft', 'ft.first_id', '=', 'product_views.id')
            ->whereNotNull('product_views.user_agent')
            ->selectRaw("
                CASE
                    WHEN user_agent LIKE '%iPad%' OR user_agent LIKE '%Tablet%' THEN 'tablet'
                    WHEN user_agent LIKE '%Mobi%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'mobile'
                    ELSE 'desktop'
                END as device,
                COUNT(*) as total
            ")
            ->groupBy('device')
            ->pluck('total', 'device');

        $deviceTotal = $deviceCounts->sum();
        $devices = [
            'mobile' => 0,
            'desktop' => 0,
            'tablet' => 0,
        ];

        if ($deviceTotal > 0) {
            foreach ($devices as $name => $_) {
                $devices[$name] = (int) round(($deviceCounts->get($name, 0) / $deviceTotal) * 100);
            }

            // Rounding three shares rarely lands on 100. Settle the remainder
            // on the largest share, where a point either way is invisible;
            // parking it on desktop could push an empty bucket to 1% or a
            // populated one negative.
            $largest = array_search(max($devices), $devices, true);
            $devices[$largest] += 100 - array_sum($devices);
        }

        return view('admin.reports.analytics', compact(
            'trafficData', 'funnel', 'rates', 'ordersPlaced', 'sources', 'devices', 'range'
        ));
    }

    public function products(Request $request): View
    {
        $range = $this->range($request);

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
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
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
        $growth = Customer::whereBetween('created_at', [$startDate, $endDate])
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
