<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $category = Category::create([
            'name' => 'Kids Toys',
            'slug' => 'kids-toys',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Puzzle Set',
            'slug' => 'puzzle-set',
            'sku' => 'PS-001',
            'price' => 499,
            'mrp' => 699,
            'cost_price' => 200,
            'stock_quantity' => 35,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_get_cart(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/cart');

        $response->assertStatus(200);
    }

    public function test_add_item_to_cart(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(201);
    }

    public function test_add_item_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_clear_cart(): void
    {
        // Add an item first
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/v1/cart');

        $response->assertStatus(200);
    }
}
