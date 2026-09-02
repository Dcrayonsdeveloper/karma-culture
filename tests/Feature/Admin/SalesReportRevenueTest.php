<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Sales Report used to filter on payment_status = "paid" and nothing else,
 * which excluded the whole cash-on-delivery book: COD orders only turn "paid"
 * when they are delivered, so a shop taking cash saw a report of zero revenue
 * and zero orders while orders kept arriving.
 */
class SalesReportRevenueTest extends TestCase
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
            'name' => 'Sales Report Test',
            'slug' => 'sales-report-test',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Report Tee',
            'slug' => 'report-tee',
            'sku' => 'RPT-001',
            'category_id' => $category->id,
            'price' => 1000,
            'mrp' => 1500,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);
    }

    private function placeOrder(string $status, string $paymentStatus, string $method, float $total): Order
    {
        $order = Order::create([
            'order_number' => 'TEST-' . uniqid(),
            'user_id' => $this->adminUser->id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => $total,
            'source' => 'web',
            'metadata' => ['payment_method' => $method],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'price' => $total,
            'mrp' => $total,
            'quantity' => 1,
            'total' => $total,
        ]);

        return $order;
    }

    public function test_cod_orders_awaiting_collection_count_as_revenue(): void
    {
        $this->placeOrder('confirmed', 'pending', 'cod', 1200);

        $response = $this->actingAs($this->adminUser, 'admin')->get('/admin/reports/sales');

        $response->assertStatus(200);
        $stats = $response->viewData('stats');

        $this->assertEquals(1200, $stats['total_revenue']);
        $this->assertEquals(1, $stats['total_orders']);
        $this->assertEquals(1, $stats['items_sold']);
        $this->assertEquals(1200, $stats['awaiting_collection']);
    }

    public function test_cancelled_and_abandoned_orders_are_left_out(): void
    {
        $this->placeOrder('delivered', 'paid', 'cod', 1000);      // collected
        $this->placeOrder('shipped', 'pending', 'cod', 500);      // cash in flight
        $this->placeOrder('confirmed', 'paid', 'online', 700);    // prepaid, settled
        $this->placeOrder('cancelled', 'pending', 'cod', 9000);   // never happened
        $this->placeOrder('returned', 'paid', 'cod', 8000);       // undone
        $this->placeOrder('pending', 'pending', 'online', 7000);  // walked away at the gateway

        $response = $this->actingAs($this->adminUser, 'admin')->get('/admin/reports/sales');

        $stats = $response->viewData('stats');

        $this->assertEquals(2200, $stats['total_revenue']);
        $this->assertEquals(3, $stats['total_orders']);
        $this->assertEquals(500, $stats['awaiting_collection']);
    }

    public function test_sold_products_and_categories_follow_the_same_rule(): void
    {
        $this->placeOrder('confirmed', 'pending', 'cod', 1000);

        $response = $this->actingAs($this->adminUser, 'admin')->get('/admin/reports/sales');

        $this->assertEquals(1, $response->viewData('topProducts')->firstWhere('id', $this->product->id)?->sold);
        $this->assertEquals(1000, $response->viewData('salesByCategory')->first()?->revenue);
    }

    public function test_daily_series_covers_every_day_in_the_window(): void
    {
        $this->placeOrder('confirmed', 'pending', 'cod', 1000);

        $response = $this->actingAs($this->adminUser, 'admin')->get('/admin/reports/sales?period=7');

        $salesData = $response->viewData('salesData');

        $this->assertCount(7, $salesData, 'A quiet day still needs a slot on the chart.');
        $this->assertEquals(1000, $salesData->last()['revenue']);
        $this->assertEquals(0, $salesData->first()['revenue']);
    }
}
