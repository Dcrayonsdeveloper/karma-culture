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

    /**
     * Checkout is deliberately guest-friendly — it no longer requires login.
     * What it does require is a non-empty cart, which sends you back to /cart.
     */
    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect('/cart');
    }

    /**
     * Checkout must not be auth-gated. A guest hitting it with an empty cart
     * goes to /cart, never to /login.
     *
     * (Cart-to-checkout continuity for a guest can't be asserted here: the
     * test client doesn't carry the session cookie between calls, so each
     * request gets a fresh session id and a fresh guest cart.)
     */
    public function test_checkout_is_not_auth_gated_for_guests(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect('/cart');
        $response->assertRedirectContains('/cart');
        $this->assertStringNotContainsString('/login', $response->headers->get('Location'));
    }

    public function test_guest_can_add_to_cart(): void
    {
        $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', ['user_id' => null]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
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

        // The controller validates address_id (scoped to the signed-in user)
        // plus a contact email — not the old shipping_address_id field.
        $response = $this->actingAs($this->user)
            ->post('/checkout/process', [
                'address_id' => $this->address->id,
                'email' => $this->user->email,
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
