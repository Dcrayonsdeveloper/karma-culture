<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The cart page kept promising free delivery after the checkout stopped.
 *
 * ShippingCharge, Cart::recalculate() and the checkout summary were all fixed
 * together, and the cart page was left behind. Its summary printed
 *
 *     Shipping (free over Rs999)                                   FREE
 *
 * with both halves hardcoded: the word FREE unconditionally, and a 999 that was
 * the Blade default of a setting the shop had actually set to 400. Its "Total
 * Amount" is an Alpine getter that read `subtotal - discount`, so the delivery
 * charge and the tax were missing from the figure the shopper agreed to before
 * pressing PROCEED TO CHECKOUT - and the next page then quoted a larger one.
 *
 * The fix sends the server's figures to the page instead of letting it work
 * delivery out for itself, because a second copy of the threshold rule in
 * JavaScript is precisely how the two screens drift apart again.
 */
class CartShippingChargeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Linen Shirt', 'slug' => 'linen-shirt', 'description' => 'x',
            'sku' => 'CART-SHIP-1', 'price' => 100, 'mrp' => 100, 'stock_quantity' => 50,
            'category_id' => $category->id, 'is_active' => true, 'status' => 'approved',
            // Not taxable: delivery is what is under test, and leaving tax on
            // made every total a test of two things at once.
            'is_taxable' => false,
        ]);
    }

    private function setting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'shipping']);
        Cache::forget("setting.{$key}");
    }

    /** A shop charging 50 for delivery below a 400 minimum - the reported setup. */
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

    private function cartPage(): string
    {
        return $this->actingAs($this->user)->get(route('cart.index'))->assertOk()->getContent();
    }

    // ---------------------------------------------------------------- summary

    public function test_the_summary_shows_the_charge_on_a_basket_under_the_minimum(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(2);   // 200 payable

        // Anchored on the trailing comma the seed line ends in. Bare
        // 'shipping: 50' is a prefix of 'shipping: 500' and of 'shipping: 50.5',
        // so it would have passed on a charge that was ten times too large.
        $this->assertStringContainsString(
            'shipping: 50,',
            $this->cartPage(),
            'The page was seeded with no delivery charge, so its total cannot include one.'
        );
    }

    public function test_the_summary_is_free_on_a_basket_over_the_minimum(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(5);   // 500 payable

        $this->assertStringContainsString('shipping: 0,', $this->cartPage());
    }

    /**
     * The reported bug in one assertion: the shop's minimum is 400, and the page
     * used to print the Blade default of 999 beside every basket.
     */
    public function test_the_threshold_note_quotes_the_configured_minimum_not_the_default(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(2);

        $html = $this->cartPage();

        $this->assertStringContainsString('freeShipThreshold: 400,', $html);
        // The old hardcoded default, asserted against the seed rather than the
        // rendered note: the note itself is assembled in the browser now, so
        // no server-rendered string could ever contain it and the obvious
        // assertion would have been one that can never fail.
        $this->assertStringNotContainsString('freeShipThreshold: 999,', $html);
        $this->assertStringNotContainsString('free over ₹999', $html);
    }

    /**
     * Delivery and tax reach the figure, not just the rows above it. Asserted on
     * the getter's source because it is evaluated in the browser: there is no
     * server-rendered total on this page to assert against.
     */
    public function test_the_total_getter_adds_delivery_and_tax(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(2);

        $this->assertStringContainsString(
            'this.subtotal - this.discount + this.shipping + this.tax',
            $this->cartPage(),
            'The cart total is still short by the delivery charge.'
        );
    }

    /**
     * The word FREE must no longer be welded into the markup - it is now one
     * arm of an x-if that only renders when there is nothing to pay.
     */
    public function test_the_shipping_row_is_conditional_rather_than_a_hardcoded_free(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(2);

        $html = $this->cartPage();

        $this->assertStringContainsString('x-if="shipping > 0"', $html);
        $this->assertStringContainsString('x-if="shipping <= 0"', $html);
    }

    /**
     * A shop that has never touched the shipping screen keeps free delivery, so
     * this change bills nobody until someone switches it on.
     */
    public function test_an_unconfigured_shop_still_ships_free(): void
    {
        $this->cartWith(2);

        $this->assertStringContainsString('shipping: 0,', $this->cartPage());
    }

    /**
     * Switching Free Shipping off must silence the "free over X" note.
     *
     * The threshold field sits inside an x-show on the settings screen, so it
     * still submits when the section is collapsed, and updateShipping() stores
     * the toggle and the amount as two independent rows. Reading the amount on
     * its own therefore advertised a minimum that isOverThreshold() refuses to
     * honour at any basket size - the shopper could spend past the advertised
     * figure and still be charged.
     */
    public function test_the_free_delivery_note_is_silent_when_free_shipping_is_switched_off(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->setting('free_shipping_enabled', '0');
        $this->cartWith(2);

        $html = $this->cartPage();

        // 0 is the page's own "say nothing" value - the note is gated on
        // freeShipThreshold > 0.
        $this->assertStringContainsString('freeShipThreshold: 0,', $html);
        $this->assertStringNotContainsString('freeShipThreshold: 400,', $html);
        // ...and the charge itself still applies, at every basket size.
        $this->assertStringContainsString('shipping: 50,', $html);
    }

    // ------------------------------------------------------------- the payload

    public function test_cart_data_reports_the_delivery_charge(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(2);

        $body = $this->actingAs($this->user)
            ->getJson(route('cart.data'))
            ->assertOk()
            ->json();

        // Cast rather than assertJsonPath: json_encode writes a whole float as
        // `50`, which decodes to an int, and the strict path assertion then
        // fails on 50 !== 50.0 while the payload is perfectly correct.
        $this->assertSame(50.0, (float) $body['cart_shipping']);
        $this->assertSame(250.0, (float) $body['cart_total']);
    }

    /**
     * Changing a quantity is what carries a basket over the minimum, so the
     * update response is the one that has to report delivery going free - and
     * it must say so with a 0 the page actually reads back.
     */
    public function test_raising_the_quantity_over_the_minimum_frees_the_delivery(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $cart = $this->cartWith(2);
        $item = $cart->items()->first();

        $body = $this->actingAs($this->user)
            ->putJson("/cart/{$item->id}", ['quantity' => 5])
            ->assertOk()
            ->json();

        $this->assertSame(0.0, (float) $body['cart_shipping']);
        $this->assertSame(500.0, (float) $body['cart_total']);
    }

    public function test_lowering_the_quantity_under_the_minimum_charges_again(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $cart = $this->cartWith(5);
        $item = $cart->items()->first();

        $body = $this->actingAs($this->user)
            ->putJson("/cart/{$item->id}", ['quantity' => 2])
            ->assertOk()
            ->json();

        $this->assertSame(50.0, (float) $body['cart_shipping']);
        $this->assertSame(250.0, (float) $body['cart_total']);
    }

    /**
     * A discount that drops the payable amount back under the minimum takes the
     * free delivery with it - the threshold is measured on what is actually paid.
     */
    public function test_a_coupon_that_drops_the_basket_under_the_minimum_reinstates_the_charge(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(5);   // 500, free

        Coupon::create([
            'code' => 'SAVE150', 'name' => 'Save 150', 'type' => 'fixed', 'value' => 150,
            'is_active' => true, 'auto_apply' => false,
        ]);

        $body = $this->actingAs($this->user)
            ->postJson(route('cart.apply-coupon'), ['code' => 'SAVE150'])
            ->assertOk()
            ->json();

        $this->assertSame(150.0, (float) $body['cart_discount']);
        $this->assertSame(50.0, (float) $body['cart_shipping'], '350 payable is under the 400 minimum.');
        $this->assertSame(400.0, (float) $body['cart_total'], '500 - 150 + 50.');
    }

    /**
     * Removing a coupon sets coupon_dismissed for the session. That flag used to
     * skip recalculate() on the cart page ENTIRELY, not just the auto-apply pass,
     * so from then on the page quoted whatever was last written to the cart row -
     * and a delivery charge configured afterwards never reached the shopper.
     */
    public function test_a_dismissed_coupon_does_not_freeze_the_delivery_charge(): void
    {
        $this->cartWith(2);   // built while the shop charged nothing

        $this->actingAs($this->user)
            ->deleteJson(route('cart.remove-coupon'))
            ->assertOk();

        // The shop starts charging only now.
        $this->chargingShop(flat: 50, threshold: 400);

        $this->assertStringContainsString(
            'shipping: 50,',
            $this->cartPage(),
            'The cart page is still quoting the delivery charge from before the setting changed.'
        );
    }

    /**
     * ...while still honouring what the dismissal is actually for: an auto-apply
     * coupon the shopper removed must not spring back on the next page load.
     */
    public function test_a_dismissed_coupon_still_does_not_come_back(): void
    {
        $this->cartWith(5);

        Coupon::create([
            'code' => 'AUTO10', 'name' => 'Auto 10%', 'type' => 'percentage', 'value' => 10,
            'is_active' => true, 'auto_apply' => true,
        ]);

        $this->actingAs($this->user)
            ->deleteJson(route('cart.remove-coupon'))
            ->assertOk();

        $this->actingAs($this->user)->get(route('cart.index'))->assertOk();

        $this->assertNull(
            Cart::where('user_id', $this->user->id)->first()->coupon_id,
            'The removed coupon was auto-applied again on view.'
        );
    }

    /**
     * ...and the checkout must not put it back either.
     *
     * CheckoutController@index recalculated with auto-apply on and no dismissal
     * check, so a coupon removed on the cart page was reinstated one screen
     * later: the cart quoted one Total Amount and the checkout quoted a smaller
     * one. Because a discount moves the basket against the free-delivery
     * minimum, the two screens could disagree about the delivery charge too.
     */
    public function test_the_checkout_does_not_reinstate_a_dismissed_coupon(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(5);   // 500 payable - free delivery

        Coupon::create([
            'code' => 'AUTO30', 'name' => 'Auto 30%', 'type' => 'percentage', 'value' => 30,
            'is_active' => true, 'auto_apply' => true,
        ]);

        $this->actingAs($this->user)->deleteJson(route('cart.remove-coupon'))->assertOk();
        $this->actingAs($this->user)->get(route('checkout.index'))->assertOk();

        $cart = Cart::where('user_id', $this->user->id)->first();

        $this->assertNull($cart->coupon_id, 'The checkout auto-applied a coupon the shopper had removed.');
        // 30% off 500 would have left 350 payable and reinstated the 50 charge,
        // so the delivery charge is the tell that the coupon stayed off.
        $this->assertSame('0.00', (string) $cart->shipping);
        $this->assertSame('500.00', (string) $cart->total);
    }

    /** Removing a line is what drops a basket back under the minimum. */
    public function test_removing_a_line_reports_the_reinstated_charge(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);

        $cart = Cart::create(['user_id' => $this->user->id]);
        foreach ([3, 2] as $qty) {
            $cart->items()->create([
                'product_id' => $this->product->id, 'quantity' => $qty,
                'price' => 100, 'total' => 100 * $qty,
            ]);
        }
        $cart->refresh()->recalculate();
        $this->assertSame('0.00', (string) $cart->refresh()->shipping, 'Precondition: 500 ships free.');

        $body = $this->actingAs($this->user)
            ->deleteJson("/cart/{$cart->items()->orderByDesc('id')->first()->id}")
            ->assertOk()
            ->json();

        $this->assertSame(50.0, (float) $body['cart_shipping'], '300 left in the basket is under the minimum.');
        $this->assertSame(350.0, (float) $body['cart_total']);
    }

    public function test_removing_a_coupon_reports_the_recalculated_charge(): void
    {
        $this->chargingShop(flat: 50, threshold: 400);
        $this->cartWith(5);   // 500

        Coupon::create([
            'code' => 'DROP150', 'name' => 'Drop 150', 'type' => 'fixed', 'value' => 150,
            'is_active' => true, 'auto_apply' => false,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('cart.apply-coupon'), ['code' => 'DROP150'])
            ->assertOk();

        // Taking it off puts the basket back over the minimum, so the charge
        // must come back off in the same response.
        $body = $this->actingAs($this->user)
            ->deleteJson(route('cart.remove-coupon'))
            ->assertOk()
            ->json();

        $this->assertSame(0.0, (float) $body['cart_shipping']);
        $this->assertSame(500.0, (float) $body['cart_total']);
    }

    // -------------------------------------------------------------------- tax

    /**
     * Tax rides along the same wires as delivery. It is a separate feature, but
     * the cart total now adds it, so a page that did not receive it would be
     * short by exactly the amount the checkout goes on to charge.
     */
    public function test_tax_reaches_the_cart_summary_and_the_payload(): void
    {
        // Settings -> Tax -> Enable Taxes. Cart::recalculate() gates the whole
        // sum on this, so without it the fixture below stores 0 and the test
        // would pass for the wrong reason.
        Setting::updateOrCreate(['key' => 'tax_enabled'], ['value' => '1', 'group' => 'tax']);
        Cache::forget('setting.tax_enabled');

        $taxed = Product::create([
            'name' => 'Taxed Shirt', 'slug' => 'taxed-shirt', 'description' => 'x',
            'sku' => 'CART-TAX-1', 'price' => 100, 'mrp' => 100, 'stock_quantity' => 50,
            'category_id' => $this->product->category_id, 'is_active' => true,
            'status' => 'approved', 'is_taxable' => true, 'tax_rate' => 10,
        ]);

        $cart = Cart::create(['user_id' => $this->user->id]);
        $cart->items()->create([
            'product_id' => $taxed->id, 'quantity' => 2, 'price' => 100, 'total' => 200,
        ]);
        $cart->refresh()->recalculate();

        $this->assertSame('20.00', (string) $cart->refresh()->tax, 'Precondition: 10% of 200.');

        $this->assertStringContainsString('tax: 20,', $this->cartPage());

        $this->actingAs($this->user)
            ->getJson(route('cart.data'))
            ->assertOk()
            ->assertJsonPath('cart_tax', 20);
    }
}
