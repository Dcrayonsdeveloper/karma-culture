<?php

namespace Tests\Feature\Fraud;

use App\Models\Admin;
use App\Models\Category;
use App\Models\FraudLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private FraudLog $fraudLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Fraud',
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
            'name' => 'Fraud Test Cat',
            'slug' => 'fraud-test-cat',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Fraud Test Product',
            'slug' => 'fraud-test-product',
            'sku' => 'FTP-001',
            'price' => 50000,
            'mrp' => 60000,
            'cost_price' => 20000,
            'stock_quantity' => 10,
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
            'address_line_1' => '123 Fraud Test St',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'pending',
            'payment_status' => 'paid',
            'subtotal' => 50000,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 50000,
            'paid_amount' => 50000,
            'source' => 'web',
            'ip_address' => '192.168.1.100',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Fraud Test Product',
            'sku' => 'FTP-001',
            'price' => 50000,
            'mrp' => 60000,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 50000,
        ]);

        $this->fraudLog = FraudLog::create([
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'type' => 'unusual_activity',
            'risk_score' => 75.50,
            'indicators' => ['velocity' => ['score' => 15, 'max' => 20, 'detail' => '4 orders in last hour']],
            'action' => 'flagged',
        ]);
    }

    public function test_admin_fraud_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/fraud');

        $response->assertStatus(200);
    }

    public function test_admin_fraud_detail_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/fraud/' . $this->fraudLog->id);

        $response->assertStatus(200);
    }

    public function test_admin_can_review_fraud_log(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->put('/admin/fraud/' . $this->fraudLog->id . '/review', [
                'action' => 'allowed',
                'notes' => 'Reviewed and cleared by admin.',
            ]);

        $response->assertRedirect();

        $this->fraudLog->refresh();
        $this->assertNotNull($this->fraudLog->reviewed_by);
    }

    public function test_fraud_index_requires_admin_auth(): void
    {
        $response = $this->get('/admin/fraud');

        $response->assertRedirect('/admin/login');
    }

    public function test_fraud_log_has_correct_risk_score(): void
    {
        $this->assertEquals(75.50, (float) $this->fraudLog->risk_score);
        $this->assertEquals('flagged', $this->fraudLog->action);
    }
}
