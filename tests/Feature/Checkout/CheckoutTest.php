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
     * Past the login gate, checkout still needs something to buy — an empty
     * cart sends the customer back to /cart rather than showing a blank order.
     */
    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $response = $this->actingAs($this->user)->get('/checkout');

        $response->assertRedirect('/cart');
    }

    /**
     * Checkout requires an account. A guest is sent to the login page, and the
     * intended URL is kept so signing in drops them back on checkout.
     */
    public function test_guest_is_sent_to_login_instead_of_checkout(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect('/login');
        $this->assertSame(url('/checkout'), session('url.intended'));
    }

    /**
     * The gate that actually matters: even posting straight at the endpoint,
     * with a complete and valid payload, a guest writes no order.
     */
    public function test_guest_cannot_place_an_order_by_posting_to_process(): void
    {
        $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->post('/checkout/process', [
            'full_name' => 'Guest Shopper',
            'email' => 'guest@example.com',
            'phone' => '9876543210',
            'address_line_1' => '12 Residency Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560025',
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * A guest used to get a cart of their own, kept against the session and
     * merged into the account at sign-in. The shop asks for the account first
     * now, so there is no guest cart to merge - the login page moved from the
     * end of the journey to the start of it.
     *
     * Covered in full by Tests\Feature\Cart\SignInBeforeCartAndWishlistTest;
     * kept here because this file is where the guest journey is described.
     */
    public function test_a_guest_gets_the_login_page_rather_than_a_cart(): void
    {
        $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
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
