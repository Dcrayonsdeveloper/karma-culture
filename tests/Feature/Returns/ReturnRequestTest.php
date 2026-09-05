<?php

namespace Tests\Feature\Returns;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;
    private OrderItem $orderItem;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Return Test Cat',
            'slug' => 'return-test-cat',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Return Test Product',
            'slug' => 'return-test-product',
            'sku' => 'RTP-001',
            'price' => 899,
            'mrp' => 1099,
            'cost_price' => 350,
            'stock_quantity' => 20,
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
            'address_line_1' => '123 Return St',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 899,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 899,
            'paid_amount' => 899,
            'source' => 'web',
            'delivered_at' => now()->subDays(2),
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Return Test Product',
            'sku' => 'RTP-001',
            'price' => 899,
            'mrp' => 1099,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 899,
        ]);
    }

    public function test_return_request_page_requires_authentication(): void
    {
        $response = $this->get('/account/returns/create');

        $response->assertRedirect('/login');
    }

    public function test_return_request_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/account/returns/create');

        $response->assertStatus(200);
    }

    public function test_return_request_can_be_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/account/returns', [
                'order_id' => $this->order->id,
                'type' => 'return',
                // One of ReturnController::REASONS. The reason stopped being a
                // free string when the form became a select, so the value this
                // used to post failed validation and no return was ever created
                // - the test redirected back to the form and called it a pass.
                'reason' => 'Defective or damaged product',
                'description' => 'The product arrived with a tear on the side.',
                'items' => [
                    [
                        'order_item_id' => $this->orderItem->id,
                        'quantity' => 1,
                        'reason' => 'Defective',
                        'condition' => 'opened',
                    ],
                ],
            ]);

        // Assert no validation errors before the redirect: a rejected form also
        // redirects, which is how this failure hid.
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('returns', [
            'order_id' => $this->order->id,
            'user_id' => $this->user->id,
            'type' => 'return',
        ]);
    }

    public function test_return_listing_shows_user_returns(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/account/returns');

        $response->assertStatus(200);
    }

    public function test_other_user_cannot_create_return_for_foreign_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($otherUser)
            ->post('/account/returns', [
                'order_id' => $this->order->id,
                'type' => 'return',
                'reason' => 'Product defective',
                'items' => [
                    [
                        'order_item_id' => $this->orderItem->id,
                        'quantity' => 1,
                        'reason' => 'Defective',
                        'condition' => 'opened',
                    ],
                ],
            ]);

        // The controller scopes the lookup to the signed-in user and
        // firstOrFail()s, so a foreign order is a 404 — it must not leak
        // that the order exists. 403/302/422 are also acceptable outcomes.
        $this->assertContains($response->status(), [404, 403, 302, 422]);
        $this->assertDatabaseMissing('returns', [
            'order_id' => $this->order->id,
            'user_id' => $otherUser->id,
        ]);
    }
}
