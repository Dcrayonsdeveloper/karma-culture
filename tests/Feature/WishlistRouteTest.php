<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The wishlist's data endpoint used to sit at /wishlist-items, outside the
 * /wishlist prefix its own page and its own write actions already used. It has
 * moved under the prefix.
 *
 * The old path still answers, and deliberately is not a redirect: Laravel's
 * redirect routes forward path parameters only, so a 301 would drop the ?ids=
 * list and tell the shopper their wishlist was empty. A tab opened before the
 * deploy is still running the previous JS bundle and still asks for the old
 * path, so it has to keep returning the same data.
 */
class WishlistRouteTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $category = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        return Product::create([
            'name' => 'Poplin Shirt',
            'slug' => 'poplin-shirt',
            'sku' => 'POPLIN',
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_the_data_endpoint_lives_under_the_wishlist_prefix(): void
    {
        $product = $this->makeProduct();

        $this->getJson('/wishlist/items?ids='.$product->id)
            ->assertOk()
            ->assertJsonPath('items.0.id', $product->id);
    }

    public function test_a_guest_can_read_the_wishlist_data(): void
    {
        $product = $this->makeProduct();

        // Reading is open; only writing takes an account.
        $this->getJson('/wishlist/items?ids='.$product->id)->assertOk();
    }

    public function test_the_old_path_still_answers_with_the_ids_intact(): void
    {
        $product = $this->makeProduct();

        // The point of the alias. A redirect here would answer 301 and lose the
        // ids, which is why this is a second route to the same action.
        $this->getJson('/wishlist-items?ids='.$product->id)
            ->assertOk()
            ->assertJsonPath('items.0.id', $product->id);
    }

    public function test_no_ids_yields_an_empty_list_rather_than_an_error(): void
    {
        $this->getJson('/wishlist/items')
            ->assertOk()
            ->assertExactJson(['items' => []]);
    }

    public function test_a_literal_segment_is_not_swallowed_by_the_product_wildcard(): void
    {
        // /wishlist/items only works because {product} is constrained to a
        // number. Without that the POST/DELETE wildcard could claim it, which is
        // the bug that once broke /cart/remove-coupon.
        $this->getJson('/wishlist/items')->assertOk();
    }
}
