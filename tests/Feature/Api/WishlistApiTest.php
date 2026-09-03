<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/v1/wishlist eager-loaded 'product:id,name,slug,price,mrp,images'.
 *
 * Everything after the colon is a column list, and images is a relation, not a
 * column - so the query asked the products table for an "images" column and the
 * endpoint answered 500. It only looked healthy because an empty wishlist skips
 * the eager load entirely: it broke on the first row a customer saved, which is
 * the only state the endpoint exists to serve.
 */
class WishlistApiTest extends TestCase
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
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $this->product->id,
            'url' => '/storage/products/blocks.jpg',
            'is_primary' => true,
            'position' => 0,
        ]);
    }

    private function get_(string $url): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['Authorization' => 'Bearer '.$this->token])->getJson($url);
    }

    public function test_a_wishlist_with_something_in_it_can_actually_be_read(): void
    {
        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

        $this->get_('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonPath('data.0.product.id', $this->product->id)
            ->assertJsonPath('data.0.product.name', 'Building Blocks');
    }

    public function test_the_product_carries_its_images(): void
    {
        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

        $body = $this->get_('/api/v1/wishlist')->assertOk()->json();

        $this->assertNotEmpty($body['data'][0]['product']['images']);
    }

    public function test_an_empty_wishlist_still_answers(): void
    {
        $this->get_('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_the_reviews_list_survives_the_same_eager_load(): void
    {
        \App\Models\Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'content' => 'Good.',
        ]);

        $this->get_('/api/v1/reviews')
            ->assertOk()
            ->assertJsonPath('data.0.product.id', $this->product->id);
    }
}
