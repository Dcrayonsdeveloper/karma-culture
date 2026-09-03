<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/v1/reviews validated a field called `comment`.
 *
 * There is no comment column on reviews - the body of a review is `content` -
 * and comment is not in Review::$fillable either, so create() dropped it
 * without a word. Every review posted through this endpoint was stored with an
 * empty body, and the endpoint still answered 201.
 *
 * update() already carried the fix and a comment explaining it; store() had
 * been left behind, so the two halves of the same resource disagreed about what
 * the field was called.
 */
class ReviewApiTest extends TestCase
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
    }

    /** @param array<string,mixed> $payload */
    private function submitReview(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->postJson('/api/v1/reviews', $payload);
    }

    public function test_the_text_of_a_review_is_actually_stored(): void
    {
        $this->submitReview([
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'Great quality',
            'content' => 'My kids love these blocks.',
        ])->assertCreated();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'content' => 'My kids love these blocks.',
        ]);
    }

    public function test_the_created_review_comes_back_with_its_text(): void
    {
        $this->submitReview([
            'product_id' => $this->product->id,
            'rating' => 4,
            'content' => 'Sturdy and safe.',
        ])->assertCreated()
          ->assertJsonPath('data.content', 'Sturdy and safe.');
    }

    public function test_a_review_arrives_unapproved(): void
    {
        $this->submitReview([
            'product_id' => $this->product->id,
            'rating' => 5,
            'content' => 'Lovely.',
        ])->assertCreated();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'is_approved' => false,
            'status' => 'pending',
        ]);
    }

    public function test_the_body_is_optional(): void
    {
        $this->submitReview([
            'product_id' => $this->product->id,
            'rating' => 5,
        ])->assertCreated();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'content' => null,
        ]);
    }

    public function test_a_second_review_of_the_same_product_is_refused(): void
    {
        $payload = [
            'product_id' => $this->product->id,
            'rating' => 5,
            'content' => 'First.',
        ];

        $this->submitReview($payload)->assertCreated();
        $this->submitReview($payload)->assertStatus(409);
    }

    public function test_store_and_update_agree_on_the_field_name(): void
    {
        $this->submitReview([
            'product_id' => $this->product->id,
            'rating' => 5,
            'content' => 'Before.',
        ])->assertCreated();

        $review = Review::where('user_id', $this->user->id)->firstOrFail();

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->putJson('/api/v1/reviews/'.$review->id, [
                'rating' => 4,
                'content' => 'After.',
            ])->assertOk();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'content' => 'After.',
        ]);
    }
}
