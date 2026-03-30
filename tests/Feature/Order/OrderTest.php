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

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Baby Wear',
            'slug' => 'baby-wear',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Baby Romper',
            'slug' => 'baby-romper',
            'sku' => 'BR-001',
            'price' => 599,
            'mrp' => 799,
            'cost_price' => 200,
            'stock_quantity' => 30,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $address = UserAddress::create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '9876543210',
            'address_line_1' => '123 Test Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 1198,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 1198,
            'paid_amount' => 1198,
            'source' => 'web',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Baby Romper',
            'sku' => 'BR-001',
            'price' => 599,
            'mrp' => 799,
            'quantity' => 2,
            'tax' => 0,
            'discount' => 0,
            'total' => 1198,
        ]);
    }

    public function test_order_listing_requires_authentication(): void
    {
        $response = $this->get('/account/orders');

        $response->assertRedirect('/login');
    }

    public function test_order_listing_shows_user_orders(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/account/orders');

        $response->assertStatus(200);
    }

    public function test_order_detail_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/account/orders/' . $this->order->id);

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($otherUser)
            ->get('/account/orders/' . $this->order->id);

        $response->assertStatus(403);
    }

    public function test_order_has_correct_status(): void
    {
        $this->assertEquals('confirmed', $this->order->status);
        $this->assertTrue($this->order->isConfirmed());
        $this->assertFalse($this->order->isCancelled());
    }
}
