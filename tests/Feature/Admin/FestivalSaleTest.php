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
            'products' => [],
        ], $overrides);
    }

    public function test_a_live_sale_writes_the_discount_into_the_product_price(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'mrp' => 2000]);

        $this->admin()
            ->post(route('admin.festival-sales.store'), $this->payload([
                'products' => [$product->id],
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
                'products' => [$product->id],
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
            'products' => [$product->id],
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

    public function test_the_header_points_at_the_live_sale(): void
    {
        $this->get('/')->assertOk()->assertSee('Introductory Offer');

        $sale = FestivalSale::create([
            'name' => 'Diwali Dhamaka', 'slug' => 'diwali-dhamaka',
            'discount_percent' => 30, 'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee(route('festival-sale.show', $sale), false)
            ->assertSee('Diwali Dhamaka');
    }
}
