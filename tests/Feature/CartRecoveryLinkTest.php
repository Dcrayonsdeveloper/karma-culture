<?php

namespace Tests\Feature;

use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\AbandonedCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Security of the abandoned-cart recovery link.
 *
 * Binding a session to a cart is not a read: CartController::update() and
 * destroy() authorise a line purely by "does it belong to the cart my session
 * resolves to", so a link that adopted any cart on sight would hand whoever
 * held the URL full write access to somebody else's basket. Every test here
 * pins one half of that.
 */
class CartRecoveryLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Product $product;
    private AbandonedCartService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'customer', 'email' => 'owner@example.test']);

        $category = Category::create(['name' => 'Recovery Cat', 'slug' => 'recovery-cat', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Linked Product',
            'slug' => 'linked-product',
            'sku' => 'LP-001',
            'price' => 500,
            'mrp' => 700,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->service = app(AbandonedCartService::class);
        Cache::flush();
    }

    private function episodeFor(?User $owner): AbandonedCart
    {
        $cart = Cart::create([
            'user_id' => $owner?->id,
            'session_id' => $owner ? null : 'dead-session-'.uniqid(),
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => $this->product->price,
            'total' => $this->product->price,
        ]);

        Cart::withoutTimestamps(fn () => $cart->newQuery()->where('id', $cart->id)->update(['updated_at' => now()->subHours(6)]));

        $this->service->detect();

        return AbandonedCart::firstWhere('cart_id', $cart->id);
    }

    public function test_the_link_carries_a_token_and_no_customer_data(): void
    {
        $episode = $this->episodeFor($this->owner);

        $url = $episode->recoveryUrl();

        $this->assertStringContainsString($episode->token, $url);
        $this->assertStringNotContainsString('owner@example.test', $url);
        $this->assertStringNotContainsString((string) $this->owner->id, parse_url($url, PHP_URL_PATH) ?? '');
        $this->assertStringNotContainsString('cart_id', $url);
    }

    public function test_the_token_is_not_serialised_with_the_model(): void
    {
        $episode = $this->episodeFor($this->owner);

        $this->assertArrayNotHasKey('token', $episode->toArray(),
            'The recovery token would leak into any JSON response or log line that touched this model.');
    }

    public function test_an_unknown_token_is_refused(): void
    {
        $this->get('/cart/recover/'.str_repeat('a', 64))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_a_malformed_token_does_not_reach_the_controller(): void
    {
        $this->get('/cart/recover/short')->assertNotFound();
    }

    public function test_a_guest_following_an_account_cart_link_is_sent_to_login_not_given_the_cart(): void
    {
        $episode = $this->episodeFor($this->owner);

        $this->get($episode->recoveryUrl())->assertRedirect(route('login'));

        $this->assertSame($this->owner->id, $episode->cart->fresh()->user_id,
            'A signed-out visitor was handed an account cart.');
    }

    public function test_a_different_customer_cannot_open_someone_elses_cart(): void
    {
        $episode = $this->episodeFor($this->owner);
        $intruder = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($intruder)->get($episode->recoveryUrl());

        $response->assertRedirect(route('cart.index'));
        $this->assertSame($this->owner->id, $episode->cart->fresh()->user_id,
            'One customer took ownership of another customer\'s cart.');
        $this->assertSame(1, CartItem::where('cart_id', $episode->cart_id)->count(),
            'The intruder\'s visit moved lines out of the owner\'s cart.');
    }

    public function test_the_owner_can_open_their_own_cart(): void
    {
        $episode = $this->episodeFor($this->owner);

        $this->actingAs($this->owner)
            ->get($episode->recoveryUrl())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');
    }

    public function test_an_expired_link_is_refused(): void
    {
        $episode = $this->episodeFor($this->owner);
        $episode->update(['abandoned_at' => now()->subDays(400)]);

        $this->actingAs($this->owner)
            ->get($episode->recoveryUrl())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_the_link_lifetime_is_configurable(): void
    {
        Setting::set('abandoned_cart_recovery_link_days', '1', 'integer', 'abandoned_cart');
        Cache::flush();

        $episode = $this->episodeFor($this->owner);
        $episode->update(['abandoned_at' => now()->subDays(3)]);

        $this->actingAs($this->owner)
            ->get($episode->recoveryUrl())
            ->assertSessionHas('error');
    }

    public function test_an_archived_cart_link_stops_working(): void
    {
        $episode = $this->episodeFor($this->owner);
        $this->service->archive($episode);

        $this->actingAs($this->owner)
            ->get($episode->recoveryUrl())
            ->assertSessionHas('error');
    }

    public function test_an_already_emptied_cart_says_so_rather_than_restoring_nothing(): void
    {
        $episode = $this->episodeFor($this->owner);
        $episode->cart->items()->delete();

        $this->actingAs($this->owner)
            ->get($episode->recoveryUrl())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('info');
    }

    public function test_a_guest_cart_is_adopted_into_the_visitors_session(): void
    {
        // Legacy rows only - adding to a cart has required an account since
        // before this feature - but they exist in production data.
        $episode = $this->episodeFor(null);

        $this->get($episode->recoveryUrl())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $cart = $episode->cart->fresh();
        $this->assertNotSame('dead-session', $cart->session_id);
        $this->assertNull($cart->user_id, 'Adopting a guest cart must not silently give it an account.');
    }

    public function test_a_signed_in_visitor_merges_a_guest_cart_into_their_own(): void
    {
        $episode = $this->episodeFor(null);
        $shopper = User::factory()->create(['role' => 'customer']);

        $this->actingAs($shopper)
            ->get($episode->recoveryUrl())
            ->assertRedirect(route('cart.index'));

        // Merged, never re-parented: the guest row keeps its identity, and the
        // shopper's own cart is the one that ends up holding the line.
        $ownCart = Cart::where('user_id', $shopper->id)->first();
        $this->assertNotNull($ownCart);
        $this->assertSame(1, $ownCart->items()->count());
        $this->assertNull($episode->cart->fresh()->user_id);
    }

    public function test_the_route_is_rate_limited(): void
    {
        $bad = '/cart/recover/'.str_repeat('b', 64);

        for ($i = 0; $i < 10; $i++) {
            $this->get($bad);
        }

        // Without this the token is a free brute-force oracle.
        $this->get($bad)->assertStatus(429);
    }
}
