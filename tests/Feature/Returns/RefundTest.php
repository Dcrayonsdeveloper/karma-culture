<?php

namespace Tests\Feature\Returns;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private OrderReturn $return;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Refund',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Refund Test Cat',
            'slug' => 'refund-test-cat',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Refund Test Product',
            'slug' => 'refund-test-product',
            'sku' => 'RFTP-001',
            'price' => 1299,
            'mrp' => 1599,
            'cost_price' => 500,
            'stock_quantity' => 15,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $address = UserAddress::create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'first_name' => 'Refund',
            'last_name' => 'Customer',
            'phone' => '9876543210',
            'address_line_1' => '456 Refund Ave',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'postal_code' => '500001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $customer->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 1299,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 1299,
            'paid_amount' => 1299,
            'source' => 'web',
            'delivered_at' => now()->subDays(3),
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'product_name' => 'Refund Test Product',
            'sku' => 'RFTP-001',
            'price' => 1299,
            'mrp' => 1599,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 1299,
        ]);

        $this->return = OrderReturn::create([
            'order_id' => $this->order->id,
            'user_id' => $customer->id,
            'type' => 'return',
            'status' => 'approved',
            'reason' => 'Wrong size',
            'refund_amount' => 1299,
            'refund_method' => 'original',
            'approved_at' => now(),
        ]);
    }

    public function test_admin_returns_listing_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/returns');

        $response->assertStatus(200);
    }

    public function test_admin_return_detail_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/returns/' . $this->return->id);

        $response->assertStatus(200);
    }

    public function test_admin_can_process_refund(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->post('/admin/returns/' . $this->return->id . '/refund', [
                'amount' => 1299,
                'method' => 'original',
            ]);

        $response->assertRedirect();
    }

    public function test_admin_can_update_return_status(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->put('/admin/returns/' . $this->return->id . '/status', [
                'status' => 'completed',
            ]);

        $response->assertRedirect();

        $this->return->refresh();
        $this->assertEquals('completed', $this->return->status);
    }

    public function test_refund_requires_admin_authentication(): void
    {
        $response = $this->post('/admin/returns/' . $this->return->id . '/refund', [
            'amount' => 1299,
        ]);

        $response->assertRedirect('/admin/login');
    }
}
