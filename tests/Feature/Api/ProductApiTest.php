<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Toys',
            'slug' => 'toys',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Building Blocks',
            'slug' => 'building-blocks',
            'sku' => 'BB-001',
            'price' => 999,
            'mrp' => 1499,
            'cost_price' => 400,
            'stock_quantity' => 40,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
            'is_featured' => true,
        ]);
    }

    public function test_product_index(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_product_show(): void
    {
        $response = $this->getJson('/api/v1/products/' . $this->product->slug);

        $response->assertStatus(200);
    }

    public function test_featured_products(): void
    {
        $response = $this->getJson('/api/v1/products/featured');

        $response->assertStatus(200);
    }

    public function test_bestseller_products(): void
    {
        $response = $this->getJson('/api/v1/products/bestsellers');

        $response->assertStatus(200);
    }

    public function test_product_search(): void
    {
        $response = $this->getJson('/api/v1/search?q=Building+Blocks');

        $response->assertStatus(200);
    }

    public function test_nonexistent_product_returns_404(): void
    {
        $response = $this->getJson('/api/v1/products/nonexistent-slug');

        $response->assertStatus(404);
    }
}
