<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\FestivalSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\FestivalSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Marketing > Festival Sales.
 *
 * The load-bearing decision these tests pin is that a festival sale WRITES the
 * discounted figure into products.price rather than resolving it when a page
 * renders. That is what makes the new price reach the ~30 surfaces that print
 * one, and - more to the point - the four that read it in SQL rather than in
 * PHP: the price-range filter, the "On Sale" facet, the "Biggest Discount"
 * sort, and the home page's price bands.
 *
 * Everything that makes that safe rather than reckless is tested here: the
 * snapshot, the restore, the sizes (which carry their own prices and would
 * otherwise leave the product page quoting a figure the cart does not charge),
 * and the rule that an admin who reprices a product mid-sale keeps their edit.
 */
class FestivalSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function admin(): self
    {
        return $this->actingAs($this->adminUser, 'admin');
    }

    private function makeProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'kurtas'],
            ['name' => 'Kurtas', 'is_active' => true],
        );

        $name = $overrides['name'] ?? 'Kurta '.Str::random(6);

        return Product::create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'sku' => strtoupper(Str::random(8)),
            'category_id' => $category->id,
            'price' => 1000,
            'mrp' => 2000,
            'stock_quantity' => 10,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Diwali Festival Sale',
            'discount_percent' => '25',
            'is_active' => '1',
            'show_on_home' => '1',
            'product_ids' => '',
        ], $overrides);
    }

    public function test_a_live_sale_writes_the_discount_into_the_product_price(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload([
                'product_ids' => (string) $product->id,
            ]))
            ->assertRedirect();

        $product->refresh();

        // 25% off 1000. The mrp is untouched because it was already above the
        // price, so the badge the storefront derives goes from 50% to 62%.
        $this->assertEquals(750.00, (float) $product->price);
        $this->assertEquals(2000.00, (float) $product->mrp);
        $this->assertTrue($product->is_on_sale);
    }

    /**
     * The whole reason the price column is written rather than shadowed: a
     * shopper filtering by price, or sorting by discount, is filtering in SQL.
     */
    public function test_the_discounted_price_is_visible_to_a_sql_price_filter(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $this->assertEquals(
            0,
            Product::where('price', '<=', 800)->whereKey($product->id)->count(),
            'Before the sale this product is out of a ₹800 filter.'
        );

        $sale = FestivalSale::create([
            'name' => 'Holi Sale', 'slug' => 'holi-sale',
            'discount_percent' => 25, 'is_active' => true,
        ]);

        app(FestivalSaleService::class)->sync($sale, [$product->id]);

        $this->assertEquals(
            1,
            Product::where('price', '<=', 800)->whereKey($product->id)->count(),
            'A festival price must be reachable by the price-range filter, which reads products.price in SQL.'
        );
    }

    public function test_sizes_are_discounted_alongside_their_product(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        // A concrete price on the size, which is what Admin\ProductController
        // writes onto every variant row on save - and what the product page
        // reads instead of the product's own price.
        $sized = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'M', 'sku' => 'KRT-M',
            'price' => 1200, 'mrp' => 2400,
            'stock_quantity' => 5, 'is_active' => true,
        ]);

        // A size with no price of its own inherits the product's, so writing
        // one here would turn an inheriting size into an independent one.
        $inheriting = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'L', 'sku' => 'KRT-L',
            'price' => null, 'mrp' => null,
            'stock_quantity' => 5, 'is_active' => true,
        ]);

        $sale = FestivalSale::create([
            'name' => 'Diwali', 'slug' => 'diwali',
            'discount_percent' => 25, 'is_active' => true,
        ]);

        app(FestivalSaleService::class)->sync($sale, [$product->id]);

        $this->assertEquals(900.00, (float) $sized->fresh()->price, '1200 less 25%');
        $this->assertNull($inheriting->fresh()->price, 'An inheriting size must stay inheriting.');
    }

    public function test_ending_a_sale_puts_every_price_back(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'M', 'sku' => 'KRT-M2',
            'price' => 1200, 'mrp' => 2400,
            'stock_quantity' => 5, 'is_active' => true,
        ]);

        $sale = FestivalSale::create([
            'name' => 'Diwali', 'slug' => 'diwali-2',
            'discount_percent' => 25, 'is_active' => true,
        ]);

        app(FestivalSaleService::class)->sync($sale, [$product->id]);
        $this->assertEquals(750.00, (float) $product->fresh()->price);

        $this->admin()
            ->put(route('admin.festival-sales.toggle', $sale))
            ->assertRedirect();

        $this->assertEquals(1000.00, (float) $product->fresh()->price);
        $this->assertEquals(2000.00, (float) $product->fresh()->mrp);
        $this->assertEquals(1200.00, (float) $variant->fresh()->price);
    }

    /**
     * Every "% off" badge on the storefront is derived from mrp against price,
     * so a product selling at its own mrp has to end up with a strike-through
     * or the sale is invisible on its card.
     */
    public function test_a_product_selling_at_its_mrp_shows_the_festival_saving(): void
    {
        $product = $this->makeProduct(['price' => 800, 'mrp' => 800]);

        $this->assertEquals(0, $product->discount_percentage, 'No saving before the sale.');

        $sale = FestivalSale::create([
            'name' => 'Launch Offer', 'slug' => 'launch-offer',
            'discount_percent' => 20, 'is_active' => true,
        ]);

        app(FestivalSaleService::class)->sync($sale, [$product->id]);

        $product->refresh();
        $this->assertEquals(640.00, (float) $product->price);
        $this->assertEquals(800.00, (float) $product->mrp);
        $this->assertEquals(20, $product->discount_percentage);
        $this->assertTrue($product->is_on_sale);
    }

    /**
     * A product priced ABOVE its mrp is misconfigured, and discounting it would
     * still show no saving. The pre-sale price stands in as the strike-through
     * for the duration, and is taken back out again afterwards.
     */
    public function test_an_mrp_below_the_price_is_raised_for_the_sale_and_restored_after(): void
    {
        $product = $this->makeProduct(['price' => 800, 'mrp' => 500]);

        $sale = FestivalSale::create([
            'name' => 'Launch Offer', 'slug' => 'launch-offer-2',
            'discount_percent' => 20, 'is_active' => true,
        ]);

        $service = app(FestivalSaleService::class);
        $service->sync($sale, [$product->id]);

        $product->refresh();
        $this->assertEquals(640.00, (float) $product->price);
        $this->assertEquals(800.00, (float) $product->mrp, 'The pre-sale price becomes the strike-through.');

        $service->revertAll($sale);

        $product->refresh();
        $this->assertEquals(800.00, (float) $product->price);
        $this->assertEquals(500.00, (float) $product->mrp, 'The mrp we raised must not outlive the sale.');
    }

    /**
     * The rule that keeps this from being destructive: a price an admin has
     * changed since the sale started is theirs, and the sale ending is not a
     * reason to overwrite it.
     */
    public function test_an_admin_reprice_during_a_sale_survives_the_sale_ending(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $sale = FestivalSale::create([
            'name' => 'Diwali', 'slug' => 'diwali-3',
            'discount_percent' => 25, 'is_active' => true,
        ]);

        $service = app(FestivalSaleService::class);
        $service->sync($sale, [$product->id]);
        $this->assertEquals(750.00, (float) $product->fresh()->price);

        // The merchandiser marks it down further, by hand, mid-sale.
        $product->fresh()->forceFill(['price' => 500])->saveQuietly();

        $service->revertAll($sale);

        $this->assertEquals(
            500.00,
            (float) $product->fresh()->price,
            'Reverting must not clobber a price the sale did not set.'
        );
    }

    public function test_changing_the_percentage_reprices_from_the_original_not_the_discounted_price(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $sale = FestivalSale::create([
            'name' => 'Diwali', 'slug' => 'diwali-4',
            'discount_percent' => 25, 'is_active' => true,
        ]);

        $service = app(FestivalSaleService::class);
        $service->sync($sale, [$product->id]);
        $this->assertEquals(750.00, (float) $product->fresh()->price);

        $sale->update(['discount_percent' => 50]);
        $service->reconcile($sale);

        // 500, not 375: the second discount must come off the original price,
        // or every save would compound the sale against itself.
        $this->assertEquals(500.00, (float) $product->fresh()->price);
    }

    public function test_removing_a_product_from_the_sale_restores_only_that_product(): void
    {
        $kept = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);
        $dropped = $this->makeProduct(['price' => 600, 'mrp' => 1200]);

        $sale = FestivalSale::create([
            'name' => 'Diwali', 'slug' => 'diwali-5',
            'discount_percent' => 25, 'is_active' => true,
        ]);

        $service = app(FestivalSaleService::class);
        $service->sync($sale, [$kept->id, $dropped->id]);
        $service->sync($sale, [$kept->id]);

        $this->assertEquals(750.00, (float) $kept->fresh()->price);
        $this->assertEquals(600.00, (float) $dropped->fresh()->price);
        $this->assertDatabaseMissing('festival_sale_products', [
            'festival_sale_id' => $sale->id,
            'product_id' => $dropped->id,
        ]);
    }

    public function test_a_draft_sale_does_not_touch_any_price(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload([
                'name' => 'Not live yet',
                'is_active' => '0',
                'product_ids' => (string) $product->id,
            ]))
            ->assertRedirect();

        $this->assertEquals(1000.00, (float) $product->fresh()->price);
        $this->assertDatabaseHas('festival_sale_products', [
            'product_id' => $product->id,
            'applied_at' => null,
        ]);
    }

    public function test_a_product_cannot_be_discounted_by_two_live_sales_at_once(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);
        $service = app(FestivalSaleService::class);

        $first = FestivalSale::create([
            'name' => 'First', 'slug' => 'first', 'discount_percent' => 25, 'is_active' => true,
        ]);
        $service->sync($first, [$product->id]);

        $second = FestivalSale::create([
            'name' => 'Second', 'slug' => 'second', 'discount_percent' => 50, 'is_active' => true,
        ]);
        $report = $service->sync($second, [$product->id]);

        $this->assertEquals(750.00, (float) $product->fresh()->price, 'The first sale keeps it.');
        $this->assertArrayHasKey($product->id, $report['skipped']);
    }

    public function test_deleting_a_sale_restores_prices_before_the_rows_go(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $sale = FestivalSale::create([
            'name' => 'Diwali', 'slug' => 'diwali-6', 'discount_percent' => 25, 'is_active' => true,
        ]);
        app(FestivalSaleService::class)->sync($sale, [$product->id]);

        $this->admin()
            ->delete(route('admin.festival-sales.destroy', $sale))
            ->assertRedirect(route('admin.festival-sales.index'));

        $this->assertEquals(
            1000.00,
            (float) $product->fresh()->price,
            'The cascade would otherwise strand the product at its sale price with no record of the original.'
        );
    }

    public function test_the_percentage_is_validated(): void
    {
        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload(['discount_percent' => '0']))
            ->assertSessionHasErrors('discount_percent');

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload(['discount_percent' => '99']))
            ->assertSessionHasErrors('discount_percent');

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload(['discount_percent' => 'lots']))
            ->assertSessionHasErrors('discount_percent');
    }

    public function test_two_sales_cannot_share_a_name(): void
    {
        FestivalSale::create([
            'name' => 'Diwali Festival Sale', 'slug' => 'diwali-festival-sale',
            'discount_percent' => 10, 'is_active' => false,
        ]);

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload())
            ->assertSessionHasErrors('name');
    }

    public function test_the_banner_is_stored_and_the_sale_page_renders(): void
    {
        Storage::fake('public');

        $product = $this->makeProduct();

        $this->admin()->post(route('admin.festival-sales.store'), $this->payload([
            'product_ids' => (string) $product->id,
            'banner' => UploadedFile::fake()->image('diwali.jpg', 1600, 500),
        ]))->assertRedirect();

        $sale = FestivalSale::firstOrFail();
        $this->assertNotNull($sale->banner_path);
        Storage::disk('public')->assertExists($sale->banner_path);

        $this->get(route('festival-sale.show', $sale))
            ->assertOk()
            ->assertSee($sale->name, false);
    }

    public function test_an_inactive_sale_has_no_public_page(): void
    {
        $sale = FestivalSale::create([
            'name' => 'Hidden', 'slug' => 'hidden', 'discount_percent' => 10, 'is_active' => false,
        ]);

        $this->get(route('festival-sale.show', $sale))->assertNotFound();
    }

    /**
     * whereIn with an empty array matches every row, which would put the entire
     * catalogue behind a sale banner.
     */
    public function test_an_empty_sale_lists_nothing_rather_than_everything(): void
    {
        $this->makeProduct();
        $this->makeProduct();

        $sale = FestivalSale::create([
            'name' => 'Empty', 'slug' => 'empty', 'discount_percent' => 10, 'is_active' => true,
        ]);

        $this->get(route('festival-sale.show', $sale))
            ->assertOk()
            ->assertSee('0 products in this sale');
    }

    /**
     * The festival link sits BESIDE "Introductory Offer", never in place of it.
     * They are two different promotions, and a shopper who came looking for the
     * introductory pricing should not find the festival wearing its name.
     */
    public function test_the_header_shows_the_festival_link_beside_the_introductory_offer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Introductory Offer')
            ->assertDontSee('Diwali Dhamaka');

        $sale = FestivalSale::create([
            'name' => 'Diwali Dhamaka', 'slug' => 'diwali-dhamaka',
            'discount_percent' => 30, 'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Introductory Offer')
            ->assertSee(route('deals'), false)
            ->assertSee(route('festival-sale.show', $sale), false)
            ->assertSee('Diwali Dhamaka');
    }

    /**
     * The Sale menu lists EVERY live sale, not the most recently saved one.
     *
     * Two sales can run at once - the service refuses to let two of them own
     * the same product, not to let two of them exist - and the header used to
     * point at whichever was touched last, so the other one was reachable only
     * by typing its URL.
     */
    public function test_the_sale_menu_lists_every_live_sale(): void
    {
        $diwali = FestivalSale::create([
            'name' => 'Diwali Dhamaka', 'slug' => 'diwali-dhamaka',
            'discount_percent' => 30, 'is_active' => true,
        ]);

        $clearance = FestivalSale::create([
            'name' => 'Monsoon Clearance', 'slug' => 'monsoon-clearance',
            'discount_percent' => 15, 'is_active' => true,
        ]);

        $draft = FestivalSale::create([
            'name' => 'Holi Preview', 'slug' => 'holi-preview',
            'discount_percent' => 10, 'is_active' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Diwali Dhamaka')
            ->assertSee(route('festival-sale.show', $diwali), false)
            ->assertSee('Monsoon Clearance')
            ->assertSee(route('festival-sale.show', $clearance), false)
            // A sale an admin has not switched on has no page to open, so it
            // must not be in the menu.
            ->assertDontSee('Holi Preview')
            ->assertDontSee(route('festival-sale.show', $draft), false);
    }

    /** No live sale, no Sale menu - the nav is never a way into an empty page. */
    public function test_the_sale_menu_is_absent_when_nothing_is_running(): void
    {
        FestivalSale::create([
            'name' => 'Ended Sale', 'slug' => 'ended-sale',
            'discount_percent' => 20, 'is_active' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Introductory Offer')
            ->assertDontSee('Ended Sale')
            // Not the pill's CSS class - that lives in a <style> block the
            // header always emits. The absence that matters is that no
            // festival-sale URL is anywhere on the page.
            ->assertDontSee(url('/festival-sale'), false);
    }

    /**
     * Every live sale's artwork reaches the hero, not just the newest one's.
     */
    public function test_the_hero_carries_a_slide_for_each_live_sale(): void
    {
        Storage::fake('public');

        $this->admin()->post(route('admin.festival-sales.store'), $this->payload([
            'name' => 'Diwali Hero Sale',
            'banner' => UploadedFile::fake()->image('diwali-hero.jpg', 1600, 500),
        ]))->assertRedirect();

        $this->admin()->post(route('admin.festival-sales.store'), $this->payload([
            'name' => 'Clearance Hero Sale',
            'banner' => UploadedFile::fake()->image('clearance-hero.jpg', 1600, 500),
        ]))->assertRedirect();

        $sales = FestivalSale::orderBy('id')->get();
        $this->assertCount(2, $sales);

        $page = $this->get('/')->assertOk();

        foreach ($sales as $sale) {
            $page->assertSee($sale->bannerUrl(), false)
                ->assertSee(route('festival-sale.show', $sale), false);
        }
    }

    /**
     * The banner leads the hero carousel rather than sitting in a strip below
     * it, so a festival is the first thing the page shows.
     */
    public function test_the_banner_leads_the_hero_on_the_home_page(): void
    {
        Storage::fake('public');

        $product = $this->makeProduct();

        $this->admin()->post(route('admin.festival-sales.store'), $this->payload([
            'name' => 'Diwali Hero Sale',
            'product_ids' => (string) $product->id,
            'banner' => UploadedFile::fake()->image('diwali-hero.jpg', 1600, 500),
        ]))->assertRedirect();

        $sale = FestivalSale::firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertSee('kk-hero-slide', false)
            ->assertSee($sale->bannerUrl(), false)
            ->assertSee(route('festival-sale.show', $sale), false);
    }

    /** With show_on_home off, the sale runs but the hero does not carry it. */
    public function test_the_hero_banner_respects_the_show_on_home_switch(): void
    {
        Storage::fake('public');

        $this->admin()->post(route('admin.festival-sales.store'), $this->payload([
            'name' => 'Quiet Sale',
            'show_on_home' => '0',
            'banner' => UploadedFile::fake()->image('quiet.jpg', 1600, 500),
        ]))->assertRedirect();

        $sale = FestivalSale::firstOrFail();

        $this->get('/')->assertOk()->assertDontSee($sale->bannerUrl(), false);
    }

    /**
     * The selection must post as ONE field, never one input per product.
     *
     * PHP's max_input_vars is 1000 and a sale can hold the whole catalogue. With
     * a `products[]` input per tick, a 995-product sale posted ~1010 fields and
     * PHP silently dropped the tail - which is the Live switch, "Show banner on
     * home page" and the banner upload, all of which sit after the picker in the
     * DOM. The symptom was a checkbox that would not stay ticked; the cause was
     * that the field never arrived at all, and $request->boolean() reads a
     * missing key as false.
     *
     * PHPUnit does not go through PHP's input parser, so the truncation itself
     * cannot be reproduced here - but the thing that caused it can be, and this
     * is what stops it coming back.
     */
    public function test_the_selection_posts_as_a_single_field(): void
    {
        $this->makeProduct();
        $this->makeProduct();

        $html = $this->admin()
            ->get(route('admin.festival-sales.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            'name="products[]"',
            $html,
            'One input per product overruns max_input_vars and silently drops every field after the picker.'
        );
        $this->assertSame(
            1,
            substr_count($html, 'name="product_ids"'),
            'The whole selection should post as exactly one field.'
        );
    }

    /** A big selection has to survive the round trip, ids and switches alike. */
    public function test_a_large_selection_round_trips_with_the_switches_intact(): void
    {
        $ids = collect(range(1, 40))->map(fn () => $this->makeProduct()->id);

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload([
                'name' => 'Big Selection',
                'product_ids' => $ids->implode(','),
            ]))
            ->assertRedirect();

        $sale = FestivalSale::firstOrFail();

        $this->assertTrue($sale->is_active, 'The Live switch must survive a big selection.');
        $this->assertTrue($sale->show_on_home, 'Show-on-home must survive a big selection.');
        $this->assertSame(40, $sale->products()->count());
    }

    /** Junk and unknown ids are dropped, not fatal - and never double-counted. */
    public function test_unknown_and_duplicate_ids_are_ignored(): void
    {
        $product = $this->makeProduct();

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload([
                'product_ids' => $product->id.',,'.$product->id.',99999999,abc',
            ]))
            ->assertRedirect();

        $this->assertSame(1, FestivalSale::firstOrFail()->products()->count());
    }

    /**
     * The picker's category filter. Products carry their whole ancestor chain,
     * so picking a parent category matches everything filed under its children.
     */
    public function test_the_product_picker_offers_a_category_filter(): void
    {
        $parent = Category::firstOrCreate(['slug' => 'menswear'], ['name' => 'Menswear', 'is_active' => true]);
        $child = Category::firstOrCreate(
            ['slug' => 'mens-kurtas'],
            ['name' => 'Mens Kurtas', 'is_active' => true, 'parent_id' => $parent->id],
        );

        $product = $this->makeProduct(['name' => 'Chain Test Kurta', 'category_id' => $child->id]);

        $html = $this->admin()
            ->get(route('admin.festival-sales.create'))
            ->assertOk()
            ->assertSee('All categories')
            ->assertSee('Menswear')
            ->assertSee('Mens Kurtas')
            ->getContent();

        // The tile must carry BOTH the child and the parent, or filtering by
        // the parent would miss it.
        $this->assertMatchesRegularExpression(
            '/\\\\u0022id\\\\u0022:'.$product->id.',.{0,400}?\\\\u0022cats\\\\u0022:\[[^\]]*'.$parent->id.'[^\]]*\]/s',
            $html,
            'The picker row should carry the product\'s full category ancestor chain.'
        );
    }
}
