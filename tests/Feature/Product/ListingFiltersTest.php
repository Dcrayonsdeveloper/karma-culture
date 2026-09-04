<?php

namespace Tests\Feature\Product;

use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductFilters;
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
            'shop' => ['/products'],
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
        $response = $this->get('/products?max_price=1000')->assertOk();

        $this->assertSame(
            ['Oxford Shirt'],
            $response->viewData('products')->pluck('name')->all()
        );
    }

    /**
     * The two price boxes filled in the wrong order are named as a mistake, not
     * quietly repaired.
     *
     * Min 1000 with Max 0 asks for `price >= 1000 AND price <= 0`, a range
     * nothing can be in, so the shop answered "0 products found" and said
     * nothing about why. Swapping the pair was tried and is worse: it hands back
     * results for ₹0-₹1,000, an answer to a question nobody asked. The numbers
     * stay as typed and the sidebar says which one to fix.
     */
    public function test_a_backwards_price_range_is_reported_not_swapped(): void
    {
        $response = $this->get('/products?min_price=1000&max_price=0')->assertOk();

        $values = $response->viewData('filterPanel')['values'];

        $this->assertSame(1000.0, $values['min_price'], 'the typed minimum was not kept');
        $this->assertSame(0.0, $values['max_price'], 'the typed maximum was not kept');
        $this->assertSame(ProductFilters::PRICE_ORDER_ERROR, $values['price_error']);

        $response->assertSee(self::renderedPriceError(), false);
    }

    /**
     * The message as the sidebar renders it, closing tag and all.
     *
     * The bare string is on every listing whatever the filters say - the Alpine
     * handler that repeats the check as the shopper types carries the same
     * wording - so asserting on it alone can neither prove it was rendered nor
     * prove it was not.
     */
    private static function renderedPriceError(): string
    {
        return '>'.ProductFilters::PRICE_ORDER_ERROR.'</p>';
    }

    /**
     * The message reaches the page without JS, and the boxes still hold the
     * numbers the shopper typed so they can correct the one they meant.
     */
    public function test_a_backwards_price_range_keeps_the_boxes_as_typed(): void
    {
        $this->get('/products?min_price=1000&max_price=0')
            ->assertOk()
            ->assertSee('name="min_price" value="1000"', false)
            ->assertSee('name="max_price" value="0"', false);
    }

    /**
     * A hanger an admin typed backwards is the same range arriving under a name
     * the form never uses, so it is reported at the same place rather than in
     * the two boxes.
     */
    public function test_a_backwards_hanger_price_range_is_reported(): void
    {
        $response = $this->get('/products?price_min=2000&price_max=1000')->assertOk();

        $this->assertSame(
            ProductFilters::PRICE_ORDER_ERROR,
            $response->viewData('filterPanel')['values']['price_error']
        );
    }

    /** A range the right way round says nothing, and filters as it always did. */
    public function test_a_valid_price_range_reports_nothing(): void
    {
        $response = $this->get('/products?min_price=0&max_price=1000')->assertOk();

        $this->assertNull($response->viewData('filterPanel')['values']['price_error']);
        $this->assertSame(
            ['Oxford Shirt'],
            $response->viewData('products')->pluck('name')->all()
        );
        $response->assertDontSee(self::renderedPriceError(), false);
    }

    /** An exact-price range is a real filter, not a mistake, so it stands. */
    public function test_an_equal_price_range_is_left_alone(): void
    {
        $response = $this->get('/products?min_price=799&max_price=799')->assertOk();

        $this->assertNull($response->viewData('filterPanel')['values']['price_error']);
        $this->assertSame(
            ['Oxford Shirt'],
            $response->viewData('products')->pluck('name')->all()
        );
    }

    /** The deals page has no On Sale box, because everything on it is on sale. */
    public function test_deals_hides_the_redundant_on_sale_box(): void
    {
        $this->get('/deals')->assertOk()->assertDontSee('name="on_sale"', false);
        $this->get('/products')->assertOk()->assertSee('name="on_sale"', false);
    }

    /** The brand page has no Brand facet, because there is only ever one. */
    public function test_brand_page_hides_the_redundant_brand_facet(): void
    {
        $this->get('/brands/biba')->assertOk()->assertDontSee('name="brand[]"', false);
        $this->get('/products')->assertOk()->assertSee('name="brand[]" value="biba"', false);
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
        foreach (['/products', '/deals', '/bestsellers'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('action="'.url($url).'"', false);
        }
    }

    /** An applied filter is shown as a chip the shopper can click off again. */
    public function test_an_applied_filter_shows_as_a_removable_chip(): void
    {
        $this->get('/products?colour[]=Black')
            ->assertOk()
            ->assertSee('Active Filters')
            ->assertSee('Clear all');
    }

    /**
     * The rating rows are a floor, not an exact score, and have to say so.
     *
     * The labels were bare numerals - "4" plus one star - while the query behind
     * them is `rating >= 4`, so the control claimed to mean "rated four" and
     * quietly returned everything above it too.
     */
    public function test_rating_rows_say_they_are_a_floor_and_can_be_cleared(): void
    {
        $response = $this->get('/products')->assertOk();

        $response->assertSee('name="rating" value="5"', false)
            ->assertSee('name="rating" value="1"', false)
            ->assertSee('&amp; up', false)
            ->assertSee('4 stars and up', false);

        // The empty option is the only thing in the sidebar that can take a
        // chosen rating back off; a radio group without one can be set but
        // never unset.
        $response->assertSee('name="rating" value=""', false)
            ->assertSee('Any rating');
    }

    /** And the floor is what the query actually applies. */
    public function test_the_rating_filter_keeps_everything_at_or_above_the_chosen_star(): void
    {
        // Both fixture products are rated 4.
        $this->assertSame(2, $this->get('/products?rating=4')->assertOk()->viewData('products')->total());
        $this->assertSame(2, $this->get('/products?rating=1')->assertOk()->viewData('products')->total());
        $this->assertSame(0, $this->get('/products?rating=5')->assertOk()->viewData('products')->total());
    }

    /**
     * products.rating only moves when a review is approved, so on a catalogue
     * with no reviews yet every one of the five options returned an empty grid -
     * the section was five ways to break the page.
     */
    public function test_the_rating_section_is_absent_when_nothing_is_rated(): void
    {
        Product::query()->update(['rating' => 0]);

        // Asserted on the control, not the word: "Best Rating" is also an
        // option in the sort dropdown and would match either way.
        $this->get('/products')->assertOk()->assertDontSee('name="rating"', false);
    }

    /**
     * A page with a default_sort of its own (deals, bestsellers, new arrivals)
     * carries no ?sort when the shopper picks the ordering that page opens on.
     * The sidebar only emitted a hidden sort input when the value was not
     * 'newest', so applying a filter on /deals threw an explicit "Newest" away
     * and handed back discount order.
     */
    public function test_a_chosen_sort_survives_a_filter_submit(): void
    {
        foreach (['/deals', '/bestsellers', '/new-arrivals'] as $url) {
            $this->get($url.'?sort=newest')
                ->assertOk()
                ->assertSee('name="sort" value="newest"', false);
        }
    }

    /**
     * A "Shop It Your Way" hanger links to /products?shade=Indigo, and the listing
     * merges `shade` into `colour` before the panel is built. The chip stripped
     * only `colour`, so the URL it handed back still said `shade=` and the next
     * request re-derived the very filter the shopper had just removed.
     */
    public function test_a_hanger_filter_can_be_taken_off_again(): void
    {
        $this->get('/products?shade=Black')
            ->assertOk()
            ->assertSee('Active Filters')
            ->assertDontSee('shade=Black', false);

        $this->get('/products?price_min=100&price_max=2000')
            ->assertOk()
            ->assertSee('Active Filters')
            ->assertDontSee('price_min=100', false);
    }

    /**
     * The category page applies its own bound and passes owns_category => false,
     * so ?category= never reaches its query. The chip drew anyway, offering to
     * remove a filter that was already doing nothing.
     */
    public function test_no_category_chip_where_the_page_does_not_own_the_facet(): void
    {
        $this->get('/category/men?category=men')
            ->assertOk()
            ->assertDontSee('Active Filters');

        // On the shop, which does own it, the same parameter is a real filter.
        $this->get('/products?category=men')
            ->assertOk()
            ->assertSee('Active Filters');
    }

    /**
     * The Filters button in the header reaches every page, including the ones
     * with no listing behind them - before this, a shopper away from a listing
     * had no filter control anywhere, and the listing was not linked from the header,
     * the mobile drawer or the footer.
     */
    public function test_every_page_carries_the_header_filters_button(): void
    {
        foreach (['/products', '/wishlist', '/brands'] as $url) {
            $this->get($url)->assertOk()->assertSee('open-global-filters', false);
        }

        // A listing answers the button with its own sidebar, so the filters
        // apply to the grid on screen; anywhere else the header's shop-wide
        // drawer takes it. This marker is how the drawer tells them apart.
        // Asserted on the sidebar's own Alpine state, not on the marker
        // attribute: the drawer's querySelector mentions that attribute by name,
        // so the literal string is on every page whether a sidebar is there or
        // not. `mobileOpen` belongs to the sidebar alone.
        $this->get('/products')->assertOk()
            ->assertSee('data-kk-filter-sidebar', false)
            ->assertSee('mobileOpen', false);
        $this->get('/wishlist')->assertOk()->assertDontSee('mobileOpen', false);
    }

    /**
     * The collection a sub-category page IS gets a settled row, not a checkbox
     * that swallows clicks.
     *
     * The page ticks its own slug when the request carries no subcategory, and
     * re-ticks it on every submit - so unticking the box reloaded the same page
     * with the box ticked again, and the one control in the sidebar that looked
     * most obviously undoable was the one that could not be undone.
     */
    public function test_the_pages_own_subcategory_is_settled_rather_than_a_dead_checkbox(): void
    {
        $this->get('/category/shirts')
            ->assertOk()
            ->assertSee('You are browsing this collection', false);

        // On the parent the very same box is a live filter again.
        $this->get('/category/men')
            ->assertOk()
            ->assertSee('name="subcategory[]" value="shirts"', false)
            ->assertDontSee('You are browsing this collection', false);
    }

    /** And /products is reachable from the navigation rather than only by accident. */
    public function test_the_shop_is_linked_from_the_navigation(): void
    {
        $this->get('/wishlist')->assertOk()->assertSee('href="'.route('shop').'"', false);
    }

    /** The drawer fetches the panel on first open rather than on every page load. */
    public function test_the_filter_panel_endpoint_returns_a_usable_form(): void
    {
        $this->get('/products/filters')
            ->assertOk()
            ->assertSee('action="'.route('shop').'"', false)
            ->assertSee('name="size[]" value="M"', false)
            ->assertSee('name="rating"', false);

        // Filters already in the URL are reflected, so opening the drawer after
        // arriving from a hanger shows that hanger's picks ticked.
        $this->get('/products/filters?shade=Black')
            ->assertOk()
            ->assertSee('name="colour[]" value="Black"', false);
    }
}
