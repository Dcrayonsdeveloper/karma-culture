<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Girls Wear',
            'slug' => 'girls-wear',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Frock',
            'slug' => 'kids-frock',
            'sku' => 'KF-001',
            'price' => 799,
            'mrp' => 999,
            'cost_price' => 300,
            'stock_quantity' => 25,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_product_index_page_loads(): void
    {
        $response = $this->get('/products');

        $response->assertStatus(200);
    }

    public function test_product_detail_page_loads(): void
    {
        $response = $this->get('/products/' . $this->product->slug);

        $response->assertStatus(200);
    }

    public function test_category_page_shows_products(): void
    {
        $response = $this->get('/category/' . $this->category->slug);

        $response->assertStatus(200);
    }

    public function test_search_returns_results(): void
    {
        $response = $this->get('/search?q=Kids+Frock');

        $response->assertStatus(200);
    }

    public function test_nonexistent_product_returns_404(): void
    {
        $response = $this->get('/products/nonexistent-product-slug');

        $response->assertStatus(404);
    }
}
