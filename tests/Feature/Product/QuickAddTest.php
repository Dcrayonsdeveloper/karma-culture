<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The round add-to-cart button on a product card.
 *
 * The button opens a popup, the popup reads /product/{id}/quick-view, and what
 * comes back has to be exactly what /cart/add will accept - a card that offers
 * a size the cart then refuses is worse than a card with no quick-add at all.
 * These tests hold the two ends together.
 */
class QuickAddTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Menswear',
            'slug' => 'menswear',
            'is_active' => true,
        ]);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Premium Polo T-Shirt',
            'slug' => 'premium-polo-t-shirt',
            'sku' => 'PP-001',
            'price' => 1199,
            'mrp' => 1899,
            'cost_price' => 500,
            'stock_quantity' => 20,
            'stock_status' => 'in_stock',
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ], $overrides));
    }

    public function test_quick_view_offers_the_sizes_and_colours_the_product_sells(): void
    {
        $product = $this->product([
            'attributes' => ['Colours' => [['name' => 'Navy', 'hex' => '#001f54'], ['name' => 'Cream']]],
        ]);

        $small = ProductVariant::create([
            'product_id' => $product->id, 'name' => 'S', 'sku' => 'PP-001-S',
            'price' => 1199, 'mrp' => 1899, 'stock_quantity' => 4, 'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id, 'name' => 'L', 'sku' => 'PP-001-L',
            'price' => 1399, 'mrp' => 1999, 'stock_quantity' => 0, 'is_active' => true,
        ]);
        // Withdrawn rows must not reach the popup: the product page does not
        // render them and /cart/add refuses them.
        ProductVariant::create([
            'product_id' => $product->id, 'name' => 'XXL', 'sku' => 'PP-001-XXL',
            'price' => 1499, 'stock_quantity' => 9, 'is_active' => false,
        ]);

        $response = $this->getJson("/product/{$product->id}/quick-view");

        $response->assertOk()
            ->assertJsonPath('sizes.0.label', 'S')
            ->assertJsonPath('sizes.0.variant_id', $small->id)
            ->assertJsonPath('sizes.0.stock', 4)
            // Carried through so the popup can show the size and strike it out
            // rather than silently dropping it - a missing size reads as a bug.
            ->assertJsonPath('sizes.1.label', 'L')
            ->assertJsonPath('sizes.1.stock', 0)
            ->assertJsonPath('colours.0.name', 'Navy')
            ->assertJsonPath('colours.0.hex', '#001f54')
            ->assertJsonPath('colours.1.name', 'Cream')
            ->assertJsonPath('colours.1.hex', null);

        $this->assertSame(['S', 'L'], array_column($response->json('sizes'), 'label'));

        // Compared numerically, not with assertJsonPath: json_encode writes a
        // whole float as 1199, so a strict identity check would be asserting
        // PHP's number formatting rather than the price. Every money field in
        // this app is sent the same way.
        $this->assertEquals(1199, $response->json('sizes.0.price'));
        $this->assertEquals(1899, $response->json('sizes.0.mrp'));
        // The size row's own price, not the product's - the popup quotes what
        // the cart will actually charge.
        $this->assertEquals(1399, $response->json('sizes.1.price'));
    }

    public function test_quick_view_falls_back_to_the_free_text_size_attribute(): void
    {
        // Older products hold their sizes as one free-text string. The product
        // page splits it into buttons; so must the popup, or those products get
        // a quick-add that cannot name a size the cart accepts.
        $product = $this->product(['attributes' => ['Size' => 'S, M, XL']]);

        $response = $this->getJson("/product/{$product->id}/quick-view");

        $response->assertOk();
        $this->assertSame(['S', 'M', 'XL'], array_column($response->json('sizes'), 'label'));
        // No size rows, so stock is the product's own.
        $this->assertSame(20, $response->json('sizes.0.stock'));
        $this->assertNull($response->json('sizes.0.variant_id'));
    }

    public function test_quick_view_404s_an_inactive_product(): void
    {
        $product = $this->product(['is_active' => false]);

        $this->getJson("/product/{$product->id}/quick-view")->assertNotFound();
    }

    public function test_quick_view_is_open_to_a_guest(): void
    {
        // The popup's login gate runs in the browser before the request, but a
        // guest reading options is not a write - and 401ing here would show an
        // error toast on the way to the login page.
        $this->getJson("/product/{$this->product()->id}/quick-view")->assertOk();
    }

    public function test_the_cart_accepts_the_payload_the_popup_builds(): void
    {
        $product = $this->product([
            'attributes' => ['Colours' => [['name' => 'Navy', 'hex' => '#001f54']]],
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => 'M', 'sku' => 'PP-001-M',
            'price' => 1299, 'mrp' => 1899, 'stock_quantity' => 6, 'is_active' => true,
        ]);

        $options = $this->getJson("/product/{$product->id}/quick-view")->json();

        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->postJson('/cart/add', [
                'product_id' => $options['id'],
                'variant_id' => $options['sizes'][0]['variant_id'],
                'size' => $options['sizes'][0]['label'],
                'colour' => $options['colours'][0]['name'],
                'quantity' => 2,
            ]);

        $response->assertOk()->assertJsonPath('cart_count', 2);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'size' => 'M',
            'colour' => 'Navy',
            'quantity' => 2,
            'price' => 1299,
        ]);
    }

    public function test_the_cart_still_refuses_a_size_the_product_does_not_sell(): void
    {
        // The guard CartController::add has always had, kept honest after the
        // derivation moved into ProductOptions.
        $product = $this->product();
        ProductVariant::create([
            'product_id' => $product->id, 'name' => 'M', 'sku' => 'PP-001-M2',
            'price' => 1299, 'stock_quantity' => 6, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->postJson('/cart/add', [
                'product_id' => $product->id,
                'size' => 'XXXL',
                'quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'That size is not available for this product.');
    }

    public function test_the_cart_accepts_a_size_whose_casing_drifted(): void
    {
        $product = $this->product(['attributes' => ['Size' => 'S, M, XL']]);

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->postJson('/cart/add', [
                'product_id' => $product->id,
                'size' => 'm',
                'quantity' => 1,
            ])
            ->assertOk();
    }

    public function test_a_guest_adding_from_the_popup_is_sent_to_sign_in(): void
    {
        // The button gets there first, but a stale tab still lands here and the
        // 401 is what the front end turns into the trip to the login page.
        $this->postJson('/cart/add', [
            'product_id' => $this->product()->id,
            'quantity' => 1,
        ])->assertStatus(401);
    }

    public function test_the_card_renders_the_round_button_only_while_in_stock(): void
    {
        $inStock = $this->product();
        $soldOut = $this->product([
            'name' => 'Sold Out Polo', 'slug' => 'sold-out-polo', 'sku' => 'PP-002',
            'stock_quantity' => 0, 'stock_status' => 'out_of_stock',
        ]);

        $html = fn (Product $p) => (string) view('components.product-card', ['product' => $p])->render();

        $this->assertStringContainsString("\$store.quickAdd.show({$inStock->id})", $html($inStock));
        $this->assertStringNotContainsString('$store.quickAdd.show(', $html($soldOut));
    }
}
