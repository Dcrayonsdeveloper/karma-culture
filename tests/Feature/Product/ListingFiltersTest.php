<?php

namespace Tests\Feature\Product;

use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every storefront listing offers the same filter sidebar.
 *
 * The shop and the category page each carried their own near-identical copy and
 * had drifted apart, search carried a third cut-down one that knew only
 * category, brand and price, and the brand, deals, flash-sale, new-arrivals and
 * bestsellers pages offered no filters at all - so a shopper who reached the
 * same sixty products by a different route lost the ability to narrow them.
 */
class ListingFiltersTest extends TestCase
{
    use RefreshDatabase;

    private Category $men;

    private Category $shirts;

    private Brand $biba;

    private Product $shirt;

    private FlashSale $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->men = Category::create(['name' => 'Men', 'slug' => 'men', 'is_active' => true]);
        $this->shirts = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $this->men->id, 'is_active' => true]);

        $this->biba = Brand::create(['name' => 'Biba', 'is_active' => true]);

        $this->shirt = $this->product('Oxford Shirt', 'oxford-shirt', 799, 999);
        $this->product('Linen Shirt', 'linen-shirt', 1499, 1499);

        $this->sale = FlashSale::create([
            'name' => 'Weekend Sale',
            'slug' => 'weekend-sale',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);
        $this->sale->products()->attach($this->shirt->id, ['sale_price' => 499, 'stock_limit' => 10, 'sold_count' => 1]);
    }

    private function product(string $name, string $slug, float $price, float $mrp): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'price' => $price,
            'mrp' => $mrp,
            'cost_price' => 300,
            'stock_quantity' => 20,
            'category_id' => $this->shirts->id,
            'brand_id' => $this->biba->id,
            'rating' => 4,
            'status' => 'approved',
            'is_active' => true,
            'attributes' => ['Colours' => [['name' => 'Black', 'hex' => '#000000']]],
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'M',
            'sku' => strtoupper($slug).'-M',
            'price' => $price,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        return $product;
    }

    /** Every page a shopper can browse products on. */
    public static function listingPages(): array
    {
        return [
            'shop' => ['/shop'],
            'category' => ['/category/men'],
            'sub-category' => ['/category/shirts'],
            'search' => ['/search?q=shirt'],
            'brand' => ['/brands/biba'],
            'deals' => ['/deals'],
            'flash sale' => ['/flash-sale/weekend-sale'],
            'new arrivals' => ['/new-arrivals'],
            'bestsellers' => ['/bestsellers'],
        ];
    }

    #[DataProvider('listingPages')]
    public function test_every_listing_offers_the_same_filters(string $url): void
    {
        $response = $this->get($url)->assertOk();

        foreach (['Size', 'Colour', 'Price Range', 'Rating', 'Availability', 'In Stock Only'] as $section) {
            $response->assertSee($section, false);
        }

        // The controls themselves, not just the headings.
        $response->assertSee('name="size[]" value="M"', false)
            ->assertSee('name="colour[]" value="Black"', false)
            ->assertSee('name="min_price"', false)
            ->assertSee('name="max_price"', false)
            ->assertSee('name="in_stock"', false);
    }

    /**
     * A sidebar that renders but never reaches the query is the bug this covers,
     * so each page is asked for a bound nothing can satisfy and has to come back
     * with an empty grid. The paginator is read rather than the HTML because the
     * layout carries "recently viewed" and "recommended" rails that mention
     * products the listing itself has filtered out.
     */
    #[DataProvider('listingPages')]
    public function test_every_listing_actually_applies_a_filter(string $url): void
    {
        $join = str_contains($url, '?') ? '&' : '?';

        $response = $this->get($url.$join.'max_price=1')->assertOk();

        $this->assertSame(0, $response->viewData('products')->total(), "{$url} ignored max_price");
    }

    /** And the same filter set at a real value keeps what matches. */
    public function test_a_price_filter_keeps_the_products_inside_the_bound(): void
    {
        $response = $this->get('/shop?max_price=1000')->assertOk();

        $this->assertSame(
            ['Oxford Shirt'],
            $response->viewData('products')->pluck('name')->all()
        );
    }

    /** The deals page has no On Sale box, because everything on it is on sale. */
    public function test_deals_hides_the_redundant_on_sale_box(): void
    {
        $this->get('/deals')->assertOk()->assertDontSee('name="on_sale"', false);
        $this->get('/shop')->assertOk()->assertSee('name="on_sale"', false);
    }

    /** The brand page has no Brand facet, because there is only ever one. */
    public function test_brand_page_hides_the_redundant_brand_facet(): void
    {
        $this->get('/brands/biba')->assertOk()->assertDontSee('name="brand[]"', false);
        $this->get('/shop')->assertOk()->assertSee('name="brand[]" value="biba"', false);
    }

    /** Search carries the phrase through a filter submit, or the results reset. */
    public function test_search_keeps_the_query_when_a_filter_is_applied(): void
    {
        $this->get('/search?q=shirt')
            ->assertOk()
            ->assertSee('name="q" value="shirt"', false);

        $this->get('/search?q=shirt&size[]=M')
            ->assertOk()
            ->assertSee('Oxford Shirt');
    }

    /**
     * Sorting used to be hand-rolled per page and the shop's copy pointed at
     * route('home'), so choosing an ordering threw the shopper off the listing
     * and onto the front page.
     */
    public function test_sorting_stays_on_the_page_it_was_chosen_from(): void
    {
        foreach (['/shop', '/deals', '/bestsellers'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('action="'.url($url).'"', false);
        }
    }

    /** An applied filter is shown as a chip the shopper can click off again. */
    public function test_an_applied_filter_shows_as_a_removable_chip(): void
    {
        $this->get('/shop?colour[]=Black')
            ->assertOk()
            ->assertSee('Active Filters')
            ->assertSee('Clear all');
    }
}
