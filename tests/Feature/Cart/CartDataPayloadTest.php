<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What GET /cart/data hands the drawer.
 *
 * Three things were wrong with the item rows. `image` was the one field in the
 * map with no guard against the product having been deleted out from under the
 * cart line, so a cart holding a since-deleted product 500'd the whole endpoint
 * - and the drawer's catch swallowed it, so the cart simply stopped updating
 * with no error anywhere the shopper could see.
 *
 * It also read primaryImage->first()?->url, a raw column, rather than the
 * primary_image_url accessor the rest of the app emits - so a product saved
 * with gallery images but no "Main Image" (the field is optional) sent null,
 * and nothing was fingerprinted.
 *
 * And the rows carried a slug but no url, so the drawer built '/product/' +
 * item.slug by hand while the recommendation rail three elements below it in
 * the same template used the url the server sent.
 */
class CartDataPayloadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        $this->product = Product::create([
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

    private function cartWith(Product $product): Cart
    {
        $cart = Cart::create(['user_id' => $this->user->id]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'total' => $product->price,
        ]);

        $cart->recalculate();

        return $cart;
    }

    public function test_a_gallery_image_is_used_when_no_main_image_was_set(): void
    {
        // is_primary false - the "Main Image" field is optional in the admin.
        ProductImage::create([
            'product_id' => $this->product->id,
            'url' => '/storage/products/poplin.jpg',
            'is_primary' => false,
            'position' => 0,
        ]);

        $this->cartWith($this->product);

        $image = $this->actingAs($this->user)
            ->getJson('/cart/data')->assertOk()->json('items.0.image');

        $this->assertNotNull($image, 'the drawer was sent a null image');
        $this->assertStringContainsString('products/poplin.jpg', $image);
    }

    public function test_a_product_with_no_images_at_all_gets_the_placeholder_not_null(): void
    {
        $this->cartWith($this->product);

        $image = $this->actingAs($this->user)
            ->getJson('/cart/data')->assertOk()->json('items.0.image');

        $this->assertNotNull($image);
        $this->assertStringContainsString('no-product-image', $image);
    }

    public function test_deleting_a_product_empties_the_money_with_the_basket(): void
    {
        $this->cartWith($this->product);

        // The admin deletes the product while it sits in someone's cart. Product
        // soft-deletes, so the cart line survives the delete and is pruned on the
        // next read instead.
        $this->product->delete();

        $body = $this->actingAs($this->user)->getJson('/cart/data')->assertOk()->json();

        $this->assertSame([], $body['items']);
        $this->assertSame(0, $body['cart_count']);

        // The stored totals were worked out with that line in them. Pruning it
        // did not recompute them, so the cart reported an empty basket next to
        // the old money - and checkout reads the same stored total.
        $this->assertEquals(0, $body['cart_subtotal'], 'subtotal went stale');
        $this->assertEquals(0, $body['cart_total'], 'total went stale');
    }

    public function test_each_row_carries_the_product_url_so_the_drawer_need_not_build_one(): void
    {
        $this->cartWith($this->product);

        $this->actingAs($this->user)
            ->getJson('/cart/data')->assertOk()
            ->assertJsonPath('items.0.url', route('product.show', $this->product));
    }

    public function test_the_money_fields_are_named_and_typed_like_every_other_cart_endpoint(): void
    {
        $this->cartWith($this->product);

        $body = $this->actingAs($this->user)->getJson('/cart/data')->assertOk()->json();

        foreach (['cart_subtotal', 'cart_discount', 'cart_total'] as $key) {
            $this->assertArrayHasKey($key, $body, "/cart/data did not send {$key}");
            $this->assertIsNotString($body[$key], "{$key} went out as a string");
            $this->assertIsNumeric($body[$key]);
        }
    }

    public function test_add_to_cart_reports_the_total_as_a_number_not_a_string(): void
    {
        $body = $this->actingAs($this->user)
            ->postJson('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ])->assertOk()->json();

        // It was the one cart endpoint that skipped the cast and sent "500.00".
        $this->assertIsNotString($body['cart_total']);
        $this->assertIsNumeric($body['cart_total']);
    }
}
