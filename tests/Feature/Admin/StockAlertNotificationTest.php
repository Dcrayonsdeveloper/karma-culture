<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StockAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin bell hears about a shelf going low, or going empty.
 *
 * The shop had no in-app signal for either: stock:check-low mails a digest at
 * 08:00 and nothing ever reached the bell, so a size that sold out at lunchtime
 * stayed sold out until somebody opened the inventory page.
 *
 * The load-bearing decision these tests pin is that the alert is a TRANSITION,
 * not a state. Production carried 326 products and 7,056 sizes already under
 * their threshold when this was written; announcing the current state would
 * have written tens of thousands of rows across four admins the first time
 * anything ran. So a shelf that was already low says nothing when it drops
 * again - and says something again only once it has been refilled.
 */
class StockAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function makeProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'shirts'],
            ['name' => 'Shirts', 'is_active' => true],
        );

        $name = $overrides['name'] ?? 'Kurti '.Str::random(6);

        return Product::create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'sku' => strtoupper(Str::random(8)),
            'category_id' => $category->id,
            'price' => 500,
            'mrp' => 900,
            'stock_quantity' => 100,
            'low_stock_threshold' => 10,
            'is_active' => true,
        ], $overrides));
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Notification> */
    private function alerts(): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::query()
            ->forAdmin()
            ->whereIn('type', ['product_low_stock', 'product_out_of_stock'])
            ->get();
    }

    public function test_crossing_into_low_stock_notifies_the_admin_bell(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);

        $product->update(['stock_quantity' => 8]);

        $alert = $this->alerts()->sole();

        $this->assertSame('product_low_stock', $alert->type);
        $this->assertSame(Notification::AUDIENCE_ADMIN, $alert->audience);
        $this->assertSame($this->admin->id, $alert->user_id);
        $this->assertSame($product->id, $alert->data['product_id']);
        $this->assertStringContainsString($product->name, $alert->content);
        $this->assertStringContainsString('8 left', $alert->content);
    }

    public function test_selling_out_notifies_as_out_of_stock_not_low_stock(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);

        $product->update(['stock_quantity' => 0]);

        $alert = $this->alerts()->sole();

        $this->assertSame('product_out_of_stock', $alert->type);
        $this->assertStringContainsString('out of stock', $alert->content);
    }

    /**
     * The decrement() path, which is how both checkouts take stock down. It
     * fires `updated` without firing `saved`, so a hook on the tidier-looking
     * event would miss every sale in the shop.
     */
    public function test_a_sale_taken_with_decrement_still_notifies(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 12]);

        $product->decrement('stock_quantity', 5);

        $this->assertSame('product_low_stock', $this->alerts()->sole()->type);
    }

    public function test_a_shelf_that_was_already_low_says_nothing_when_it_drops_again(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);

        $product->update(['stock_quantity' => 9]);   // crosses in - one alert
        $product->update(['stock_quantity' => 7]);   // still low - silent
        $product->update(['stock_quantity' => 2]);   // still low - silent

        $this->assertCount(1, $this->alerts());
    }

    public function test_going_low_then_empty_reports_both_crossings(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);

        $product->update(['stock_quantity' => 5]);
        $product->update(['stock_quantity' => 0]);

        $this->assertSame(
            ['product_low_stock', 'product_out_of_stock'],
            $this->alerts()->sortBy('id')->pluck('type')->all(),
        );
    }

    public function test_a_restock_re_arms_the_alert(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);

        $product->update(['stock_quantity' => 4]);    // alert
        $product->update(['stock_quantity' => 100]);  // back to healthy - silent
        $product->update(['stock_quantity' => 4]);    // alert again

        $this->assertCount(2, $this->alerts());
    }

    public function test_a_restock_on_its_own_notifies_nobody(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 3]);

        $product->update(['stock_quantity' => 250]);

        $this->assertCount(0, $this->alerts());
    }

    public function test_a_size_running_down_names_its_product_and_its_size(): void
    {
        $product = $this->makeProduct(['name' => 'Block Print Kurti', 'stock_quantity' => 100]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'M',
            'sku' => strtoupper(Str::random(8)),
            'stock_quantity' => 40,
            'is_active' => true,
        ]);

        $variant->update(['stock_quantity' => 3]);

        $alert = $this->alerts()->sole();

        $this->assertSame('product_low_stock', $alert->type);
        $this->assertStringContainsString('Block Print Kurti (M)', $alert->content);
        // Sizes have no page of their own, so the bell opens the product form.
        $this->assertSame($product->id, $alert->data['product_id']);
        $this->assertSame($variant->id, $alert->data['variant_id']);
    }

    /**
     * A size is judged by its product's threshold, because product_variants
     * carries none of its own.
     */
    public function test_a_size_is_measured_against_its_products_threshold(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100, 'low_stock_threshold' => 25]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'L',
            'sku' => strtoupper(Str::random(8)),
            'stock_quantity' => 80,
            'is_active' => true,
        ]);

        $variant->update(['stock_quantity' => 20]);

        $this->assertStringContainsString('threshold 25', $this->alerts()->sole()->content);
    }

    /**
     * A threshold of 0 is how the admin product list already says "never warn
     * me about this one". Only an empty shelf is worth reporting for those.
     */
    public function test_a_zero_threshold_only_ever_reports_an_empty_shelf(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100, 'low_stock_threshold' => 0]);

        $product->update(['stock_quantity' => 1]);
        $this->assertCount(0, $this->alerts());

        $product->update(['stock_quantity' => 0]);
        $this->assertSame('product_out_of_stock', $this->alerts()->sole()->type);
    }

    public function test_a_muted_bulk_run_announces_nothing(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);

        StockAlertService::muted(function () use ($product) {
            $product->update(['stock_quantity' => 0]);
        });

        $this->assertCount(0, $this->alerts());
        $this->assertFalse(StockAlertService::$muted, 'the mute must be lifted afterwards');
    }

    public function test_the_mute_is_lifted_even_when_the_bulk_run_throws(): void
    {
        try {
            StockAlertService::muted(fn () => throw new \RuntimeException('import blew up'));
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertFalse(StockAlertService::$muted);
    }

    /**
     * Every admin gets their own row: read-scoping in this app is per-admin, so
     * one shared row would let the first admin to look clear it for everyone.
     */
    public function test_every_admin_gets_their_own_row(): void
    {
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'customer']);

        $this->makeProduct(['stock_quantity' => 100])->update(['stock_quantity' => 0]);

        $alerts = $this->alerts();

        $this->assertCount(2, $alerts);
        $this->assertEqualsCanonicalizing(
            User::where('role', 'admin')->pluck('id')->all(),
            $alerts->pluck('user_id')->all(),
        );
    }

    /**
     * A stock alert must never land on a shopper's own notifications page. One
     * table serves both bells and an admin can also shop, so audience is the
     * only thing keeping them apart.
     */
    public function test_no_stock_alert_reaches_the_customer_bell(): void
    {
        $this->makeProduct(['stock_quantity' => 100])->update(['stock_quantity' => 0]);

        $this->assertSame(0, Notification::query()->forCustomer()->count());
    }

    /**
     * The Excel importer creates every new row is_active => false and then
     * writes its stock, and the daily low-stock email has always filtered the
     * same way. An alert about a product no shopper can reach is noise.
     */
    public function test_a_draft_product_is_not_worth_a_bell(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100, 'is_active' => false]);

        $product->update(['stock_quantity' => 0]);

        $this->assertCount(0, $this->alerts());
    }

    public function test_a_product_in_the_bin_is_not_worth_a_bell(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);
        $product->delete();

        $product->update(['stock_quantity' => 0]);

        $this->assertCount(0, $this->alerts());
    }

    public function test_a_switched_off_size_is_not_worth_a_bell(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 100]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'XS',
            'sku' => strtoupper(Str::random(8)),
            'stock_quantity' => 40,
            'is_active' => false,
        ]);

        $variant->update(['stock_quantity' => 0]);

        $this->assertCount(0, $this->alerts());
    }

    public function test_a_change_that_is_not_stock_notifies_nobody(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 2]);

        $product->update(['name' => 'Renamed while already low']);

        $this->assertCount(0, $this->alerts());
    }
}
