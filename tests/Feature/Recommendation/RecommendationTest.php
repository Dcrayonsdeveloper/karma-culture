<?php

namespace Tests\Feature\Recommendation;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Recommendation Test',
            'slug' => 'recommendation-test',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Recommended Product',
            'slug' => 'recommended-product',
            'sku' => 'RP-001',
            'price' => 699,
            'mrp' => 899,
            'cost_price' => 300,
            'stock_quantity' => 50,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
            'sales_count' => 100,
        ]);

        // Create additional products for recommendation variety
        for ($i = 2; $i <= 5; $i++) {
            Product::create([
                'name' => "Recommended Product {$i}",
                'slug' => "recommended-product-{$i}",
                'sku' => "RP-00{$i}",
                'price' => 699 + ($i * 100),
                'mrp' => 899 + ($i * 100),
                'cost_price' => 300,
                'stock_quantity' => 50,
                'category_id' => $category->id,
                'status' => 'approved',
                'is_active' => true,
                'sales_count' => 100 - ($i * 10),
            ]);
        }
    }

    public function test_recently_viewed_endpoint(): void
    {
        $response = $this->get('/recommendations/recently-viewed');

        $response->assertStatus(200);
    }

    public function test_similar_products_endpoint(): void
    {
        $response = $this->get('/recommendations/similar/' . $this->product->id);

        $response->assertStatus(200);
    }

    public function test_frequently_bought_together_endpoint(): void
    {
        $response = $this->get('/recommendations/bought-together/' . $this->product->id);

        $response->assertStatus(200);
    }

    public function test_personalized_recommendations_endpoint(): void
    {
        $response = $this->get('/recommendations/personalized');

        $response->assertStatus(200);
    }

    public function test_api_popular_recommendations(): void
    {
        $response = $this->getJson('/api/v1/recommendations/popular');

        $response->assertStatus(200);
    }

    public function test_api_similar_recommendations(): void
    {
        $response = $this->getJson('/api/v1/recommendations/similar/' . $this->product->id);

        $response->assertStatus(200);
    }
}
