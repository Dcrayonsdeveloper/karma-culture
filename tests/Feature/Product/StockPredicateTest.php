<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * One answer to "is this in stock", everywhere it is asked.
 *
 * The product card paints its "Out of Stock" badge from Product::isInStock(),
 * which is BOTH halves: stock_status = in_stock AND stock_quantity > 0. Three
 * places read only one half and so disagreed with the badge:
 *
 *  - scopeInStock() read the flag alone, so the assistant recommended a product
 *    with an empty shelf;
 *  - "In Stock Only" read the quantity alone, so it kept a product flagged
 *    out_of_stock and then drew "Out of Stock" across the card it let through;
 *  - POST /cart/add read the quantity alone, which was not cosmetic - it would
 *    sell a product the card, the PDP, the filter and Reorder all call sold out.
 *
 * Checkout is deliberately excluded: stock_quantity is the purchase contract
 * there, and gating payment on a merchandising flag would refuse orders that
 * are genuinely fulfillable.
 */
class StockPredicateTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);
    }

    private function makeProduct(string $name, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => strtoupper(Str::slug($name)),
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ], $overrides));
    }

    public function test_the_scope_matches_the_badge_on_every_combination(): void
    {
        $buyable = $this->makeProduct('Scope Buyable');
        $this->makeProduct('Scope Flagged Out', ['stock_status' => 'out_of_stock', 'stock_quantity' => 5]);
        $this->makeProduct('Scope Empty Shelf', ['stock_status' => 'in_stock', 'stock_quantity' => 0]);
        $this->makeProduct('Scope Backorder', ['stock_status' => 'backorder', 'stock_quantity' => 5]);

        $this->assertSame([$buyable->id], Product::query()->inStock()->pluck('id')->all());

        foreach (Product::all() as $product) {
            $this->assertSame(
                $product->isInStock(),
                Product::query()->inStock()->whereKey($product->id)->exists(),
                $product->name.': the scope and the badge disagree.'
            );
        }
    }

    public static function listingUrls(): array
    {
        return [
            'shop' => ['/shop?in_stock=1'],
            'category' => ['/category/shirts?in_stock=1'],
        ];
    }

    #[DataProvider('listingUrls')]
    public function test_in_stock_only_hides_whatever_the_card_would_badge(string $url): void
    {
        $this->makeProduct('Buyable Shirt');
        // Units on the shelf, but flagged sold out: kept by a quantity-only filter.
        $this->makeProduct('Flagged Out Shirt', ['stock_status' => 'out_of_stock', 'stock_quantity' => 5]);
        // The ordinary just-sold-out state: checkout decrements the quantity and
        // never flips the flag.
        $this->makeProduct('Empty Shelf Shirt', ['stock_status' => 'in_stock', 'stock_quantity' => 0]);

        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('Buyable Shirt', $html);
        $this->assertStringNotContainsString('Flagged Out Shirt', $html);
        $this->assertStringNotContainsString('Empty Shelf Shirt', $html);
    }

    public function test_the_cart_refuses_what_the_storefront_calls_sold_out(): void
    {
        // The card badges it, the PDP hides both CTAs - and the cart drawer's
        // quick-add used to take it anyway, because this endpoint read the
        // quantity and never the flag.
        $product = $this->makeProduct('Flagged Out Shirt', [
            'stock_status' => 'out_of_stock',
            'stock_quantity' => 5,
        ]);

        // Signed in: putting anything in a cart takes an account now, and an
        // unauthenticated post is turned away at the route before this guard
        // gets a chance to run.
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error', 'This item is currently out of stock.');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_the_cart_still_takes_what_is_genuinely_buyable(): void
    {
        // The guard above must not shut the ordinary path.
        $product = $this->makeProduct('Buyable Shirt');

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])
            ->assertSuccessful();

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_the_drawer_rail_only_offers_products_it_can_add(): void
    {
        // Every tile in the drawer is a bare Add to Cart with nowhere to show a
        // badge, so a sold-out one is a button that can only return an error.
        $inCart = $this->makeProduct('Cart Item Shirt');
        $this->makeProduct('Rail Buyable Shirt');
        $this->makeProduct('Rail Flagged Out', ['stock_status' => 'out_of_stock', 'stock_quantity' => 5]);
        $this->makeProduct('Rail Empty Shelf', ['stock_status' => 'in_stock', 'stock_quantity' => 0]);

        // The rail only considers products that have an image.
        foreach (Product::where('name', 'like', 'Rail %')->get() as $product) {
            $product->images()->create(['url' => 'products/x.jpg', 'position' => 0, 'is_primary' => true]);
        }

        // Signed in, because a guest's cart is keyed on the session id and the
        // test session driver is `array` - it would not survive the hop from the
        // add to the fetch, and the rail would come back empty for the wrong reason.
        $this->actingAs(User::factory()->create());

        $this->postJson('/cart/add', ['product_id' => $inCart->id, 'quantity' => 1])->assertSuccessful();

        $names = collect($this->getJson('/cart/recommendations')->assertOk()->json('products'))
            ->pluck('name')
            ->all();

        $this->assertNotEmpty($names, 'The rail came back empty, so it proves nothing.');
        $this->assertContains('Rail Buyable Shirt', $names);
        $this->assertNotContains('Rail Flagged Out', $names);
        $this->assertNotContains('Rail Empty Shelf', $names);
    }
}
