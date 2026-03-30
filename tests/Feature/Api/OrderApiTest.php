<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Order $order;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $category = Category::create([
            'name' => 'Books',
            'slug' => 'books',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Story Book',
            'slug' => 'kids-story-book',
            'sku' => 'KSB-001',
            'price' => 350,
            'mrp' => 450,
            'cost_price' => 150,
            'stock_quantity' => 100,
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
            'address_line_1' => '789 API Street',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'postal_code' => '600001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'pending',
            'payment_status' => 'paid',
            'subtotal' => 700,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 700,
            'paid_amount' => 700,
            'source' => 'api',
        ]);
    }

    public function test_order_listing(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_order_listing_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(401);
    }

    public function test_order_show(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/orders/' . $this->order->id);

        $response->assertStatus(200);
    }

    public function test_order_cancel(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/orders/' . $this->order->id . '/cancel');

        $response->assertStatus(200);

        $this->order->refresh();
        $this->assertEquals('cancelled', $this->order->status);
    }

    public function test_other_user_cannot_view_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);
        $otherToken = $otherUser->createToken('other-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken,
        ])->getJson('/api/v1/orders/' . $this->order->id);

        $response->assertStatus(403);
    }
}
