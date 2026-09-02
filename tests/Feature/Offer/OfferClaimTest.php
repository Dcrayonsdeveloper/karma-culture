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
 * The exit popup used to hand out `exit_popup_code` as display text and nothing
 * anywhere linked that string to a coupon, an account or a cart: "Claim Offer"
 * only wrote a newsletter subscriber, and the customer had to remember the code
 * and retype it at checkout - where it failed outright if no Coupon row existed.
 *
 * These cover the claim now being recorded, and the rule that decides whether it
 * may touch a cart: the address has to be the one the customer is signed in as.
 */
class OfferClaimTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

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
            'sku' => 'KJ-001',
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
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'KARMAA10',
            'name' => 'Exit intent 10%',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 0,
            'usage_per_user' => 1,
            'is_active' => true,
        ], $overrides));
    }

    private function claim(array $body = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/newsletter/subscribe', array_merge([
            'email' => 'shopper@example.com',
            'source' => 'exit_intent',
        ], $body));
    }

    public function test_a_guest_claim_is_recorded_and_touches_no_cart(): void
    {
        $this->coupon();

        $this->claim(['email' => 'guest@example.com'])
            ->assertOk()
            ->assertJsonPath('offer.state', 'saved');

        // user_id stays null for a guest and is stamped for a signed-in claimer -
        // covered both ways so the column cannot quietly stop being written.
        $this->assertDatabaseHas('offer_claims', [
            'email' => 'guest@example.com',
            'code' => 'KARMAA10',
            'user_id' => null,
        ]);
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_a_signed_in_claim_with_the_account_email_applies_the_coupon(): void
    {
        $coupon = $this->coupon();
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->actingAs($user)->claim()
            ->assertOk()
            ->assertJsonPath('offer.state', 'applied')
            // json_encode() drops the zero fraction, so this decodes to an int -
            // assertJsonPath is strict and 100.0 would not match.
            ->assertJsonPath('offer.discount', 100);

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => $coupon->id, 'discount' => 100]);
        $this->assertDatabaseHas('offer_claims', ['email' => 'shopper@example.com', 'user_id' => $user->id]);
    }

    public function test_a_claim_typed_for_someone_else_is_not_stamped_with_the_claimers_account(): void
    {
        $this->coupon();
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->claim(['email' => 'someone.else@example.com'])->assertOk();

        $this->assertDatabaseHas('offer_claims', ['email' => 'someone.else@example.com', 'user_id' => null]);
    }

    public function test_a_coupon_scoped_to_other_products_is_not_attached(): void
    {
        // Coupon::calculateDiscount() only consults applicable_products for
        // buy_x_get_y, so without an explicit check a scoped percentage coupon
        // would discount the whole basket.
        $this->coupon(['applicable_products' => [$this->product->id + 5000]]);
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->claim()->assertOk()->assertJsonPath('offer.state', 'saved');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => null]);
    }

    public function test_a_free_shipping_claim_stays_attached_and_reports_as_applied(): void
    {
        // calculateDiscount() returns 0 for free_shipping because the saving is
        // on shipping, not the subtotal - judging "did it stick" on the discount
        // alone would report failure for a coupon that is on the cart working.
        $coupon = $this->coupon(['type' => 'free_shipping', 'value' => 0]);
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->claim()->assertOk()->assertJsonPath('offer.state', 'applied');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => $coupon->id]);
    }

    public function test_a_signed_in_claim_for_someone_elses_email_leaves_the_cart_alone(): void
    {
        $this->coupon();
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        // The same envelope a guest gets, so the response cannot be used to
        // test whether an address has an account here.
        $this->actingAs($user)->claim(['email' => 'someone.else@example.com'])
            ->assertOk()
            ->assertJsonPath('offer.state', 'saved');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => null]);
        $this->assertDatabaseHas('offer_claims', ['email' => 'someone.else@example.com']);
    }

    public function test_the_code_comes_from_settings_not_the_request_body(): void
    {
        $this->coupon();
        $this->coupon(['code' => 'FREESTUFF', 'value' => 100]);

        $this->claim(['code' => 'FREESTUFF', 'coupon_code' => 'FREESTUFF'])->assertOk();

        $this->assertDatabaseHas('offer_claims', ['email' => 'shopper@example.com', 'code' => 'KARMAA10']);
        $this->assertDatabaseMissing('offer_claims', ['code' => 'FREESTUFF']);
    }

    public function test_the_email_is_stored_lower_cased_and_matches_the_account_either_way(): void
    {
        $coupon = $this->coupon();
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->actingAs($user)->claim(['email' => 'Shopper@Example.COM'])
            ->assertOk()
            ->assertJsonPath('offer.state', 'applied');

        // Read the column back rather than asserting through assertDatabaseHas:
        // the default collation is case-insensitive, so a WHERE on the address
        // would match whatever case was stored and prove nothing.
        $this->assertSame('shopper@example.com', OfferClaim::firstOrFail()->email);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => $coupon->id]);
    }

    public function test_a_claim_made_before_the_coupon_exists_starts_working_once_it_does(): void
    {
        // The admin screen deliberately allows a code with no coupon behind it.
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->claim()->assertOk()->assertJsonPath('offer.state', 'saved');
        $this->assertDatabaseHas('offer_claims', ['email' => 'shopper@example.com', 'coupon_id' => null]);

        $coupon = $this->coupon();
        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->get('/cart')->assertOk();

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => $coupon->id]);
        $this->assertDatabaseHas('offer_claims', ['email' => 'shopper@example.com', 'coupon_id' => $coupon->id]);
    }

    public function test_re_claiming_refreshes_the_window_rather_than_freezing_it(): void
    {
        // Freezing expires_at on the first insert turns a claim into a denial of
        // service: one POST with a stranger's address would start their window
        // silently and their own later claim would be a no-op.
        $this->coupon();

        $this->claim()->assertOk();
        $first = OfferClaim::where('email', 'shopper@example.com')->firstOrFail();

        $this->travel(3)->days();
        $this->claim()->assertOk();

        $this->assertDatabaseCount('offer_claims', 1);
        $this->assertTrue(
            OfferClaim::where('email', 'shopper@example.com')->firstOrFail()->expires_at->gt($first->expires_at),
            'Re-claiming should extend the claim window, not leave the original deadline in place.'
        );
    }

    public function test_an_expired_claim_never_applies(): void
    {
        $this->coupon();
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->claim()->assertOk();
        OfferClaim::query()->update(['expires_at' => now()->subDay()]);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->get('/cart')->assertOk();

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => null]);
    }

    public function test_a_coupon_the_customer_may_not_use_is_never_attached(): void
    {
        // applicable_users is enforced by Coupon::canBeUsedBy(), which this path
        // composes rather than reimplementing.
        $this->coupon(['applicable_users' => [999999]]);
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->claim()->assertOk()->assertJsonPath('offer.state', 'saved');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => null]);
    }

    public function test_a_coupon_the_customer_typed_themselves_is_not_overwritten(): void
    {
        $this->coupon();
        $manual = $this->coupon(['code' => 'BIGGER', 'value' => 25]);
        $user = User::factory()->create(['email' => 'shopper@example.com', 'role' => 'customer']);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->post('/cart/apply-coupon', ['code' => 'BIGGER']);

        $this->actingAs($user)->claim()->assertOk()->assertJsonPath('offer.state', 'saved');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'coupon_id' => $manual->id]);
    }

    public function test_a_claim_is_not_recorded_when_the_popup_is_switched_off(): void
    {
        Setting::updateOrCreate(['key' => 'exit_popup_enabled'], [
            'value' => '0', 'group' => 'exit_popup', 'type' => 'boolean',
        ]);
        $this->coupon();

        $this->claim()->assertOk()->assertJsonPath('offer.state', 'none');

        $this->assertDatabaseCount('offer_claims', 0);
    }

    public function test_other_signup_sources_do_not_claim_the_offer(): void
    {
        $this->coupon();

        $this->postJson('/newsletter/subscribe', [
            'email' => 'shopper@example.com',
            'name' => 'Ada Lovelace',
            'phone' => '9876543210',
            'source' => 'offer_popup',
        ])->assertOk()->assertJsonPath('offer.state', 'none');

        $this->assertDatabaseCount('offer_claims', 0);
    }
}
