<?php

namespace Tests\Feature\Admin;

use App\Mail\AbandonedCartReminder;
use App\Models\AbandonedCart;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
use App\Services\AbandonedCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Abandoned Cart Management.
 *
 * The defects these pin are all ones the shape of the data makes easy to write:
 * carts are recycled after checkout so their timestamps and totals lie about
 * the current basket, every page load creates an empty cart row, and writing
 * bookkeeping onto the cart moves the very clock that decides abandonment.
 */
class AbandonedCartTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $customer;
    private Product $product;
    private AbandonedCartService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $this->adminUser->id, 'role' => 'super_admin', 'is_active' => true]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'email' => 'priya@example.test',
            'phone' => '9876543210',
        ]);

        $category = Category::create(['name' => 'Cart Cat', 'slug' => 'cart-cat', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Recoverable Kurta',
            'slug' => 'recoverable-kurta',
            'sku' => 'RK-001',
            'price' => 1000,
            'mrp' => 1500,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->service = app(AbandonedCartService::class);

        // detect() is throttled through the cache for the admin listing, and
        // CACHE_STORE=array means a stale lock would leak between assertions in
        // the same test.
        Cache::flush();
    }

    /**
     * Build a cart with one line, backdated so it already looks abandoned.
     *
     * The backdate is applied with a raw update: CartItem::saved() calls
     * Cart::recalculate(), which writes the cart, so setting updated_at through
     * the model would be overwritten by the very next line added.
     */
    private function abandonedCart(?User $owner, int $hoursIdle = 5, int $quantity = 2): Cart
    {
        $cart = Cart::create([
            'user_id' => $owner?->id,
            'session_id' => $owner ? null : 'guest-session-'.uniqid(),
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'price' => $this->product->price,
            'total' => $this->product->price * $quantity,
        ]);

        $when = now()->subHours($hoursIdle);
        Cart::withoutTimestamps(fn () => $cart->newQuery()->where('id', $cart->id)->update(['updated_at' => $when]));

        return $cart->fresh();
    }

    private function actingAsAdmin(): self
    {
        $this->actingAs($this->adminUser, 'admin');

        return $this;
    }

    // ---------------------------------------------------------------- detection

    public function test_detection_opens_an_episode_for_an_idle_cart_with_items(): void
    {
        $cart = $this->abandonedCart($this->customer);

        $this->assertSame(1, $this->service->detect());

        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);
        $this->assertNotNull($episode, 'An idle cart with items produced no abandoned-cart record.');
        $this->assertSame(AbandonedCart::STATUS_PENDING, $episode->recovery_status);
        $this->assertSame($this->customer->id, $episode->user_id);
        $this->assertSame(1, $episode->item_count);
        $this->assertSame(2, $episode->quantity);
        $this->assertEquals(2000, (float) $episode->total);
        $this->assertSame(64, strlen($episode->token));
    }

    public function test_detection_ignores_empty_carts(): void
    {
        // Every page load - storefront AND admin - fires GET /cart/data, which
        // firstOrCreates a cart row keyed on the session id. Without the
        // whereHas('items') guard the listing would be thousands of these.
        $cart = Cart::create(['user_id' => $this->customer->id]);
        Cart::withoutTimestamps(fn () => $cart->newQuery()->where('id', $cart->id)->update(['updated_at' => now()->subDays(2)]));

        $this->assertSame(0, $this->service->detect());
        $this->assertSame(0, AbandonedCart::count());
    }

    public function test_detection_ignores_a_cart_still_inside_the_threshold(): void
    {
        $this->abandonedCart($this->customer, hoursIdle: 1);

        $this->assertSame(0, $this->service->detect(), 'A cart touched an hour ago is still being shopped, not abandoned.');
    }

    public function test_threshold_is_configurable_and_not_hard_coded(): void
    {
        Setting::set('abandoned_cart_threshold_hours', '12', 'integer', 'abandoned_cart');
        Cache::flush();

        $this->abandonedCart($this->customer, hoursIdle: 5);
        $this->assertSame(0, $this->service->detect(), 'The 12-hour threshold from settings was ignored.');

        Setting::set('abandoned_cart_threshold_hours', '2', 'integer', 'abandoned_cart');
        Cache::flush();

        $this->assertSame(1, app(AbandonedCartService::class)->detect());
    }

    public function test_detection_is_idempotent(): void
    {
        $this->abandonedCart($this->customer);

        $this->service->detect();
        $this->service->detect();
        $this->service->detect();

        $this->assertSame(1, AbandonedCart::count(), 'Re-running detection opened duplicate episodes for one cart.');
    }

    public function test_an_existing_episode_does_not_hide_other_carts_from_detection(): void
    {
        // The skip clause is an OR inside a correlated EXISTS. Written flat it
        // escapes the correlation and means "ANY episode anywhere is newer than
        // this cart", so a single recent episode would stop every older cart in
        // the table from ever being found again.
        $this->abandonedCart($this->customer, hoursIdle: 3);
        $this->assertSame(1, $this->service->detect());

        $older = $this->abandonedCart(
            User::factory()->create(['role' => 'customer']),
            hoursIdle: 10
        );

        $this->assertSame(1, $this->service->detect(), 'An older cart was hidden by another cart\'s episode.');
        $this->assertNotNull(AbandonedCart::firstWhere('cart_id', $older->id));
    }

    public function test_detection_does_not_touch_the_carts_last_activity_timestamp(): void
    {
        // The whole feature rests on this: carts.updated_at is the abandonment
        // clock, and the old cron corrupted it by writing its bookkeeping to
        // carts.metadata, which re-mailed every reminded customer forever.
        $cart = $this->abandonedCart($this->customer);
        $before = $cart->fresh()->updated_at;

        $this->service->sync();

        $this->assertEquals(
            $before->toDateTimeString(),
            $cart->fresh()->updated_at->toDateTimeString(),
            'Detection moved carts.updated_at, which resets the abandonment clock it reads.'
        );
    }

    public function test_a_guest_cart_is_detected_but_has_no_contact_details(): void
    {
        $cart = $this->abandonedCart(null);

        $this->service->detect();

        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);
        $this->assertNotNull($episode);
        $this->assertNull($episode->user_id);
        $this->assertNull($episode->contactEmail());
        $this->assertSame('Guest', $episode->customerName());
    }

    // ----------------------------------------------------------------- recovery

    public function test_checkout_marks_the_episode_recovered_against_the_real_order(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();

        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_number' => 'ORD-RECOVER-1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 2000,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 2000,
        ]);

        $this->service->markRecoveredFromCheckout($cart, $order);

        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);
        $this->assertSame(AbandonedCart::STATUS_RECOVERED, $episode->recovery_status);
        $this->assertSame($order->id, $episode->recovered_order_id);
        $this->assertNotNull($episode->recovered_at);
    }

    public function test_an_emptied_cart_with_no_matching_order_is_not_counted_as_recovered(): void
    {
        // A customer who clears their own basket has not been recovered, and
        // counting them would inflate the recovery rate for free.
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();

        $cart->items()->delete();

        $this->service->reconcile();

        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);
        $this->assertNotSame(AbandonedCart::STATUS_RECOVERED, $episode->recovery_status);
        $this->assertNull($episode->recovered_order_id);
        $this->assertSame('emptied', $episode->cartStatus());
    }

    public function test_a_guest_episode_is_never_auto_recovered(): void
    {
        // orders carries no session_id, so there is genuinely nothing to match a
        // guest cart against - guessing would report recoveries that never were.
        $cart = $this->abandonedCart(null);
        $this->service->detect();

        Order::create([
            'user_id' => $this->customer->id,
            'order_number' => 'ORD-GUEST-1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 2000, 'discount' => 0, 'tax' => 0, 'shipping_cost' => 0, 'total' => 2000,
        ]);

        $cart->items()->delete();
        $this->service->reconcile();

        $this->assertNotSame(
            AbandonedCart::STATUS_RECOVERED,
            AbandonedCart::firstWhere('cart_id', $cart->id)->recovery_status
        );
    }

    public function test_an_unrecovered_episode_expires_after_the_configured_window(): void
    {
        $cart = $this->abandonedCart($this->customer, hoursIdle: 5);
        $this->service->detect();

        // Travel rather than backdating the episode by hand. Moving only the
        // episode's timestamps leaves the cart looking newer than its own
        // episode, which reconcile() correctly reads as "the customer came back
        // and changed the basket" and re-snapshots instead of expiring.
        $this->travelTo(now()->addDays(60));

        $this->service->reconcile();

        $this->assertSame(
            AbandonedCart::STATUS_EXPIRED,
            AbandonedCart::firstWhere('cart_id', $cart->id)->recovery_status
        );

        $this->travelBack();
    }

    public function test_a_changed_basket_refreshes_the_episode_instead_of_opening_a_second(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'variant_id' => null,
            'size' => 'M',
            'quantity' => 1,
            'price' => $this->product->price,
            'total' => $this->product->price,
        ]);
        Cart::withoutTimestamps(fn () => $cart->newQuery()->where('id', $cart->id)->update(['updated_at' => now()->subHours(4)]));

        $this->service->sync();

        $this->assertSame(1, AbandonedCart::where('cart_id', $cart->id)->count(), 'A changed basket opened a duplicate episode.');
        $this->assertSame(2, AbandonedCart::firstWhere('cart_id', $cart->id)->item_count);
    }

    // ---------------------------------------------------------------- reminders

    public function test_sending_a_reminder_mails_the_customer_and_records_it(): void
    {
        Mail::fake();

        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        $this->assertNull($this->service->sendReminder($episode));

        // assertSent, never assertQueued: the test env runs the sync queue so a
        // queued mail would pass here and then sit forever in production, where
        // no worker has ever run.
        Mail::assertSent(AbandonedCartReminder::class, fn ($mail) => $mail->hasTo('priya@example.test'));

        $episode->refresh();
        $this->assertSame(1, $episode->reminder_count);
        $this->assertNotNull($episode->last_reminder_at);
        $this->assertSame(AbandonedCart::STATUS_REMINDER_SENT, $episode->recovery_status);
    }

    public function test_a_second_reminder_inside_the_cooldown_is_refused(): void
    {
        Mail::fake();

        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        $this->service->sendReminder($episode);
        $reason = $this->service->sendReminder($episode->fresh());

        $this->assertNotNull($reason, 'A duplicate reminder inside the cooldown was allowed through.');
        Mail::assertSentCount(1);
    }

    public function test_reminders_stop_at_the_configured_maximum(): void
    {
        Mail::fake();

        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);
        $episode->update(['reminder_count' => $this->service->maxReminders()]);

        $this->assertNotNull($this->service->sendReminder($episode));
        Mail::assertNothingSent();
    }

    public function test_a_cart_with_no_contact_details_cannot_be_reminded(): void
    {
        Mail::fake();

        $cart = $this->abandonedCart(null);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        $reason = $this->service->sendReminder($episode);

        $this->assertStringContainsString('No email address', (string) $reason);
        Mail::assertNothingSent();
    }

    public function test_a_failed_send_is_recorded_rather_than_silently_swallowed(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP refused the connection'));

        $reason = $this->service->sendReminder($episode);

        $this->assertStringContainsString('SMTP refused', (string) $reason);
        $episode->refresh();
        $this->assertSame(0, $episode->reminder_count, 'A failed send still advanced the reminder count.');
        $this->assertStringContainsString('SMTP refused', (string) $episode->last_reminder_error);
    }

    // ------------------------------------------------------------------- screen

    public function test_the_listing_requires_an_authenticated_admin(): void
    {
        $this->get('/admin/abandoned-carts')->assertRedirect(route('admin.login'));
    }

    public function test_staff_without_the_section_are_refused(): void
    {
        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-WH-1',
            'role' => 'warehouse',
            'is_active' => true,
            // Warehouse staff hold `orders` for fulfilment; that must not carry
            // customer contact details with it.
            'permissions' => ['dashboard', 'catalog', 'orders'],
        ]);

        $this->actingAs($staffUser, 'admin')
            ->get('/admin/abandoned-carts')
            ->assertForbidden();
    }

    public function test_staff_granted_the_section_can_open_it(): void
    {
        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-SUP-1',
            'role' => 'support',
            'is_active' => true,
            'permissions' => ['dashboard', 'abandoned_carts'],
        ]);

        $this->actingAs($staffUser, 'admin')
            ->get('/admin/abandoned-carts')
            ->assertOk();
    }

    public function test_the_listing_renders_an_abandoned_cart(): void
    {
        $this->abandonedCart($this->customer);

        $this->actingAsAdmin()
            ->get('/admin/abandoned-carts')
            ->assertOk()
            ->assertSee('Priya Sharma')
            ->assertSee('priya@example.test');
    }

    public function test_opening_the_listing_detects_carts_without_waiting_for_a_cron(): void
    {
        // Neither schedule:run nor a queue worker runs on the production host,
        // so the screen has to find carts itself or it is permanently empty.
        $this->abandonedCart($this->customer);

        $this->actingAsAdmin()->get('/admin/abandoned-carts')->assertOk();

        $this->assertSame(1, AbandonedCart::count());
    }

    public function test_status_filter_and_tab_counts_agree_with_the_rows(): void
    {
        $this->abandonedCart($this->customer);
        $this->service->detect();
        $recovered = $this->abandonedCart(User::factory()->create(['role' => 'customer', 'first_name' => 'Other']));
        $this->service->detect();
        AbandonedCart::firstWhere('cart_id', $recovered->id)->update(['recovery_status' => AbandonedCart::STATUS_RECOVERED]);

        $response = $this->actingAsAdmin()->get('/admin/abandoned-carts?status=recovered')->assertOk();

        $carts = $response->viewData('carts');
        $this->assertCount(1, $carts);
        $this->assertSame(AbandonedCart::STATUS_RECOVERED, $carts->first()->recovery_status);
        $this->assertSame(1, $response->viewData('counts')['recovered']);
        $this->assertSame(1, $response->viewData('counts')['pending']);
    }

    public function test_search_matches_the_customer_and_escapes_like_wildcards(): void
    {
        $this->abandonedCart($this->customer);
        $this->service->detect();

        $this->assertCount(1, $this->actingAsAdmin()->get('/admin/abandoned-carts?search=priya')->viewData('carts'));

        // Unescaped, "%" is a LIKE wildcard and would match every row.
        $this->assertCount(0, $this->actingAsAdmin()->get('/admin/abandoned-carts?search=%25')->viewData('carts'));
    }

    public function test_value_and_item_filters_narrow_the_list(): void
    {
        $this->abandonedCart($this->customer, quantity: 2); // 2000
        $this->service->detect();

        $this->assertCount(1, $this->actingAsAdmin()->get('/admin/abandoned-carts?min_total=1500')->viewData('carts'));
        $this->assertCount(0, $this->actingAsAdmin()->get('/admin/abandoned-carts?min_total=5000')->viewData('carts'));
        $this->assertCount(0, $this->actingAsAdmin()->get('/admin/abandoned-carts?min_items=3')->viewData('carts'));
    }

    public function test_pagination_is_bounded(): void
    {
        $this->actingAsAdmin()->get('/admin/abandoned-carts?per_page=100')->assertOk();
        // Unbounded per_page is a live denial-of-service class in this codebase.
        $this->actingAsAdmin()->get('/admin/abandoned-carts?per_page=100000')->assertSessionHasErrors('per_page');
    }

    public function test_the_sort_parameter_is_allowlisted(): void
    {
        $this->actingAsAdmin()->get('/admin/abandoned-carts?sort=total&dir=asc')->assertOk();
        // orderBy cannot bind an identifier, so anything off the list is refused.
        $this->actingAsAdmin()->get('/admin/abandoned-carts?sort=token')->assertSessionHasErrors('sort');
    }

    public function test_the_detail_page_shows_the_basket_without_repricing_it(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        $before = $cart->fresh()->updated_at->toDateTimeString();

        $this->actingAsAdmin()
            ->get("/admin/abandoned-carts/{$episode->id}")
            ->assertOk()
            ->assertSee('Recoverable Kurta')
            ->assertSee('Priya Sharma');

        $this->assertSame($before, $cart->fresh()->updated_at->toDateTimeString(),
            'Opening the detail page rewrote the cart, which resets the abandonment clock.');
    }

    public function test_the_detail_page_never_leaks_the_recovery_token_of_another_cart(): void
    {
        $mine = $this->abandonedCart($this->customer);
        $theirs = $this->abandonedCart(User::factory()->create(['role' => 'customer']));
        $this->service->detect();

        $mineEpisode = AbandonedCart::firstWhere('cart_id', $mine->id);
        $theirsEpisode = AbandonedCart::firstWhere('cart_id', $theirs->id);

        $this->actingAsAdmin()
            ->get("/admin/abandoned-carts/{$mineEpisode->id}")
            ->assertOk()
            ->assertDontSee($theirsEpisode->token);
    }

    // ------------------------------------------------------------------ actions

    public function test_marking_contacted_records_the_attempt(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        $this->actingAsAdmin()
            ->post("/admin/abandoned-carts/{$episode->id}/contacted")
            ->assertRedirect();

        $episode->refresh();
        $this->assertSame(AbandonedCart::STATUS_CONTACTED, $episode->recovery_status);
        $this->assertNotNull($episode->last_contacted_at);
    }

    public function test_an_order_belonging_to_someone_else_cannot_be_linked_as_the_recovery(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        $stranger = User::factory()->create(['role' => 'customer']);
        $strangersOrder = Order::create([
            'user_id' => $stranger->id,
            'order_number' => 'ORD-STRANGER-1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 500, 'discount' => 0, 'tax' => 0, 'shipping_cost' => 0, 'total' => 500,
        ]);

        $this->actingAsAdmin()
            ->post("/admin/abandoned-carts/{$episode->id}/recovered", ['order_id' => $strangersOrder->id])
            ->assertRedirect();

        $episode->refresh();
        $this->assertNotSame(AbandonedCart::STATUS_RECOVERED, $episode->recovery_status,
            'Another customer order was accepted as this cart\'s recovery, crediting the wrong account.');
    }

    public function test_a_recovered_cart_cannot_be_archived_out_of_the_figures(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);
        $episode->update(['recovery_status' => AbandonedCart::STATUS_RECOVERED]);

        $this->actingAsAdmin()->post("/admin/abandoned-carts/{$episode->id}/archive")->assertRedirect();

        $this->assertSame(AbandonedCart::STATUS_RECOVERED, $episode->fresh()->recovery_status);
    }

    public function test_archiving_removes_the_cart_from_the_recovery_rate(): void
    {
        $cart = $this->abandonedCart($this->customer);
        $this->service->detect();
        $episode = AbandonedCart::firstWhere('cart_id', $cart->id);

        $this->actingAsAdmin()->post("/admin/abandoned-carts/{$episode->id}/archive")->assertRedirect();

        $this->assertSame(AbandonedCart::STATUS_ARCHIVED, $episode->fresh()->recovery_status);
        // Nothing left in the denominator, so the rate is 0 rather than a
        // divide-by-zero or a rate made worse by tidying up.
        $this->assertSame(0.0, $this->service->stats()['recovery_rate']);
    }

    public function test_bulk_actions_are_capped(): void
    {
        $this->actingAsAdmin()
            ->post('/admin/abandoned-carts/bulk-action', ['action' => 'archive', 'ids' => range(1, 500)])
            ->assertSessionHasErrors('ids');
    }

    public function test_the_scan_action_finds_carts_immediately(): void
    {
        $this->abandonedCart($this->customer);

        $this->actingAsAdmin()->post('/admin/abandoned-carts/scan')->assertRedirect();

        $this->assertSame(1, AbandonedCart::count());
    }

    public function test_settings_are_saved_and_bounded(): void
    {
        $this->actingAsAdmin()->put('/admin/abandoned-carts/settings', [
            'threshold_hours' => 6,
            'expiry_days' => 14,
            'reminder_cooldown_hours' => 48,
            'max_reminders' => 2,
            'recovery_link_days' => 21,
            'recent_hours' => 12,
        ])->assertRedirect();

        Cache::flush();
        $this->assertSame(6, app(AbandonedCartService::class)->thresholdHours());

        $this->actingAsAdmin()->put('/admin/abandoned-carts/settings', [
            'threshold_hours' => 0,
            'expiry_days' => 14,
            'reminder_cooldown_hours' => 48,
            'max_reminders' => 2,
            'recovery_link_days' => 21,
            'recent_hours' => 12,
        ])->assertSessionHasErrors('threshold_hours');
    }

    public function test_the_export_carries_the_same_filters_as_the_list(): void
    {
        $this->abandonedCart($this->customer);
        $this->service->detect();

        $response = $this->actingAsAdmin()->get('/admin/abandoned-carts/export?min_total=5000');
        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringNotContainsString('priya@example.test', $csv,
            'The export ignored the filters the list was taken with.');
    }
}
