<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The product page's "Apply offers for maximum savings" block used to render four
 * hardcoded bank offers (Paytm / Axis / SBI), identical on every product, whose
 * Apply button only fired a toast reading "Offer applied at checkout". No
 * discount was ever applied and none of those offers existed — meanwhile the
 * controller was already fetching the store's real coupons and the view ignored
 * them. These lock the widget to real data.
 */
class ProductOffersTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Linen Shirt', 'slug' => 'linen-shirt', 'sku' => 'LS-OFF-1',
            'price' => 1000, 'mrp' => 1500, 'cost_price' => 400, 'stock_quantity' => 10,
            'category_id' => $category->id, 'status' => 'approved', 'is_active' => true,
        ]);
    }

    private function coupon(array $overrides = []): Coupon
    {
        $attributes = array_merge([
            'code' => 'REAL10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ], $overrides);

        return Coupon::create($attributes + ['name' => $attributes['code'].' offer']);
    }

    public function test_the_page_never_shows_the_invented_bank_offers(): void
    {
        $this->coupon();

        $html = $this->get('/product/'.$this->product->slug)->assertOk()->getContent();

        foreach (['Paytm', 'Axis Bank', 'SBI Card', 'Offer applied at checkout'] as $invented) {
            $this->assertStringNotContainsString($invented, $html,
                "The product page is advertising an offer the store does not have: {$invented}");
        }
    }

    public function test_a_real_coupon_is_shown_with_its_code(): void
    {
        $this->coupon(['code' => 'REAL10', 'value' => 10]);

        $html = $this->get('/product/'.$this->product->slug)->assertOk()->getContent();

        $this->assertStringContainsString('REAL10', $html);
        // The saving must be computed from the coupon, not invented: 10% of 1000.
        $this->assertStringContainsString('Buy at ₹900', $html);
    }

    public function test_the_offers_block_is_hidden_when_the_store_has_no_coupons(): void
    {
        $html = $this->get('/product/'.$this->product->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('Apply offers for maximum savings', $html,
            'An empty offers block must not be advertised.');
    }

    public function test_a_coupon_the_product_cannot_reach_is_not_advertised_on_it(): void
    {
        // Minimum spend is above this product's price, so showing it here would
        // promise a discount the customer cannot get from this item.
        $this->coupon(['code' => 'BIGSPEND', 'type' => 'fixed', 'value' => 200, 'min_order_amount' => 5000]);

        $html = $this->get('/product/'.$this->product->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('BIGSPEND', $html);
    }

    public function test_an_expired_or_inactive_coupon_is_not_advertised(): void
    {
        $this->coupon(['code' => 'EXPIRED', 'expires_at' => now()->subDay()]);
        $this->coupon(['code' => 'SWITCHEDOFF', 'is_active' => false]);
        $this->coupon(['code' => 'USEDUP', 'usage_limit' => 5, 'times_used' => 5]);

        $html = $this->get('/product/'.$this->product->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('EXPIRED', $html);
        $this->assertStringNotContainsString('SWITCHEDOFF', $html);
        $this->assertStringNotContainsString('USEDUP', $html);
    }

    /**
     * The whole point: a code shown on the product page must actually work when
     * the customer applies it in their cart.
     */
    public function test_a_code_advertised_on_the_product_page_applies_in_the_cart(): void
    {
        $this->coupon(['code' => 'REAL10', 'type' => 'percentage', 'value' => 10]);

        $html = $this->get('/product/'.$this->product->slug)->assertOk()->getContent();
        $this->assertStringContainsString('REAL10', $html);

        // Signed in on purpose: a guest cart is keyed by session id, and the
        // test client does not carry the session cookie between requests, so
        // each call would get its own empty cart. A user cart is keyed by
        // user_id and survives.
        $user = \App\Models\User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1])
            ->assertRedirect();

        $this->actingAs($user)
            ->post('/cart/apply-coupon', ['code' => 'REAL10'])
            ->assertRedirect();

        // 10% of the ₹1000 product.
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'discount' => 100.00,
        ]);
    }
}
