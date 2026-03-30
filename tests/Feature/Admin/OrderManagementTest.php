<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'Manager',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Admin Test Cat',
            'slug' => 'admin-test-cat',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Admin Test Product',
            'slug' => 'admin-test-product',
            'sku' => 'ATP-001',
            'price' => 999,
            'mrp' => 1299,
            'cost_price' => 400,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $address = UserAddress::create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => '9876543210',
            'address_line_1' => '123 Admin Test St',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $customer->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'pending',
            'payment_status' => 'paid',
            'subtotal' => 999,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 999,
            'paid_amount' => 999,
            'source' => 'web',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'product_name' => 'Admin Test Product',
            'sku' => 'ATP-001',
            'price' => 999,
            'mrp' => 1299,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 999,
        ]);
    }

    public function test_admin_order_listing_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/orders');

        $response->assertStatus(200);
    }

    public function test_admin_order_detail_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/orders/' . $this->order->id);

        $response->assertStatus(200);
    }

    public function test_admin_can_update_order_status(): void
    {
        // Update order to 'confirmed' first so we can test a valid transition
        $this->order->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->put('/admin/orders/' . $this->order->id . '/status', [
                'status' => 'processing',
            ]);

        $this->order->refresh();
        $this->assertEquals('processing', $this->order->status);
    }

    public function test_admin_order_listing_requires_authentication(): void
    {
        $response = $this->get('/admin/orders');

        $response->assertRedirect('/admin/login');
    }
}
