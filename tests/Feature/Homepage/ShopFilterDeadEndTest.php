<?php

namespace Tests\Feature\Homepage;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A "Shop It Your Way" hanger can no longer be a dead end.
 *
 * The reported bug: /products?size=cd, reached by clicking a hanger on the home
 * page, showed "0 products found" with "cd" drawn in the sidebar as though it
 * were a size the shop sells. "cd" was a typo an admin had saved into a hanger
 * months earlier, and nothing between that field and the shopper's screen ever
 * checked it against a product. On the live catalogue six of the eight size
 * hangers and all six shade hangers were the same dead end.
 *
 * Two rounds of fixes tried to catch that after the fact - hide the empty
 * hangers (which emptied the whole Shade tab off the live site and was taken
 * back out), then report the count on the admin screen so somebody could go and
 * fix them by hand. The rail is derived from the catalogue now, which removes
 * the cause instead: a hanger exists only while a product carries its value, so
 * there is no field left to type "cd" into. What this file pins is that this is
 * really true end to end, and that the OTHER half of the original fix still
 * holds: a value that arrives in the URL and that nothing carries must not be
 * drawn in the sidebar as though the shop sold it.
 */
class ShopFilterDeadEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Men', 'slug' => 'men', 'is_active' => true]);

        $product = Product::create([
            'name' => 'Oxford Shirt',
            'slug' => 'oxford-shirt',
            'sku' => 'OXFORD',
            'price' => 799,
            'mrp' => 999,
            'cost_price' => 300,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
            'attributes' => [
                'Colours' => [['name' => 'Black', 'hex' => '#000000']],
                'Textures' => ['Matte'],
            ],
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'M',
            'sku' => 'OXFORD-M',
            'price' => 799,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
    }

    // ------------------------------------------------------------------
    // The rail hangs the catalogue, so every hanger leads somewhere
    // ------------------------------------------------------------------

    public function test_every_hanger_on_the_rail_opens_a_listing_with_products_on_it(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // The one product's own values, and nothing else.
        $this->assertStringContainsString('?size=M"', $html);
        $this->assertStringContainsString('?shade=Black"', $html);
        $this->assertStringContainsString('?texture=Matte"', $html);

        foreach (['?size=M', '?shade=Black', '?texture=Matte'] as $query) {
            $this->get('/products'.$query)->assertOk()->assertSee('Oxford Shirt');
        }
    }

    public function test_a_size_the_shop_does_not_stock_cannot_get_onto_the_rail(): void
    {
        // "cd" was the live typo. There is no longer a field to put it in: the
        // rail can only ever say what a product says.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('?size=cd"', $html);
        $this->assertStringContainsString('--kk-rail-count: 1', $html);
    }

    public function test_a_tab_the_catalogue_has_nothing_for_is_not_rendered(): void
    {
        // One product at one price, so there is no price band to offer - and a
        // tab with nothing on it is a button onto a bare rail.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString("tab='size'", $html);
        $this->assertStringNotContainsString("tab='price'", $html);
    }

    public function test_deactivating_the_product_takes_its_hangers_off_the_rail(): void
    {
        Product::query()->firstOrFail()->update(['is_active' => false]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('?size=M"', $html);
        $this->assertStringNotContainsString('?shade=Black"', $html);
    }

    // ------------------------------------------------------------------
    // A value that only the URL believes in
    // ------------------------------------------------------------------

    public function test_the_sidebar_does_not_offer_a_size_no_product_carries(): void
    {
        $response = $this->get('/products?size=cd')->assertOk();

        // Not a pickable size...
        $response->assertDontSee('name="size[]" value="cd"', false)
            // ...but still removable, or the shopper is stranded on an empty page.
            ->assertSee('Size: cd', false)
            ->assertSee('name="size[]" value="M"', false);

        $this->assertSame(0, $response->viewData('products')->total());
    }

    public function test_the_sidebar_does_not_offer_a_colour_no_product_lists(): void
    {
        $response = $this->get('/products?shade=cinnamon')->assertOk();

        $response->assertDontSee('name="colour[]" value="cinnamon"', false)
            ->assertSee('cinnamon');

        $this->assertSame(0, $response->viewData('products')->total());
    }

    public function test_the_sidebar_does_not_offer_a_texture_no_product_lists(): void
    {
        $response = $this->get('/products?texture=corduroy')->assertOk();

        $response->assertDontSee('name="texture[]" value="corduroy"', false)
            // The chip above the grid is what takes it back off.
            ->assertSee('Texture: corduroy', false)
            ->assertSee('name="texture[]" value="Matte"', false);

        $this->assertSame(0, $response->viewData('products')->total());
    }

    /**
     * The rule is "nothing carries it", not "nothing matches right now": a size
     * the shop stocks has to stay tickable after another filter has emptied the
     * grid, or it can never be unticked from the sidebar.
     */
    public function test_a_stocked_size_stays_tickable_once_another_filter_empties_it(): void
    {
        $this->get('/products?size=M&max_price=1')
            ->assertOk()
            ->assertSee('name="size[]" value="M"', false);
    }

    public function test_a_stocked_texture_stays_tickable_once_another_filter_empties_it(): void
    {
        $this->get('/products?texture=Matte&max_price=1')
            ->assertOk()
            ->assertSee('name="texture[]" value="Matte"', false);
    }
}
