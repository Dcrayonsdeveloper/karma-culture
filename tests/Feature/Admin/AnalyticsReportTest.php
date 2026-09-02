<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Analytics report showed percentages that cannot exist.
 *
 * Live, it read "View -> Cart Rate 137.5% (11 of 8 visitors)" and "Cart ->
 * Order Rate 154.5% (17 of 11 carts)". Both came from dividing one population
 * by a different one: visitors were counted from product_views, cart activity
 * from carts, and "checkout" was a count of *orders* rather than of the people
 * who placed them. Nothing constrained the numerator to be part of the
 * denominator, so the figures were free to exceed 100%.
 *
 * These tests reproduce that shape of data — someone who carts without a
 * tracked view in the window, someone who orders several times — and pin the
 * rates to a population every numerator is a subset of.
 */
class AnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Analytics Test',
            'slug' => 'analytics-test',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Analytics Tee',
            'slug' => 'analytics-tee',
            'sku' => 'ANL-001',
            'category_id' => $category->id,
            'price' => 500,
            'mrp' => 800,
            'stock_quantity' => 25,
            'is_active' => true,
        ]);
    }

    private function recordView(?User $user, ?string $sessionId, string $referrer = '', string $agent = 'Mozilla/5.0'): ProductView
    {
        return ProductView::create([
            'product_id' => $this->product->id,
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'referrer' => $referrer,
            'user_agent' => $agent,
        ]);
    }

    private function cartFor(?User $user, ?string $sessionId): CartItem
    {
        $cart = Cart::create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $sessionId,
        ]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
        ]);
    }

    private function placeOrder(?User $user, string $status = 'confirmed', string $paymentStatus = 'pending', string $method = 'cod'): Order
    {
        return Order::create([
            'order_number' => 'ANL-' . uniqid(),
            'user_id' => $user?->id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 500,
            'source' => 'web',
            'metadata' => ['payment_method' => $method],
        ]);
    }

    private function report(string $query = '')
    {
        return $this->actingAs($this->adminUser, 'admin')->get('/admin/reports/analytics' . $query);
    }

    /** The exact shape that produced 137.5% and 154.5% on the live site. */
    public function test_rates_cannot_exceed_one_hundred_percent(): void
    {
        // Two people browsed.
        $this->recordView(null, 'sess-a');
        $this->recordView(null, 'sess-b');

        // Three carts, two of them from people with no tracked view this
        // window — the old numerator that outgrew its denominator.
        $this->cartFor(null, 'sess-a');
        $this->cartFor(null, 'sess-c');
        $this->cartFor(null, 'sess-d');

        // One customer, four orders. The old code counted orders, not people.
        $buyer = User::factory()->create();
        foreach (range(1, 4) as $ignored) {
            $this->placeOrder($buyer);
        }

        $response = $this->report();
        $response->assertStatus(200);

        $rates = $response->viewData('rates');

        foreach ($rates as $name => $value) {
            $this->assertGreaterThanOrEqual(0, $value, "{$name} went negative.");
            $this->assertLessThanOrEqual(100, $value, "{$name} exceeded 100% — the bug this test exists for.");
        }
    }

    /** Visitors is the union, so every later stage is a subset of it. */
    public function test_every_funnel_stage_fits_inside_the_visitor_count(): void
    {
        $this->recordView(null, 'sess-a');
        $this->cartFor(null, 'sess-c');
        $this->placeOrder(User::factory()->create());

        $funnel = $this->report()->viewData('funnel');

        $this->assertSame(3, $funnel['visitors'], 'One viewer, one carter and one buyer are three people.');

        foreach (['viewers', 'add_to_cart', 'checkout', 'completed'] as $stage) {
            $this->assertLessThanOrEqual(
                $funnel['visitors'],
                $funnel[$stage],
                "Stage '{$stage}' claimed more people than visited."
            );
        }
    }

    /** One person, many visits, is one visitor — and many product views. */
    public function test_repeat_views_count_as_views_but_not_as_visitors(): void
    {
        $this->recordView(null, 'sess-a');
        $this->recordView(null, 'sess-a');
        $this->recordView(null, 'sess-a');

        $funnel = $this->report()->viewData('funnel');

        $this->assertSame(3, $funnel['product_views']);
        $this->assertSame(1, $funnel['visitors']);
    }

    /**
     * A signed-in customer is one visitor, not one per session.
     *
     * COALESCE(user_id, session_id) also let user 5 and a session literally
     * named "5" collapse into a single visitor; the keys are prefixed now.
     */
    public function test_a_signed_in_customer_is_one_visitor_across_sessions(): void
    {
        $customer = User::factory()->create();

        $this->recordView($customer, 'sess-one');
        $this->recordView($customer, 'sess-two');

        $this->assertSame(1, $this->report()->viewData('funnel')['visitors']);
    }

    /** COD is a sale the moment it is placed — this store is COD-first. */
    public function test_cash_on_delivery_orders_count_as_completed(): void
    {
        $this->placeOrder(User::factory()->create(), 'confirmed', 'pending', 'cod');

        $this->assertSame(1, $this->report()->viewData('funnel')['completed']);
    }

    public function test_cancelled_orders_are_not_completed(): void
    {
        $this->placeOrder(User::factory()->create(), 'cancelled', 'pending', 'cod');

        $funnel = $this->report()->viewData('funnel');

        $this->assertSame(1, $funnel['checkout'], 'It was still an order that was placed.');
        $this->assertSame(0, $funnel['completed']);
    }

    public function test_a_custom_date_range_bounds_both_ends(): void
    {
        $this->recordView(null, 'sess-old')->forceFill(['created_at' => now()->subDays(20)])->save();
        $this->recordView(null, 'sess-inside')->forceFill(['created_at' => now()->subDays(5)])->save();
        $this->recordView(null, 'sess-today');

        $from = now()->subDays(7)->format('Y-m-d');
        $to = now()->subDays(2)->format('Y-m-d');

        $response = $this->report("?from={$from}&to={$to}");
        $response->assertStatus(200);

        $this->assertSame(1, $response->viewData('funnel')['product_views'], 'Only the view inside the window counts.');
        $this->assertCount(6, $response->viewData('trafficData'), 'The window is inclusive at both ends.');
    }

    public function test_a_reversed_range_is_read_the_way_round_it_was_meant(): void
    {
        $from = now()->subDays(2)->format('Y-m-d');
        $to = now()->subDays(9)->format('Y-m-d');

        $range = $this->report("?from={$from}&to={$to}")->viewData('range');

        $this->assertSame($to, $range->fromDate());
        $this->assertSame($from, $range->toDate());
    }

    public function test_a_preset_window_is_inclusive_of_today(): void
    {
        $range = $this->report('?period=7')->viewData('range');

        $this->assertSame(7, $range->days());
        $this->assertSame(now()->format('Y-m-d'), $range->toDate());
        $this->assertSame(now()->subDays(6)->format('Y-m-d'), $range->fromDate());
    }

    /** Internal navigation is not an inbound traffic source. */
    public function test_a_referrer_from_our_own_site_is_direct(): void
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        $this->recordView(null, 'sess-internal', "https://{$host}/shop");
        $this->recordView(null, 'sess-google', 'https://www.google.com/search?q=tee');

        $sources = $this->report()->viewData('sources')->keyBy('source');

        $this->assertSame(1, $sources['Direct']['visitors']);
        $this->assertSame(1, $sources['Organic Search']['visitors']);
        $this->assertSame(0, $sources['Referral']['visitors']);
    }

    /** Device split describes visitors, and always totals 100%. */
    public function test_device_split_covers_all_traffic_and_totals_one_hundred(): void
    {
        $this->recordView(null, 'sess-m', '', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Mobile/15E148');
        $this->recordView(null, 'sess-t', '', 'Mozilla/5.0 (iPad; CPU OS 17_0) Safari/604.1');
        $this->recordView(null, 'sess-d', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120');

        $devices = $this->report()->viewData('devices');

        $this->assertSame(100, array_sum($devices), 'Rounding must still land on 100%.');
        foreach ($devices as $name => $share) {
            $this->assertGreaterThan(0, $share, "No traffic attributed to {$name}.");
        }
    }

    public function test_guest_product_views_are_recorded_by_the_storefront(): void
    {
        $this->get('/product/' . $this->product->slug)->assertStatus(200);

        $this->assertDatabaseCount('product_views', 1);
        $this->assertSame(1, $this->report()->viewData('funnel')['visitors']);
    }

    public function test_crawlers_are_not_counted_as_visitors(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->get('/product/' . $this->product->slug)
            ->assertStatus(200);

        $this->assertDatabaseCount('product_views', 0);
    }
}
