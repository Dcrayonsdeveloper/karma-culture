<?php

namespace Tests\Feature\Cart;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Kids Clothing',
            'slug' => 'kids-clothing',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Jeans',
            'slug' => 'kids-jeans',
            'sku' => 'KJ-001',
            'price' => 899,
            'mrp' => 1199,
            'cost_price' => 400,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_cart_page_loads(): void
    {
        $response = $this->get('/cart');

        $response->assertStatus(200);
    }

    public function test_add_product_to_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response->assertRedirect();
    }

    /**
     * A guest used to get a cart of their own, merged into the account on
     * sign-in. The store owner asked for an account up front instead: a guest
     * basket is one nobody can be contacted about, and checkout has required an
     * account throughout, so the guest half of the journey only ever ended at
     * this same login page - later, with more typed in and more to lose.
     */
    public function test_a_guest_is_sent_to_the_login_page_instead_of_getting_a_cart(): void
    {
        $response = $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('cart_items', 0);
    }

    /**
     * The button redirects before it ever asks, so a request that does arrive
     * from a guest is a stale tab or a script. Those get a 401 rather than a
     * redirect to HTML, which is what the front end turns back into a trip to
     * the login page.
     */
    public function test_a_guests_background_request_is_refused_with_a_401(): void
    {
        $this->postJson('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ])->assertStatus(401);
    }

    /** Reading stays open: a guest's cart answers, and answers empty. */
    public function test_a_guest_can_still_read_an_empty_cart(): void
    {
        $this->get('/cart')->assertStatus(200);
        $this->getJson('/cart/data')->assertStatus(200)->assertJsonPath('cart_count', 0);
    }

    public function test_get_cart_data(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/cart/data');

        $response->assertStatus(200);
    }

    public function test_clear_cart(): void
    {
        // Add item first
        $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($this->user)
            ->delete('/cart');

        $response->assertRedirect();
    }
}
