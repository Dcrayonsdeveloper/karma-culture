<?php

namespace Tests\Feature\Homepage;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopFilterItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A "Shop It Your Way" hanger has to open a listing with something on it.
 *
 * The reported bug: /shop?size=cd, reached by clicking a hanger on the home
 * page, showed "0 products found" with "cd" drawn in the sidebar as though it
 * were a size the shop sells. "cd" was a typo an admin had saved into a hanger
 * months earlier, and nothing between that field and the shopper's screen ever
 * checked it against a product. On the live catalogue six of the eight size
 * hangers and all six shade hangers were the same dead end.
 *
 * Two halves, both covered here: the rail stops promoting a filter with
 * nothing behind it, and the sidebar stops offering a value off the URL that
 * no product carries.
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
            'attributes' => ['Colours' => [['name' => 'Black', 'hex' => '#000000']]],
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

    private function hanger(string $type, string $label, ?string $query, int $position = 0): ShopFilterItem
    {
        return ShopFilterItem::create([
            'type' => $type,
            'label' => $label,
            'query_string' => $query,
            'position' => $position,
            'is_active' => true,
        ]);
    }

    public function test_a_size_the_shop_does_not_stock_is_left_off_the_rail(): void
    {
        $this->hanger('size', 'M', 'size=M');
        $this->hanger('size', 'cd', 'size=cd', 1);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('?size=M"', $html);
        $this->assertStringNotContainsString('?size=cd"', $html);
        // And the rail is sized to what is left on it, not to what was saved.
        $this->assertStringContainsString('--kk-rail-count: 1', $html);
    }

    public function test_a_shade_no_product_lists_is_left_off_the_rail(): void
    {
        $this->hanger('shade', 'Black', 'shade=Black');
        $this->hanger('shade', 'Cinnamon', 'shade=cinnamon', 1);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('?shade=Black"', $html);
        $this->assertStringNotContainsString('?shade=cinnamon"', $html);
    }

    public function test_a_price_bound_holding_nothing_is_left_off_the_rail(): void
    {
        $this->hanger('price', 'Under 1k', 'price_max=1000');
        $this->hanger('price', 'Over 7k', 'price_min=7000', 1);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('?price_max=1000"', $html);
        $this->assertStringNotContainsString('?price_min=7000"', $html);
    }

    /** A hanger with no query is a plain tile, not a dead end - it still hangs. */
    public function test_a_hanger_with_no_query_string_still_hangs(): void
    {
        $this->hanger('size', 'Coming Soon', null);

        $this->get('/')->assertOk()->assertSee('Coming Soon');
    }

    /** A tab whose hangers have all come down is a button onto a bare rail. */
    public function test_a_tab_left_with_nothing_on_it_is_not_rendered(): void
    {
        $this->hanger('size', 'M', 'size=M');
        $this->hanger('shade', 'Cinnamon', 'shade=cinnamon');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString("tab='size'", $html);
        $this->assertStringNotContainsString("tab='shade'", $html);
    }

    /** The size off a hanger still filters the shop it opens. */
    public function test_a_live_hanger_opens_a_listing_with_products_on_it(): void
    {
        $this->get('/shop?size=M')
            ->assertOk()
            ->assertSee('Oxford Shirt');
    }

    public function test_the_sidebar_does_not_offer_a_size_no_product_carries(): void
    {
        $response = $this->get('/shop?size=cd')->assertOk();

        // Not a pickable size...
        $response->assertDontSee('name="size[]" value="cd"', false)
            // ...but still removable, or the shopper is stranded on an empty page.
            ->assertSee('Size: cd', false)
            ->assertSee('name="size[]" value="M"', false);

        $this->assertSame(0, $response->viewData('products')->total());
    }

    public function test_the_sidebar_does_not_offer_a_colour_no_product_lists(): void
    {
        $response = $this->get('/shop?shade=cinnamon')->assertOk();

        $response->assertDontSee('name="colour[]" value="cinnamon"', false)
            ->assertSee('cinnamon');

        $this->assertSame(0, $response->viewData('products')->total());
    }

    /**
     * The rule is "nothing carries it", not "nothing matches right now": a size
     * the shop stocks has to stay tickable after another filter has emptied the
     * grid, or it can never be unticked from the sidebar.
     */
    public function test_a_stocked_size_stays_tickable_once_another_filter_empties_it(): void
    {
        $this->get('/shop?size=M&max_price=1')
            ->assertOk()
            ->assertSee('name="size[]" value="M"', false);
    }

    public function test_the_admin_screen_flags_a_hanger_that_matches_nothing(): void
    {
        $this->hanger('size', 'M', 'size=M');
        $this->hanger('size', 'cd', 'size=cd', 1);

        $this->actingAs(User::factory()->create(['role' => 'admin']), 'admin')
            ->get(route('admin.homepage.shop-filters'))
            ->assertOk()
            ->assertSee('0 &middot; hidden', false)
            // And it offers the sizes that would work instead of leaving the
            // admin to guess at them.
            ->assertSee('<option value="size=M">', false);
    }

    /**
     * The quieter half: `price=2` is not a bound the shop reads, so the hanger
     * opens the whole catalogue. It is not a dead end and stays hung - but the
     * count beside it would read as healthy, so the screen has to say why.
     */
    public function test_the_admin_screen_flags_a_query_the_shop_does_not_read(): void
    {
        $this->hanger('price', '1k - 2k', 'price=2');

        $this->actingAs(User::factory()->create(['role' => 'admin']), 'admin')
            ->get(route('admin.homepage.shop-filters'))
            ->assertOk()
            ->assertSee('>ignored<', false);

        $this->get('/')->assertOk()->assertSee('?price=2"', false);
    }
}
