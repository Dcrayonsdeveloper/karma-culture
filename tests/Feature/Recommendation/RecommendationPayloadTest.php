<?php

namespace Tests\Feature\Recommendation;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the recommendation rails are handed.
 *
 * All four endpoints carried their own hand-written copy of the same product
 * array, and the copies had drifted apart.
 *
 * Every one of them read $p->images->first()?->image_path for the picture.
 * product_images has no image_path column - the column is called url - so that
 * expression was null for every product in the catalogue, and every card in
 * every rail on the site fell back to the "no image" placeholder.
 *
 * None of them sent a url, so three Blade components built one by hand as
 * '/products/' + product.slug - the plural path that now redirects.
 *
 * And bought-together left out the rating its three siblings sent.
 */
class RecommendationPayloadTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Payload',
            'slug' => 'payload',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Payload Product',
            'slug' => 'payload-product',
            'sku' => 'PP-1',
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
            'sales_count' => 50,
        ]);

        // Stored the way the admin controller writes an upload.
        ProductImage::create([
            'product_id' => $this->product->id,
            'url' => '/storage/products/payload.jpg',
            'is_primary' => true,
            'position' => 0,
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    private function cards(string $url): array
    {
        return $this->getJson($url)->assertOk()->json('data');
    }

    public function test_every_rail_sends_a_usable_image_url_not_a_null(): void
    {
        foreach ($this->rails() as $label => $url) {
            foreach ($this->cards($url) as $card) {
                $this->assertNotNull($card['image'], "{$label} sent a null image");
                $this->assertStringContainsString(
                    'products/payload.jpg',
                    $card['image'],
                    "{$label} did not resolve the image to a servable URL"
                );
            }
        }
    }

    public function test_every_rail_sends_the_product_url_so_the_front_end_need_not_build_one(): void
    {
        foreach ($this->rails() as $label => $url) {
            foreach ($this->cards($url) as $card) {
                $this->assertArrayHasKey('url', $card, "{$label} sent no url");

                // The canonical singular path, not the plural one that redirects.
                $this->assertStringContainsString('/product/', $card['url'], "{$label} url is not canonical");
                $this->assertStringNotContainsString('/products/', $card['url'], "{$label} url uses the redirecting path");
            }
        }
    }

    public function test_all_four_rails_answer_in_the_same_shape(): void
    {
        $expected = ['id', 'name', 'slug', 'url', 'price', 'mrp', 'image', 'rating'];

        foreach ($this->rails() as $label => $url) {
            foreach ($this->cards($url) as $card) {
                $this->assertSame(
                    $expected,
                    array_keys($card),
                    "{$label} does not match the shared card shape"
                );
            }
        }
    }

    public function test_the_envelope_is_the_one_the_components_read(): void
    {
        foreach ($this->rails() as $label => $url) {
            $body = $this->getJson($url)->assertOk()->json();

            // The components read `data.data || []`.
            $this->assertTrue($body['success'], "{$label} did not report success");
            $this->assertIsArray($body['data'], "{$label} did not send a data array");
        }
    }

    /** @return array<string,string> */
    private function rails(): array
    {
        // recently-viewed and personalized answer for a guest too, so no login
        // is needed to reach any of the four.
        return [
            'similar' => '/recommendations/similar/'.$this->product->id,
            'bought-together' => '/recommendations/bought-together/'.$this->product->id,
            'recently-viewed' => '/recommendations/recently-viewed',
            'personalized' => '/recommendations/personalized',
        ];
    }

    public function test_a_signed_in_shopper_gets_the_same_shape(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));

        foreach ($this->rails() as $label => $url) {
            $this->getJson($url)->assertOk()->assertJsonStructure(['success', 'data']);
        }
    }
}
