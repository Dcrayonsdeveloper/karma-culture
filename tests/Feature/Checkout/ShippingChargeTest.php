<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\ShippingCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shop could not charge for delivery.
 *
 * Settings -> Shipping has had a flat rate and a free-shipping threshold for as
 * long as the screen has existed and nothing read either. Cart::recalculate()
 * only ever READ `shipping`, never wrote it, so it stayed at the column default
 * of 0; the checkout summary printed the word FREE unconditionally; and the
 * order was created with shipping_cost => 0 and a total of subtotal - discount.
 *
 * So an admin could set a charge and a minimum order value, watch the checkout
 * say FREE, and be paid nothing for delivery.
 */
class ShippingChargeTest extends TestCase
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
            'sku' => 'SHIP-1', 'price' => 100, 'mrp' => 100, 'stock_quantity' => 50,
            'category_id' => $category->id, 'is_active' => true, 'status' => 'approved',
            // Not taxable: tax is its own line with its own rules, and leaving
            // it in made these totals a test of two things at once.
            'is_taxable' => false,
        ]);
    }

    private function setting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'shipping']);
        \Illuminate\Support\Facades\Cache::forget("setting.{$key}");
    }

    private function chargingShop(float $flat = 50, float $threshold = 400): void
    {
        $this->setting('flat_rate_enabled', '1');
        $this->setting('flat_rate_amount', (string) $flat);
        $this->setting('free_shipping_enabled', '1');
        $this->setting('free_shipping_threshold', (string) $threshold);
    }

    private function cartWith(int $quantity): Cart
    {
        $cart = Cart::create(['user_id' => $this->user->id]);

        $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'price' => $this->product->price,
            'total' => $this->product->price * $quantity,
        ]);

        $cart->refresh()->recalculate();

        return $cart->refresh();
    }

    public function test_an_order_under_the_minimum_is_charged(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);

        $cart = $this->cartWith(2);   // 200

        $this->assertSame('50.00', (string) $cart->shipping);
        $this->assertSame('250.00', (string) $cart->total, 'The charge is not in the total.');
    }

    public function test_an_order_over_the_minimum_is_free(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);

        $cart = $this->cartWith(5);   // 500

        $this->assertSame('0.00', (string) $cart->shipping);
        $this->assertSame('500.00', (string) $cart->total);
    }

    /**
     * The boundary is "at or above" - an order exactly on the minimum earns the
     * free delivery it was told about.
     */
    public function test_an_order_exactly_on_the_minimum_is_free(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);

        $cart = $this->cartWith(4);   // 400

        $this->assertSame('0.00', (string) $cart->shipping);
    }

    /**
     * A shop that has never touched the shipping screen keeps free delivery -
     * so this change bills nobody until someone switches it on.
     */
    public function test_a_shop_with_nothing_configured_still_ships_free(): void
    {
        $cart = $this->cartWith(2);

        $this->assertSame('0.00', (string) $cart->shipping);
    }

    public function test_a_flat_rate_switched_off_is_not_charged(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->setting('flat_rate_enabled', '0');

        $this->assertSame('0.00', (string) $this->cartWith(2)->shipping);
    }

    /**
     * Free shipping ticked with no minimum means free, not "charge everyone".
     */
    public function test_free_shipping_with_no_threshold_is_free_for_all(): void
    {
        $this->chargingShop(flat: 50, threshold: 0);

        $this->assertSame('0.00', (string) $this->cartWith(1)->shipping);
    }

    /**
     * Without the free-shipping switch there is no minimum to clear, so the
     * flat rate applies to every order however large.
     */
    public function test_a_large_order_still_pays_when_free_shipping_is_off(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->setting('free_shipping_enabled', '0');

        $this->assertSame('50.00', (string) $this->cartWith(10)->shipping);
    }

    /**
     * The threshold is measured on what the customer actually pays. A basket
     * discounted below the minimum has not earned free delivery.
     */
    public function test_the_minimum_is_measured_after_the_discount(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);

        $cart = $this->cartWith(5);          // 500, free
        $this->assertSame('0.00', (string) $cart->shipping);

        $cart->update(['discount' => 150]);  // 350 payable
        $cart->refresh();

        $this->assertFalse(ShippingCharge::isOverThreshold((float) $cart->subtotal - 150));
    }

    public function test_the_checkout_summary_shows_the_amount_not_the_word_free(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(2);

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('free over', $html, 'The threshold is worth telling the customer about.');
        $this->assertMatchesRegularExpression(
            '/Shipping.*?50\.00/s',
            $html,
            'The summary is still announcing free delivery on a charged order.'
        );
    }

    public function test_a_free_order_still_says_free(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(5);

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('FREE', $html);
    }
}
