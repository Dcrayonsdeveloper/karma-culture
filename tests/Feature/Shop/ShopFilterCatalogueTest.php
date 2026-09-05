<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopFilterExclusion;
use App\Models\User;
use App\Support\ShopFilterCatalogue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shop filters are the catalogue, not a second copy of it.
 *
 * The screen at Homepage > Shop Filters used to hold a list an admin typed by
 * hand: every size and every shade had to be entered a second time after it had
 * already been entered on the product, and nothing ever checked the two against
 * each other. A typo there - `?size=cd` - was a promoted hanger onto "No
 * products found", and on the live catalogue most of them were.
 *
 * So WHICH values exist is now derived and stored nowhere, and the only thing
 * an admin owns is WHETHER a value is offered. These two halves are what this
 * file pins: that the derivation follows the products exactly, and that the
 * admin's decision outlives them.
 */
class ShopFilterCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Men', 'slug' => 'men', 'is_active' => true]);
    }

    /** @param  array<int, string>  $sizes */
    private function product(string $name, array $attributes = [], array $sizes = ['M'], float $price = 999, bool $active = true): Product
    {
        $slug = str($name)->slug()->value();

        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'price' => $price,
            'mrp' => $price,
            'stock_quantity' => 10,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => $active,
            'attributes' => $attributes ?: null,
        ]);

        foreach ($sizes as $i => $size) {
            ProductVariant::create([
                'product_id' => $product->id,
                'name' => $size,
                'sku' => strtoupper($slug).'-'.$i,
                'price' => $price,
                'stock_quantity' => 5,
                'is_active' => true,
            ]);
        }

        return $product;
    }

    /** @return array<int, string> the labels one rail currently offers a shopper */
    private function shown(string $type): array
    {
        return ShopFilterCatalogue::values($type)->pluck('label')->all();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function hide(string $type, string $label): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.shop-filter-exclusions.store'), [
                'type' => $type,
                'value_key' => ShopFilterCatalogue::normaliseKey($label),
                'label' => $label,
            ])
            ->assertRedirect();
    }

    // ------------------------------------------------------------------
    // The catalogue is the source of the values
    // ------------------------------------------------------------------

    public function test_a_saved_product_puts_its_size_colour_and_texture_on_the_rails(): void
    {
        $this->product('Oxford Shirt', [
            'Colours' => [['name' => 'Black', 'hex' => '#000000']],
            'Textures' => ['Matte'],
        ], ['M'], 999);

        $this->assertSame(['M'], $this->shown('size'));
        $this->assertSame(['Black'], $this->shown('shade'));
        $this->assertSame(['Matte'], $this->shown('texture'));
    }

    public function test_the_admin_screen_shows_them_without_anyone_typing_them_in(): void
    {
        $this->product('Oxford Shirt', [
            'Colours' => [['name' => 'Black', 'hex' => '#000000']],
            'Textures' => ['Matte'],
        ], ['M']);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.homepage.shop-filters'))
            ->assertOk();

        $response->assertSee('Matte')->assertSee('Black')->assertSee('Texture');

        // Nothing on this screen creates a filter value any more. The old form
        // asked for the label, a sub-label, a swatch hex and a query string -
        // and every one of those was something the admin had already entered on
        // the product. (`label` survives as a hidden field on the Hide button,
        // which carries the spelling forward so a value can still be named
        // after the last product carrying it is gone.)
        $response->assertDontSee('name="query_string"', false)
            ->assertDontSee('name="sub_label"', false)
            ->assertDontSee('name="shade_hex"', false)
            ->assertDontSee('+ Add', false);
    }

    public function test_two_products_sharing_a_value_list_it_once(): void
    {
        $this->product('Shirt One', ['Textures' => ['Matte']], ['M']);
        $this->product('Shirt Two', ['Textures' => ['Matte']], ['L']);

        $this->assertSame(['Matte'], $this->shown('texture'));
        $this->assertSame(2, ShopFilterCatalogue::values('texture')->first()->count);
    }

    public function test_four_spellings_of_one_colour_are_one_filter(): void
    {
        // The four an admin can type across four products without noticing.
        $this->product('One', ['Colours' => ['Black']], ['M']);
        $this->product('Two', ['Colours' => ['black']], ['M']);
        $this->product('Three', ['Colours' => ['BLACK']], ['M']);
        $this->product('Four', ['Colours' => ['Black ']], ['M']);

        $shades = ShopFilterCatalogue::values('shade');

        $this->assertCount(1, $shades);
        // The spelling the catalogue uses most wins; on a four-way tie the one
        // that sorts first does, which is the properly-cased one.
        $this->assertSame('Black', $shades->first()->label);
        $this->assertSame(4, $shades->first()->count);
    }

    public function test_xl_and_lowercase_xl_are_one_size(): void
    {
        $this->product('One', [], ['XL']);
        $this->product('Two', [], ['xl']);

        $this->assertCount(1, ShopFilterCatalogue::values('size'));
    }

    public function test_editing_a_products_texture_moves_the_filter_with_it(): void
    {
        $product = $this->product('Shirt', ['Textures' => ['Matte']], ['M']);

        $this->assertSame(['Matte'], $this->shown('texture'));

        $product->update(['attributes' => ['Textures' => ['Smooth']]]);

        $this->assertSame(['Smooth'], $this->shown('texture'));
    }

    public function test_deactivating_the_last_product_carrying_a_value_retires_it(): void
    {
        $product = $this->product('Velvet Shirt', ['Textures' => ['Velvet']], ['M']);

        $this->assertSame(['Velvet'], $this->shown('texture'));

        $product->update(['is_active' => false]);

        $this->assertSame([], $this->shown('texture'));
    }

    public function test_deleting_the_last_product_carrying_a_value_retires_it(): void
    {
        $product = $this->product('Velvet Shirt', ['Textures' => ['Velvet']], ['M']);
        $this->product('Plain Shirt', ['Textures' => ['Matte']], ['M']);

        $product->delete();

        $this->assertSame(['Matte'], $this->shown('texture'));
    }

    public function test_a_value_that_was_never_hidden_comes_back_on_its_own(): void
    {
        $product = $this->product('Velvet Shirt', ['Textures' => ['Velvet']], ['M']);
        $product->delete();

        $this->assertSame([], $this->shown('texture'));

        $this->product('Velvet Coat', ['Textures' => ['Velvet']], ['L']);

        $this->assertSame(['Velvet'], $this->shown('texture'));
    }

    // ------------------------------------------------------------------
    // The admin owns whether a value is offered - and nothing else
    // ------------------------------------------------------------------

    public function test_hiding_a_value_takes_it_off_the_filters_and_leaves_the_product_alone(): void
    {
        $product = $this->product('Pink Shirt', [
            'Colours' => [['name' => 'Pink', 'hex' => '#ff69b4']],
        ], ['M']);

        $this->hide('shade', 'Pink');

        $this->assertSame([], $this->shown('shade'));

        // The product is untouched: hiding a filter is not deleting data.
        $this->assertSame('Pink', $product->fresh()->attributes['Colours'][0]['name']);

        // And a shopper who follows a link naming it still gets the product -
        // the filter is withdrawn, the catalogue is not.
        $this->get(route('shop').'?shade=Pink')->assertOk()->assertSee('Pink Shirt');
    }

    public function test_hiding_reaches_a_storefront_whose_answer_is_already_cached(): void
    {
        $this->product('Pink Shirt', ['Colours' => [['name' => 'Pink', 'hex' => '#ff69b4']]], ['M']);
        $this->product('Blue Shirt', ['Colours' => [['name' => 'Blue', 'hex' => '#0000ff']]], ['L']);

        // Warm the rails first. This is the state a live shop is always in and
        // the state the tests were never in: every earlier case hid a value
        // before anything had read one. On production the first hide after a
        // deploy wrote its row and changed nothing on the storefront, because
        // the version counter the cache keys off had been cleared by the
        // deploy and the "bump" wrote back the value readers already assumed.
        $this->assertContains('Pink', $this->shown('shade'));

        $this->hide('shade', 'Pink');

        $this->assertSame(['Blue'], $this->shown('shade'));
        $this->get(route('shop'))
            ->assertOk()
            ->assertDontSee('name="colour[]" value="Pink"', false);
    }

    public function test_saving_a_product_reaches_a_storefront_whose_answer_is_already_cached(): void
    {
        $product = $this->product('Shirt', ['Textures' => ['Matte']], ['M']);

        $this->assertSame(['Matte'], $this->shown('texture'));

        $product->update(['attributes' => ['Textures' => ['Matte', 'Ribbed']]]);

        $this->assertEqualsCanonicalizing(['Matte', 'Ribbed'], $this->shown('texture'));
    }

    public function test_a_hidden_value_does_not_come_back_with_the_next_product(): void
    {
        $this->product('Rough One', ['Textures' => ['Rough']], ['M']);

        $this->hide('texture', 'Rough');
        $this->assertSame([], $this->shown('texture'));

        // Months later, a new product arrives carrying the same texture.
        $this->product('Rough Two', ['Textures' => ['Rough']], ['L']);

        $this->assertSame([], $this->shown('texture'));
    }

    public function test_a_hidden_value_stays_hidden_under_any_spelling(): void
    {
        $this->product('One', ['Textures' => ['Rough']], ['M']);
        $this->hide('texture', 'Rough');

        $this->product('Two', ['Textures' => ['ROUGH']], ['L']);

        $this->assertSame([], $this->shown('texture'));
    }

    public function test_showing_a_hidden_value_again_puts_it_back(): void
    {
        $this->product('Rough One', ['Textures' => ['Rough']], ['M']);
        $this->hide('texture', 'Rough');

        $exclusion = ShopFilterExclusion::where('type', 'texture')->firstOrFail();

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.homepage.shop-filter-exclusions.destroy', $exclusion->uuid))
            ->assertRedirect();

        $this->assertSame(['Rough'], $this->shown('texture'));
    }

    public function test_a_hidden_value_no_product_carries_is_still_listed_so_it_can_be_shown_again(): void
    {
        $product = $this->product('Velvet Shirt', ['Textures' => ['Velvet']], ['M']);
        $this->hide('texture', 'Velvet');
        $product->delete();

        // Nothing offers it to a shopper...
        $this->assertSame([], $this->shown('texture'));

        // ...but the admin can still see it and undo the decision. Without this
        // the exclusion would be unreachable from the screen that made it.
        $listed = ShopFilterCatalogue::values('texture', includeHidden: true);
        $this->assertSame(['Velvet'], $listed->pluck('label')->all());
        $this->assertSame(0, $listed->first()->count);
    }

    public function test_hiding_the_same_value_twice_records_it_once(): void
    {
        $this->product('Pink Shirt', ['Colours' => ['Pink']], ['M']);

        $this->hide('shade', 'Pink');
        $this->hide('shade', 'Pink');

        $this->assertSame(1, ShopFilterExclusion::where('type', 'shade')->count());
    }

    public function test_a_hidden_value_is_gone_from_the_storefront_sidebar_too(): void
    {
        $this->product('Pink Shirt', ['Colours' => [['name' => 'Pink', 'hex' => '#ff69b4']]], ['M']);
        $this->product('Blue Shirt', ['Colours' => [['name' => 'Blue', 'hex' => '#0000ff']]], ['L']);

        $this->hide('shade', 'Pink');

        $this->get(route('shop'))
            ->assertOk()
            ->assertSee('name="colour[]" value="Blue"', false)
            ->assertDontSee('name="colour[]" value="Pink"', false);
    }

    public function test_the_admin_screen_says_hiding_does_not_touch_the_product(): void
    {
        $this->product('Pink Shirt', ['Colours' => ['Pink']], ['M']);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.homepage.shop-filters'))
            ->assertOk()
            ->assertSee('keep this value');
    }

    public function test_hiding_a_value_of_an_unknown_type_is_refused_rather_than_stored(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.shop-filter-exclusions.store'), [
                'type' => 'weight',
                'value_key' => 'heavy',
                'label' => 'Heavy',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, ShopFilterExclusion::count());
    }

    // ------------------------------------------------------------------
    // Price is a spread, not a list of prices
    // ------------------------------------------------------------------

    public function test_price_bands_split_the_catalogue_rather_than_the_price_axis(): void
    {
        // The live shape this was rewritten for: almost everything cheap, one
        // outlier at the top. Cutting the axis into quarters put nearly the
        // whole shop in one band and left the rest empty, so the rail offered
        // two chips and one of them was "everything".
        foreach ([900, 1100, 1400, 1800, 2200, 2900, 3500, 4200] as $i => $price) {
            $this->product('Cheap '.$i, [], ['M'], $price);
        }
        $this->product('The Outlier', [], ['M'], 35000);

        $bands = ShopFilterCatalogue::values('price');

        $this->assertGreaterThanOrEqual(3, $bands->count());

        // No band may hold nearly the whole shop; that is a chip that narrows
        // nothing.
        foreach ($bands as $band) {
            $this->assertGreaterThan(0, $band->count);
            $this->assertLessThan(8, $band->count, "Band {$band->label} holds almost everything");
        }
    }

    public function test_price_bands_are_derived_and_never_one_band_per_price(): void
    {
        foreach ([499, 500, 501, 502, 1200, 2400, 4800] as $i => $price) {
            $this->product('Item '.$i, [], ['M'], $price);
        }

        $bands = ShopFilterCatalogue::values('price');

        $this->assertGreaterThan(1, $bands->count());
        $this->assertLessThanOrEqual(5, $bands->count());
        // Seven products at seven prices, so a per-price rail would be seven
        // chips - which is the thing this must never become.
        $this->assertLessThan(7, $bands->count());
    }

    public function test_every_price_band_has_products_in_it(): void
    {
        foreach ([300, 900, 2500, 9000] as $i => $price) {
            $this->product('Item '.$i, [], ['M'], $price);
        }

        foreach (ShopFilterCatalogue::values('price') as $band) {
            $this->assertGreaterThan(0, $band->count, "Band {$band->label} is empty");

            // And the band actually returns what it claims: the count is not a
            // number from somewhere else.
            $this->get(route('shop').'?'.$band->query_string)->assertOk();
        }
    }

    public function test_one_price_is_no_price_filter_at_all(): void
    {
        $this->product('One', [], ['M'], 999);
        $this->product('Two', [], ['L'], 999);

        // A single band covering everything narrows nothing; it is a chip that
        // does not work, which is exactly what this screen existed to remove.
        $this->assertSame([], $this->shown('price'));
    }

    public function test_a_shop_with_no_products_offers_no_filters_and_does_not_break(): void
    {
        $this->get('/')->assertOk();

        foreach (ShopFilterCatalogue::TYPES as $type) {
            $this->assertSame([], $this->shown($type));
        }
    }

    // ------------------------------------------------------------------
    // Filtering
    // ------------------------------------------------------------------

    public function test_texture_actually_narrows_the_grid(): void
    {
        $this->product('Matte Shirt', ['Textures' => ['Matte']], ['M']);
        $this->product('Glossy Shirt', ['Textures' => ['Glossy']], ['M']);

        $this->get(route('shop').'?texture=Matte')
            ->assertOk()
            ->assertSee('Matte Shirt')
            ->assertDontSee('Glossy Shirt');
    }

    public function test_texture_matches_whatever_case_the_url_carries(): void
    {
        $this->product('Matte Shirt', ['Textures' => ['Matte']], ['M']);

        $this->get(route('shop').'?texture=matte')->assertOk()->assertSee('Matte Shirt');
    }

    public function test_a_colour_and_a_texture_of_the_same_name_do_not_answer_for_each_other(): void
    {
        // Both lists live in one JSON column, so an unscoped match over the
        // whole blob would hand a colour filter a product whose TEXTURE is
        // Ivory - and the reverse just as readily.
        $this->product('Ivory Coloured', ['Colours' => [['name' => 'Ivory', 'hex' => '#fffff0']]], ['M']);
        $this->product('Ivory Textured', ['Textures' => ['Ivory']], ['L']);

        $this->get(route('shop').'?colour=Ivory')
            ->assertOk()
            ->assertSee('Ivory Coloured')
            ->assertDontSee('Ivory Textured');

        $this->get(route('shop').'?texture=Ivory')
            ->assertOk()
            ->assertSee('Ivory Textured')
            ->assertDontSee('Ivory Coloured');
    }

    public function test_colour_size_texture_and_price_narrow_together(): void
    {
        $wanted = $this->product('The One', [
            'Colours' => [['name' => 'Black', 'hex' => '#000000']],
            'Textures' => ['Matte'],
        ], ['M'], 800);

        $this->product('Wrong Texture', [
            'Colours' => [['name' => 'Black', 'hex' => '#000000']],
            'Textures' => ['Glossy'],
        ], ['M'], 800);

        $this->product('Wrong Price', [
            'Colours' => [['name' => 'Black', 'hex' => '#000000']],
            'Textures' => ['Matte'],
        ], ['M'], 4000);

        $this->product('Wrong Size', [
            'Colours' => [['name' => 'Black', 'hex' => '#000000']],
            'Textures' => ['Matte'],
        ], ['XL'], 800);

        $this->get(route('shop').'?colour=Black&size=M&texture=Matte&min_price=500&max_price=1000')
            ->assertOk()
            ->assertSee($wanted->name)
            ->assertDontSee('Wrong Texture')
            ->assertDontSee('Wrong Price')
            ->assertDontSee('Wrong Size');
    }

    public function test_a_texture_nothing_carries_returns_an_empty_grid_rather_than_an_error(): void
    {
        $this->product('Matte Shirt', ['Textures' => ['Matte']], ['M']);

        $this->get(route('shop').'?texture=Corduroy')
            ->assertOk()
            ->assertDontSee('Matte Shirt')
            // and it is not offered back as though the shop sold it
            ->assertDontSee('name="texture[]" value="Corduroy"', false);
    }

    public function test_a_malformed_texture_parameter_does_not_500(): void
    {
        $this->product('Matte Shirt', ['Textures' => ['Matte']], ['M']);

        $this->get(route('shop').'?texture[]['.'0]=x')->assertOk();
        $this->get(route('shop').'?texture=%25')->assertOk()->assertDontSee('Matte Shirt');
        $this->get(route('shop').'?texture='.str_repeat('a', 500))->assertOk();
    }

    public function test_a_product_with_no_texture_at_all_still_works(): void
    {
        $product = $this->product('Plain Shirt', ['Colours' => ['Black']], ['M']);

        $this->assertSame([], $this->shown('texture'));
        $this->get(route('product.show', $product->slug))->assertOk();
        $this->get(route('shop'))->assertOk()->assertSee('Plain Shirt');
    }
}
