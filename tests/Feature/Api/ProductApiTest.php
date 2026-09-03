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

    /**
     * The same wrong-way-round pair the storefront sidebar corrects: min 1500
     * with max 500 is `price >= 1500 AND price <= 500`, a range nothing can be
     * in, so the endpoint answered an empty page for what the caller plainly
     * meant as 500-1500.
     */
    public function test_a_backwards_price_range_is_put_back_in_order(): void
    {
        $response = $this->getJson('/api/v1/products?min_price=1500&max_price=500');

        $response->assertStatus(200);
        $this->assertSame(['Building Blocks'], array_column($response->json('data'), 'name'));
    }

    /**
     * A bound that is not a number is not a bound. `?max_price=` compared price
     * against the empty string and `?min_price[]=1` handed an array to the query
     * builder, which 500'd the endpoint.
     */
    public function test_a_price_bound_that_is_not_a_number_is_ignored(): void
    {
        $this->getJson('/api/v1/products?min_price[]=1&max_price=')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
