<?php

namespace Tests\Feature\Product;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sidebar offered Sub-categories, Size, Colour, Price and Availability but
 * never Brand, even though every product carries a brand_id and both listing
 * views already had the markup for a brand chip.
 */
class BrandFilterTest extends TestCase
{
    use RefreshDatabase;

    private Category $dresses;
    private Category $shoes;
    private Brand $premium;
    private Brand $organics;
    private Brand $unstocked;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dresses = Category::create(['name' => 'Girls Dresses', 'slug' => 'girls-dresses', 'is_active' => true]);
        $this->shoes = Category::create(['name' => 'Footwear', 'slug' => 'footwear', 'is_active' => true]);

        $this->premium = Brand::create(['name' => 'FK Premium', 'is_active' => true]);
        $this->organics = Brand::create(['name' => 'FK Organics', 'is_active' => true]);
        $this->unstocked = Brand::create(['name' => 'Nike', 'is_active' => true]);

        $this->product('Party Frock', 'party-frock', $this->dresses, $this->premium);
        $this->product('Cotton Frock', 'cotton-frock', $this->dresses, $this->organics);
        $this->product('School Shoes', 'school-shoes', $this->shoes, $this->premium);
    }

    private function product(string $name, string $slug, Category $category, Brand $brand): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'price' => 799,
            'mrp' => 999,
            'cost_price' => 300,
            'stock_quantity' => 25,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_category_sidebar_offers_a_brand_filter(): void
    {
        $this->get('/category/girls-dresses')
            ->assertOk()
            ->assertSee('name="brand[]" value="fk-premium"', false)
            ->assertSee('name="brand[]" value="fk-organics"', false);
    }

    public function test_shop_sidebar_offers_a_brand_filter(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('name="brand[]" value="fk-premium"', false);
    }

    /**
     * The brands table still holds demo rows from the seed. Offering "Nike" on a
     * kidswear shop only leads to an empty result page.
     */
    public function test_brands_with_no_products_are_not_offered(): void
    {
        $this->get('/shop')->assertDontSee('name="brand[]" value="nike"', false);
        $this->get('/category/girls-dresses')->assertDontSee('name="brand[]" value="nike"', false);
    }

    /** A brand stocked elsewhere in the shop is not offered inside this category. */
    public function test_category_brand_list_is_scoped_to_that_category(): void
    {
        $this->get('/category/footwear')
            ->assertSee('name="brand[]" value="fk-premium"', false)
            ->assertDontSee('name="brand[]" value="fk-organics"', false);
    }

    public function test_category_brand_filter_narrows_the_products(): void
    {
        $this->get('/category/girls-dresses?brand[]=fk-premium')
            ->assertOk()
            ->assertSee('Party Frock')
            ->assertDontSee('Cotton Frock');
    }

    public function test_shop_brand_filter_narrows_the_products(): void
    {
        $this->get('/shop?brand[]=fk-organics')
            ->assertOk()
            ->assertSee('Cotton Frock')
            ->assertDontSee('Party Frock')
            ->assertDontSee('School Shoes');
    }

    /** Ticking two boxes widens the result rather than returning nothing. */
    public function test_selecting_two_brands_returns_both(): void
    {
        $this->get('/shop?brand[]=fk-premium&brand[]=fk-organics')
            ->assertOk()
            ->assertSee('Party Frock')
            ->assertSee('Cotton Frock');
    }

    public function test_an_unknown_brand_slug_returns_nothing_rather_than_everything(): void
    {
        $this->get('/shop?brand[]=no-such-brand')
            ->assertOk()
            ->assertDontSee('Party Frock')
            ->assertDontSee('Cotton Frock');

        $this->get('/category/girls-dresses?brand[]=no-such-brand')
            ->assertOk()
            ->assertDontSee('Party Frock');
    }

    /**
     * products/index.blade.php has always dereferenced $brands for the active
     * filter chip and the meta description, but the controller never passed it,
     * so any ?brand= URL - including the ones already linked from search - hit
     * "Undefined variable $brands" and 500'd.
     */
    public function test_a_scalar_brand_parameter_does_not_error(): void
    {
        $this->get('/shop?brand=fk-premium')
            ->assertOk()
            ->assertSee('Party Frock')
            ->assertDontSee('Cotton Frock');
    }

    /** The chip names the brand, so the shopper can see and clear what is applied. */
    public function test_the_applied_brand_shows_as_a_removable_chip(): void
    {
        $this->get('/category/girls-dresses?brand[]=fk-premium')
            ->assertSee('Active Filters')
            ->assertSee('FK Premium');
    }
}
