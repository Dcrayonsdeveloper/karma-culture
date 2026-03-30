<?php

namespace Tests\Feature\Order;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Cap',
            'slug' => 'kids-cap',
            'sku' => 'KC-001',
            'price' => 299,
            'mrp' => 399,
            'cost_price' => 100,
            'stock_quantity' => 50,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->address = UserAddress::create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '9876543210',
            'address_line_1' => '456 Test Lane',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'postal_code' => '560001',
            'country' => 'IN',
            'is_default' => true,
        ]);
    }

    private function createOrder(string $status = 'pending'): Order
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $this->address->id,
            'billing_address_id' => $this->address->id,
            'status' => $status,
            'payment_status' => 'paid',
            'subtotal' => 299,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 299,
            'paid_amount' => 299,
            'source' => 'web',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Kids Cap',
            'sku' => 'KC-001',
            'price' => 299,
            'mrp' => 399,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 299,
        ]);

        return $order;
    }

    public function test_pending_order_can_be_cancelled(): void
    {
        $order = $this->createOrder('confirmed');

        $response = $this->actingAs($this->user)
            ->post('/account/orders/' . $order->id . '/cancel');

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    public function test_confirmed_order_can_be_cancelled(): void
    {
        $order = $this->createOrder('confirmed');

        $response = $this->actingAs($this->user)
            ->post('/account/orders/' . $order->id . '/cancel');

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    public function test_shipped_order_cannot_be_cancelled(): void
    {
        $order = $this->createOrder('shipped');

        $response = $this->actingAs($this->user)
            ->post('/account/orders/' . $order->id . '/cancel');

        $order->refresh();
        $this->assertNotEquals('cancelled', $order->status);
    }

    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $order = $this->createOrder('delivered');

        $response = $this->actingAs($this->user)
            ->post('/account/orders/' . $order->id . '/cancel');

        $order->refresh();
        $this->assertNotEquals('cancelled', $order->status);
    }

    public function test_other_user_cannot_cancel_order(): void
    {
        $order = $this->createOrder('pending');
        $otherUser = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($otherUser)
            ->post('/account/orders/' . $order->id . '/cancel');

        $response->assertStatus(403);

        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }
}
