<?php

namespace Tests\Feature\Cart;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A coupon addressed to named customers may only be spent by one of them.
 *
 * `applicable_users` was enforced by Coupon::canBeUsedBy() alone, and the
 * storefront cart - the route every shopper actually uses - never called it.
 * It checked the coupon was active, met the minimum, and was under its usage
 * caps, then applied it. The column was therefore decorative on the web: any
 * code aimed at one person was redeemable by anybody who learned it.
 *
 * That mattered little while every coupon was a public promotion. Store credit
 * issued for a return is somebody's money, and it is issued exactly this way,
 * so these tests guard the gate that makes the feature safe rather than the
 * feature itself.
 */
class PersonalCouponOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $stranger;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'customer']);
        $this->stranger = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Owned Cat',
            'slug' => 'owned-cat',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Owned Product',
            'slug' => 'owned-product',
            'sku' => 'OWN-001',
            'price' => 3000,
            'mrp' => 3500,
            'cost_price' => 1000,
            'stock_quantity' => 25,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function personalCoupon(): Coupon
    {
        return Coupon::create([
            'code' => 'KKC-OWNEDONLY',
            'name' => 'Store credit',
            'type' => 'fixed',
            'value' => 1000,
            'min_order_amount' => 0,
            'usage_limit' => 1,
            'usage_per_user' => 1,
            'is_active' => true,
            'auto_apply' => false,
            'applicable_users' => [$this->owner->id],
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);
    }

    private function fillCart(User $user): void
    {
        $this->actingAs($user)->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
    }

    public function test_the_customer_it_belongs_to_can_spend_it(): void
    {
        $this->personalCoupon();
        $this->fillCart($this->owner);

        $this->actingAs($this->owner)
            ->post('/cart/apply-coupon', ['code' => 'KKC-OWNEDONLY']);

        $this->assertSame(
            '1000.00',
            (string) $this->owner->fresh()->carts()->latest('id')->first()->discount
        );
    }

    public function test_another_customer_cannot_spend_it(): void
    {
        $this->personalCoupon();
        $this->fillCart($this->stranger);

        $this->actingAs($this->stranger)
            ->post('/cart/apply-coupon', ['code' => 'KKC-OWNEDONLY'])
            ->assertSessionHas('error');

        $this->assertSame(
            '0.00',
            (string) $this->stranger->fresh()->carts()->latest('id')->first()->discount
        );
    }

    public function test_a_signed_out_visitor_cannot_spend_it(): void
    {
        // A guest is not the named customer either, and cannot become them by
        // holding the code. The cart routes are behind auth, so the guard that
        // answers here is the middleware rather than the ownership check - the
        // point of the test is that no unauthenticated path reaches the coupon.
        $this->personalCoupon();

        $response = $this->post('/cart/apply-coupon', ['code' => 'KKC-OWNEDONLY']);

        $this->assertContains($response->status(), [302, 401, 403]);
        $this->assertSame(0, Coupon::where('code', 'KKC-OWNEDONLY')->first()->times_used);
    }

    public function test_an_ordinary_public_coupon_is_unaffected(): void
    {
        // The gate must not break the shop's actual promotions, which name
        // nobody and are meant for everyone.
        Coupon::create([
            'code' => 'PUBLIC100',
            'name' => 'Everyone',
            'type' => 'fixed',
            'value' => 100,
            'min_order_amount' => 0,
            'is_active' => true,
            'auto_apply' => false,
            'applicable_users' => null,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $this->fillCart($this->stranger);

        $this->actingAs($this->stranger)->post('/cart/apply-coupon', ['code' => 'PUBLIC100']);

        $this->assertSame(
            '100.00',
            (string) $this->stranger->fresh()->carts()->latest('id')->first()->discount
        );
    }

    public function test_a_personal_coupon_is_not_advertised_on_product_pages(): void
    {
        // The product page publishes live coupons sorted by value, with a copy
        // button. A credit worth thousands would not merely appear on that
        // list - it would lead it.
        $this->personalCoupon();

        // Singular /product/{slug}: the plural is deliberately unregistered.
        $this->get('/product/owned-product')
            ->assertStatus(200)
            ->assertDontSee('KKC-OWNEDONLY');
    }
}
