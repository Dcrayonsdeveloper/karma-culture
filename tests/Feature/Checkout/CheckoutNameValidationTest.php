<?php

namespace Tests\Feature\Checkout;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The server end of the checkout Full Name fix. The `pattern` added to the box
 * is a courtesy a direct POST skips entirely, so these prove V::name() is
 * actually wired into /checkout/process rather than only into the markup.
 *
 * Tests\Feature\NameFieldCharsetTest covers the browser end, for every box that
 * carries this charset, without a database.
 */
class CheckoutNameValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Kids Shoes',
            'slug' => 'kids-shoes-names',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Sneakers',
            'slug' => 'kids-sneakers-names',
            'sku' => 'KS-NAME-001',
            'price' => 1499,
            'mrp' => 1999,
            'cost_price' => 600,
            'stock_quantity' => 15,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function placeOrderWithName(string $fullName): TestResponse
    {
        $this->actingAs($this->user)->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        return $this->actingAs($this->user)->post('/checkout/process', [
            'full_name' => $fullName,
            'email' => 'dev01@gmail.com',
            'phone' => '9876543210',
            'address_line_1' => '12 Residency Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560025',
            'payment_method' => 'cod',
        ]);
    }

    public function test_the_server_rejects_a_name_of_symbols(): void
    {
        $response = $this->placeOrderWithName('chirag raw arakn@#@!#q13123123');

        $response->assertSessionHasErrors('full_name');
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * The other half of that: a name carrying the separators still gets through,
     * so the rejection above is the charset and not a blanket refusal.
     */
    public function test_the_server_accepts_a_name_with_separators(): void
    {
        $response = $this->placeOrderWithName("Mary-Anne O'Connor");

        $response->assertSessionDoesntHaveErrors('full_name');
    }
}
