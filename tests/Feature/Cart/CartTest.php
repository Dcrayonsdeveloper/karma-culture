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

    public function test_add_product_to_cart_as_guest(): void
    {
        $response = $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect();
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
