<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\FraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private FraudDetectionService $service;
    private User $user;
    private UserAddress $address;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FraudDetectionService();

        $this->user = User::factory()->create(['role' => 'customer']);

        $this->category = Category::create([
            'name' => 'Fraud Service Test',
            'slug' => 'fraud-service-test',
            'is_active' => true,
        ]);

        $this->address = UserAddress::create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '9876543210',
            'address_line_1' => '123 Fraud St',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
            'is_default' => true,
        ]);
    }

    private function createOrder(float $total = 1000, ?string $ipAddress = null): Order
    {
        return Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $this->address->id,
            'billing_address_id' => $this->address->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'source' => 'web',
            'ip_address' => $ipAddress,
            'shipping_address_snapshot' => [
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
            ],
            'billing_address_snapshot' => [
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
            ],
        ]);
    }

    public function test_low_risk_order_is_allowed(): void
    {
        $order = $this->createOrder(500);

        $result = $this->service->assessOrder($order);

        $this->assertEquals('allowed', $result['action']);
        $this->assertLessThan(70, $result['score']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('fraud_log_id', $result);
    }

    public function test_velocity_check_flags_multiple_orders(): void
    {
        // Create several orders in the last hour
        for ($i = 0; $i < 5; $i++) {
            Order::create([
                'user_id' => $this->user->id,
                'shipping_address_id' => $this->address->id,
                'billing_address_id' => $this->address->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => 500,
                'discount' => 0,
                'tax' => 0,
                'shipping_cost' => 0,
                'total' => 500,
                'paid_amount' => 0,
                'source' => 'web',
            ]);
        }

        $order = $this->createOrder(500);
        $result = $this->service->assessOrder($order);

        $this->assertGreaterThan(0, $result['checks']['velocity']['score']);
    }

    public function test_high_value_order_increases_score(): void
    {
        // Create a normal baseline order
        Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $this->address->id,
            'billing_address_id' => $this->address->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 500,
            'paid_amount' => 500,
            'source' => 'web',
        ]);

        // Create a very high value order (10x average)
        $order = $this->createOrder(50000);
        $result = $this->service->assessOrder($order);

        $this->assertGreaterThan(0, $result['checks']['order_value']['score']);
    }

    public function test_new_account_check_for_recent_user(): void
    {
        // User was just created (within last hour)
        $order = $this->createOrder(10000);
        $result = $this->service->assessOrder($order);

        $this->assertArrayHasKey('new_account', $result['checks']);
    }

    public function test_address_mismatch_check(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $this->address->id,
            'billing_address_id' => $this->address->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'source' => 'web',
            'shipping_address_snapshot' => [
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
            ],
            'billing_address_snapshot' => [
                'city' => 'Delhi',
                'state' => 'Delhi',
            ],
        ]);

        $result = $this->service->assessOrder($order);

        $this->assertGreaterThan(0, $result['checks']['address_mismatch']['score']);
    }

    public function test_fraud_log_is_created(): void
    {
        $order = $this->createOrder(1000);
        $result = $this->service->assessOrder($order);

        $this->assertDatabaseHas('fraud_logs', [
            'id' => $result['fraud_log_id'],
            'order_id' => $order->id,
            'user_id' => $this->user->id,
        ]);
    }
}
