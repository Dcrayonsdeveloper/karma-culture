<?php

namespace Tests\Feature\Offer;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\OfferClaim;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The journey the whole feature exists for: claim as a guest, collect later.
 *
 * Exit intent fires at people who are NOT signed in, and /checkout is auth-gated,
 * so "match the typed email against the account" almost always has to happen
 * minutes or days AFTER the claim - which is why the claim is a table row rather
 * than a session key.
 *
 * These do NOT drive the guest cart through /login. phpunit.xml sets
 * SESSION_DRIVER=array, which discards the session at the end of every request,
 * so LoginController::mergeGuestCart() can never find the pre-login cart here -
 * a test written that way would fail for a reason that has nothing to do with
 * this feature, and passing it would prove nothing about the claim. The contract
 * that actually belongs to this code is narrower and is what is asserted: a
 * claim recorded against an address is honoured for whoever later signs in as
 * that address, on whatever cart they then have. Merging the guest cart is
 * LoginController's job and predates this change.
 */
class ClaimedOfferSurvivesLoginTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private Coupon $coupon;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Kids Clothing',
            'slug' => 'kids-clothing',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Jeans',
            'slug' => 'kids-jeans',
            'sku' => 'KJ-002',
            'price' => 1000,
            'mrp' => 1200,
            'cost_price' => 400,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        Setting::updateOrCreate(['key' => 'exit_popup_code'], [
            'value' => 'KARMAA10', 'group' => 'exit_popup', 'type' => 'string',
        ]);

        $this->coupon = Coupon::create([
            'code' => 'KARMAA10',
            'name' => 'Exit intent 10%',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 0,
            'usage_per_user' => 1,
            'is_active' => true,
        ]);
    }

    private function claimAsGuest(string $email): void
    {
        $this->postJson('/newsletter/subscribe', [
            'email' => $email,
            'source' => 'exit_intent',
        ])->assertOk()->assertJsonPath('offer.state', 'saved');
    }

    public function test_a_guest_claim_is_honoured_at_checkout_once_that_address_signs_in(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com', 'role' => 'customer']);

        $this->claimAsGuest('returning@example.com');

        // Recorded, and nothing more: typing an address is not proof of owning
        // it, so a guest claim never touches a cart.
        $this->assertDatabaseHas('offer_claims', ['email' => 'returning@example.com', 'user_id' => null]);
        $this->assertDatabaseCount('carts', 0);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        // NOT assertSee('KARMAA10'): the exit popup prints that code on every
        // storefront page, so it would pass whether or not the coupon applied.
        // This line only renders when $claimedOffer['coupon'] is set.
        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSee('From the offer you claimed');

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'coupon_id' => $this->coupon->id,
            'discount' => 100,
        ]);
    }

    public function test_the_cart_page_honours_a_guest_claim_too(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com', 'role' => 'customer']);

        $this->claimAsGuest('returning@example.com');

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->get('/cart')->assertOk()->assertSee('Your claimed offer is on');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'discount' => 100]);
    }

    public function test_a_claim_for_an_address_never_signed_in_with_pays_out_nothing(): void
    {
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->claimAsGuest('a.stranger@example.com');

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->get('/checkout')->assertOk();

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => null]);
    }

    public function test_claiming_clears_the_removed_a_coupon_flag(): void
    {
        // session('coupon_dismissed') is what stops auto-apply springing a
        // removed coupon back. A guest cannot clear it any other way, so
        // OfferClaims::record() forgets it unconditionally - claiming an offer
        // is a fresh decision, exactly as typing a code is. Seeded onto the
        // request rather than carried from an earlier one because the array
        // session driver would drop it in between.
        $this->withSession(['coupon_dismissed' => true])
            ->postJson('/newsletter/subscribe', [
                'email' => 'returning@example.com',
                'source' => 'exit_intent',
            ])->assertOk();

        $this->assertFalse(session('coupon_dismissed', false));
    }

    public function test_a_coupon_removed_after_claiming_stays_removed(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com', 'role' => 'customer']);

        $this->claimAsGuest('returning@example.com');
        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        // The flag is set by CartController::removeCoupon(); seeded here for the
        // same array-driver reason. The claim must not spring the coupon back.
        $this->actingAs($user)->withSession(['coupon_dismissed' => true])->get('/cart')->assertOk();

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => null]);

        // The claim row survives - the customer said no for now, not for ever.
        $this->assertDatabaseCount('offer_claims', 1);
        $this->assertNotNull(OfferClaim::first());
    }
}
