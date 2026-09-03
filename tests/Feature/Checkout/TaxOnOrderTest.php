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
 * Tax was calculated, stored, and then shown and billed to nobody.
 *
 * Cart::recalculate() has always summed each taxable item at its own rate and
 * written it to carts.tax. The checkout summary had no tax line at all, its
 * total was subtotal - discount, and the order was created with tax => 0. Every
 * product in this catalogue is taxable at 18%, so that is 18% the shop expected
 * and never collected.
 *
 * The switch on Settings -> Tax was not read either, so turning taxes off would
 * not have stopped them once anything started charging.
 */
class TaxOnOrderTest extends TestCase
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
            'sku' => 'TAX-1', 'price' => 100, 'mrp' => 100, 'stock_quantity' => 50,
            'category_id' => $category->id, 'is_active' => true, 'status' => 'approved',
            'is_taxable' => true, 'tax_rate' => 18,
        ]);
    }

    private function setting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'tax']);
        Cache::forget("setting.{$key}");
    }

    private function cartWith(int $quantity = 2): Cart
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

    public function test_tax_is_added_to_the_cart_total_when_it_is_switched_on(): void
    {
        $this->setting('tax_enabled', '1');

        $cart = $this->cartWith(2);   // 200 + 18%

        $this->assertSame('36.00', (string) $cart->tax);
        $this->assertSame('236.00', (string) $cart->total, 'Tax is not in the total.');
    }

    /**
     * The switch is the whole safety net: a shop that has not enabled tax must
     * not start charging it because this shipped.
     */
    public function test_no_tax_while_the_setting_is_off(): void
    {
        $this->setting('tax_enabled', '0');

        $cart = $this->cartWith(2);

        $this->assertSame('0.00', (string) $cart->tax);
        $this->assertSame('200.00', (string) $cart->total);
    }

    public function test_no_tax_when_the_setting_has_never_been_saved(): void
    {
        $this->assertNull(Setting::where('key', 'tax_enabled')->first());

        $this->assertSame('0.00', (string) $this->cartWith(2)->tax);
    }

    public function test_a_product_marked_not_taxable_is_left_alone(): void
    {
        $this->setting('tax_enabled', '1');
        $this->product->update(['is_taxable' => false]);

        $this->assertSame('0.00', (string) $this->cartWith(2)->tax);
    }

    public function test_the_checkout_summary_shows_the_tax(): void
    {
        $this->setting('tax_enabled', '1');
        $this->cartWith(2);

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/Tax.*?36\.00/s',
            $html,
            'The customer is charged tax the summary does not mention.'
        );
    }

    /**
     * A zero line on a tax-free shop is noise, so the row only appears when
     * there is something in it.
     */
    public function test_no_tax_line_when_there_is_no_tax(): void
    {
        $this->setting('tax_enabled', '0');
        $this->cartWith(2);

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/>\s*Tax\s*</', $html);
    }

    /**
     * Tax and delivery are separate lines that both have to reach the total.
     */
    public function test_tax_and_shipping_add_up_together(): void
    {
        $this->setting('tax_enabled', '1');

        Setting::updateOrCreate(['key' => 'flat_rate_enabled'], ['value' => '1', 'group' => 'shipping']);
        Setting::updateOrCreate(['key' => 'flat_rate_amount'], ['value' => '50', 'group' => 'shipping']);
        Cache::forget('setting.flat_rate_enabled');
        Cache::forget('setting.flat_rate_amount');

        $cart = $this->cartWith(2);   // 200 + 36 tax + 50 delivery

        $this->assertSame('36.00', (string) $cart->tax);
        $this->assertSame('50.00', (string) $cart->shipping);
        $this->assertSame('286.00', (string) $cart->total);
    }
}
