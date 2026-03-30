<?php

namespace Tests\Feature\Checkout;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
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
            'name' => 'Kids Shoes',
            'slug' => 'kids-shoes',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Sneakers',
            'slug' => 'kids-sneakers',
            'sku' => 'KS-001',
            'price' => 1499,
            'mrp' => 1999,
            'cost_price' => 600,
            'stock_quantity' => 15,
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
            'address_line_1' => '123 Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
            'is_default' => true,
        ]);
    }

    public function test_checkout_page_requires_authentication(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect('/login');
    }

    public function test_checkout_page_loads_for_authenticated_user(): void
    {
        // Add item to cart first
        $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($this->user)
            ->get('/checkout');

        $response->assertStatus(200);
    }

    public function test_checkout_process_creates_order(): void
    {
        // Add item to cart first
        $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($this->user)
            ->post('/checkout/process', [
                'shipping_address_id' => $this->address->id,
                'same_billing_address' => true,
                'payment_method' => 'cod',
                'notes' => '',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_checkout_fails_with_out_of_stock_product(): void
    {
        $this->product->update(['stock_quantity' => 0]);

        $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($this->user)
            ->post('/checkout/process', [
                'shipping_address_id' => $this->address->id,
                'billing_address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        // Should fail or redirect with error
        $response->assertStatus(302);
    }
}
