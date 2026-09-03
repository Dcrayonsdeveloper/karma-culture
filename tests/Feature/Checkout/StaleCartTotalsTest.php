<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The checkout page quoted whatever was last written to the cart row.
 *
 * CheckoutController@index loads the cart and renders it without calling
 * recalculate(), so the summary shows the stored subtotal, shipping and tax.
 * Every cart that existed before delivery charging was wired up carries
 * shipping = 0, and every cart that was last touched before an admin changed
 * the shipping or tax settings carries the old numbers - so the customer was
 * shown FREE on an order that should have been charged, and the settings
 * looked broken when they were not.
 *
 * The cart page has always recalculated on load. This is the same rule on the
 * screen where it matters most: the last one that quotes a total.
 */
class StaleCartTotalsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Linen Shirt', 'slug' => 'linen-shirt', 'description' => 'x',
            'sku' => 'STALE-1', 'price' => 100, 'mrp' => 100, 'stock_quantity' => 50,
            'category_id' => $category->id, 'is_active' => true, 'status' => 'approved',
            'is_taxable' => false,
        ]);
    }

    private function setting(string $key, string $value, string $group = 'shipping'): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget("setting.{$key}");
    }

    /** A cart saved before the settings existed: totals stored, shipping zero. */
    private function staleCart(int $quantity = 2): Cart
    {
        $cart = Cart::create(['user_id' => $this->user->id]);

        $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'price' => $this->product->price,
            'total' => $this->product->price * $quantity,
        ]);

        $cart->refresh()->recalculate();

        // Now the admin switches delivery charging on. The cart is not touched
        // again - the customer already had it open.
        $this->setting('flat_rate_enabled', '1');
        $this->setting('flat_rate_amount', '100');
        $this->setting('free_shipping_enabled', '1');
        $this->setting('free_shipping_threshold', '400');

        return $cart->refresh();
    }

    public function test_the_stored_cart_really_is_stale(): void
    {
        $cart = $this->staleCart();

        $this->assertSame('0.00', (string) $cart->shipping, 'The fixture is not reproducing the problem.');
    }

    public function test_the_checkout_page_charges_anyway(): void
    {
        $this->staleCart();   // 200, under the 400 minimum

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/Shipping.*?100\.00/s',
            $html,
            'The checkout is quoting a stale FREE from before the settings changed.'
        );
    }

    public function test_the_cart_row_is_brought_up_to_date_by_the_visit(): void
    {
        $cart = $this->staleCart();

        $this->actingAs($this->user)->get(route('checkout.index'))->assertOk();

        $this->assertSame('100.00', (string) $cart->fresh()->shipping);
        $this->assertSame('300.00', (string) $cart->fresh()->total);
    }

    /**
     * Tax has the same failure mode: switched on after the cart was built.
     */
    public function test_tax_switched_on_afterwards_is_picked_up_too(): void
    {
        $this->product->update(['is_taxable' => true, 'tax_rate' => 18]);

        $cart = Cart::create(['user_id' => $this->user->id]);
        $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100,
            'total' => 200,
        ]);
        $cart->refresh()->recalculate();

        $this->assertSame('0.00', (string) $cart->fresh()->tax, 'Tax is off, so the fixture starts at zero.');

        $this->setting('tax_enabled', '1', 'tax');

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/Tax.*?36\.00/s', $html);
    }

    /**
     * Recalculating on load must not disturb what is already correct.
     */
    public function test_a_free_order_is_still_free_after_the_visit(): void
    {
        $this->staleCart(quantity: 5);   // 500, over the minimum

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('FREE', $html);
    }
}
