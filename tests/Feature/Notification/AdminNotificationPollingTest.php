<?php

namespace Tests\Feature\Notification;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The admin bell's background poll.
 *
 * Everything the bell showed was computed in a @php block in
 * admin.partials.header, so the count and the list were only ever as fresh as
 * the last page load: an admin sitting on the dashboard could take an order,
 * two enquiries and a return request and see none of them until they clicked
 * something. admin.notifications.updates is the same two questions - "how many
 * are unread" and "what has arrived" - asked without a page load.
 *
 * The rules this endpoint has to keep are the ones a poll makes easy to break:
 * it must never widen who can see what (the audience and ownership scoping is
 * the whole reason those exist), it must never turn "fetched" into "read", and
 * it must never hand a fresh client the backlog - a client with no cursor gets
 * a cursor and nothing else, which is what stops twenty existing notifications
 * announcing themselves twenty times over.
 */
class AdminNotificationPollingTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->admin('Poll', 'Admin');
    }

    private function admin(string $first, string $last): User
    {
        $user = User::factory()->create([
            'first_name' => $first,
            'last_name' => $last,
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $user->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        return $user;
    }

    private function adminRow(User $user, array $attributes = []): Notification
    {
        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $notification = Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'title' => 'New Order',
            'content' => 'Order KK-1001 placed by Asha Menon',
            'channel' => 'database',
            'is_read' => false,
        ], $attributes));

        if ($createdAt) {
            // created_at is not fillable, and back-dating is the only way to
            // put a row on a known side of a cursor.
            $notification->created_at = $createdAt;
            $notification->save();
        }

        return $notification->refresh();
    }

    private function customerRow(User $user, array $attributes = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => 'order_placed',
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'title' => 'Order Confirmed',
            'content' => 'Your order KK-2002 has been confirmed.',
            'channel' => 'database',
            'is_read' => false,
        ], $attributes));
    }

    private function poll(?string $since = null, ?User $as = null): \Illuminate\Testing\TestResponse
    {
        $url = route('admin.notifications.updates', $since === null ? [] : ['since' => $since]);

        return $this->actingAs($as ?? $this->adminUser, 'admin')->getJson($url);
    }

    /**
     * The first load of a page - or of a whole new tab - has no cursor, and this
     * is what stops it announcing history. The admin already has three
     * notifications on screen; the answer must carry none of them, only the
     * count and the cursor that makes the NEXT poll meaningful.
     */
    public function test_a_client_with_no_cursor_is_given_a_baseline_and_no_notifications(): void
    {
        $this->adminRow($this->adminUser);
        $this->adminRow($this->adminUser, ['title' => 'New Enquiry', 'type' => 'new_enquiry']);
        $this->adminRow($this->adminUser, ['title' => 'New Ticket', 'type' => 'new_ticket']);

        $response = $this->poll();

        $response->assertOk();
        $response->assertJsonCount(0, 'notifications');
        $response->assertJsonPath('unread_count', 3);
        $this->assertNotEmpty($response->json('next_since'));
    }

    public function test_a_poll_carries_only_what_arrived_after_the_cursor(): void
    {
        $this->adminRow($this->adminUser, [
            'title' => 'Older Order',
            'created_at' => now()->subMinutes(10),
        ]);
        $fresh = $this->adminRow($this->adminUser, ['title' => 'Newer Order']);

        $response = $this->poll(now()->subMinute()->toIso8601String());

        $response->assertOk();
        $response->assertJsonCount(1, 'notifications');
        $response->assertJsonPath('notifications.0.uuid', $fresh->uuid);
        $response->assertJsonPath('notifications.0.title', 'Newer Order');
    }

    /**
     * The scoping the audience column and the user_id both exist for. A poll is
     * just another way to ask, so it has to answer the same way the page does.
     */
    public function test_a_poll_never_carries_another_admins_notifications(): void
    {
        $other = $this->admin('Other', 'Admin');
        $this->adminRow($other, ['title' => 'Not Yours', 'content' => 'Enquiry from Ravi']);
        $mine = $this->adminRow($this->adminUser, ['title' => 'Mine']);

        $response = $this->poll(now()->subMinute()->toIso8601String());

        $response->assertOk();
        $response->assertJsonCount(1, 'notifications');
        $response->assertJsonPath('notifications.0.uuid', $mine->uuid);
        $response->assertJsonPath('unread_count', 1);
        $response->assertDontSee('Not Yours');
    }

    public function test_a_poll_never_carries_the_admins_own_customer_notifications(): void
    {
        // Same users row, both audiences: an admin who also shops.
        $this->customerRow($this->adminUser);
        $mine = $this->adminRow($this->adminUser);

        $response = $this->poll(now()->subMinute()->toIso8601String());

        $response->assertOk();
        $response->assertJsonCount(1, 'notifications');
        $response->assertJsonPath('notifications.0.uuid', $mine->uuid);
        $response->assertJsonPath('unread_count', 1);
    }

    /**
     * Fetched is not read. Reading stays where it was - on
     * admin.notifications.read and the mark-all form - or the bell would empty
     * itself just by being watched.
     */
    public function test_polling_leaves_every_notification_unread(): void
    {
        $row = $this->adminRow($this->adminUser);

        $this->poll(now()->subMinute()->toIso8601String())->assertOk();
        $this->poll(now()->subMinute()->toIso8601String())->assertOk();

        $this->assertFalse($row->refresh()->is_read);
        $this->assertNull($row->read_at);
        $this->assertSame(1, Notification::query()->forAdmin()->unread()->count());
    }

    public function test_the_unread_count_follows_the_rows_that_are_still_unread(): void
    {
        $read = $this->adminRow($this->adminUser, ['title' => 'Already Seen']);
        $this->adminRow($this->adminUser, ['title' => 'Still Waiting']);
        $read->markAsRead();

        $this->poll()->assertOk()->assertJsonPath('unread_count', 1);
    }

    /**
     * The endpoint sits behind the admin guard, and a browser reaching it with
     * Accept: application/json gets a status rather than the login page's HTML -
     * which is what lets the poller tell "signed out" apart from "offline".
     */
    public function test_a_signed_out_request_is_refused(): void
    {
        $this->getJson(route('admin.notifications.updates'))->assertUnauthorized();
    }

    public function test_a_signed_in_shopper_cannot_poll_the_admin_endpoint(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->customerRow($customer);

        // Signed in on the storefront guard, which is not the admin guard.
        $this->actingAs($customer)
            ->getJson(route('admin.notifications.updates'))
            ->assertUnauthorized();
    }

    /**
     * A cursor is a query-string value, so it is whatever the caller says it is.
     * Left unclamped, `since=1970-01-01` would walk the admin's entire history
     * and hand back a screenful of years-old toasts.
     */
    public function test_a_cursor_from_the_far_past_is_pulled_up_to_the_lookback_window(): void
    {
        $this->adminRow($this->adminUser, [
            'title' => 'Ancient History',
            'created_at' => now()->subDays(3),
        ]);
        $recent = $this->adminRow($this->adminUser, ['title' => 'This Morning']);

        $response = $this->poll('1970-01-01T00:00:00+00:00');

        $response->assertOk();
        $response->assertJsonCount(1, 'notifications');
        $response->assertJsonPath('notifications.0.uuid', $recent->uuid);
    }

    public function test_an_unparseable_cursor_is_treated_as_no_cursor_at_all(): void
    {
        $this->adminRow($this->adminUser);

        $response = $this->poll('not-a-timestamp');

        $response->assertOk();
        $response->assertJsonCount(0, 'notifications');
        $response->assertJsonPath('unread_count', 1);
    }

    /**
     * The poll runs six times a minute for as long as an admin is signed in, so
     * one answer is capped - and it is capped at the NEWEST rows. A tab that
     * slept through a busy morning should wake to what just happened, not to
     * the oldest thing still inside the window.
     */
    public function test_one_answer_is_capped_and_carries_the_newest_notifications(): void
    {
        $created = [];
        for ($i = 1; $i <= 30; $i++) {
            $created[] = $this->adminRow($this->adminUser, ['title' => "Order {$i}"]);
        }

        $response = $this->poll(now()->subMinute()->toIso8601String());

        $response->assertOk();
        $response->assertJsonCount(25, 'notifications');

        $returned = collect($response->json('notifications'))->pluck('uuid')->all();
        $newest = collect($created)->slice(5)->pluck('uuid')->values()->all();

        // Oldest-first within the answer, so the client can draw them in the
        // order they happened.
        $this->assertSame($newest, $returned);
    }

    /**
     * What the client needs to tell one notification from another and to open
     * it. The uuid is the identity - two enquiries can carry identical wording,
     * so the text can never be it - and the url comes from the notification's
     * own data through the read route, not from matching on that wording.
     */
    public function test_each_notification_carries_its_identity_and_its_destination(): void
    {
        $row = $this->adminRow($this->adminUser, [
            'type' => 'new_enquiry',
            'title' => 'New Enquiry',
            'content' => 'Enquiry from Ravi about bulk pricing',
        ]);

        $response = $this->poll(now()->subMinute()->toIso8601String());

        $response->assertOk();
        $response->assertJsonStructure([
            'notifications' => [
                ['id', 'uuid', 'type', 'title', 'content', 'channel', 'is_read', 'created_at', 'created_at_for_humans', 'url'],
            ],
            'unread_count',
            'next_since',
        ]);
        $response->assertJsonPath('notifications.0.uuid', $row->uuid);
        $response->assertJsonPath('notifications.0.type', 'new_enquiry');
        $response->assertJsonPath('notifications.0.is_read', false);
        $response->assertJsonPath('notifications.0.url', route('admin.notifications.read', $row));
    }

    /**
     * The cursor is handed back rewound a few seconds. A notification written
     * inside a slow transaction can be timestamped just before the poll that
     * misses it and only become visible afterwards; the overlap is what gives
     * the next poll a chance to see it, and the client's uuid set is what stops
     * the overlap announcing anything twice.
     */
    public function test_the_cursor_handed_back_overlaps_the_window_just_polled(): void
    {
        $before = now();

        $nextSince = Carbon::parse($this->poll()->assertOk()->json('next_since'));

        $this->assertTrue(
            $nextSince->lessThanOrEqualTo($before),
            'The cursor handed back must sit behind the moment of the poll, not in front of it.'
        );
        $this->assertTrue($nextSince->greaterThan($before->copy()->subMinute()));
    }

    /**
     * When more arrives than one answer carries, the answer says so. The cursor
     * moves to now regardless, so the rows past the cap are not delivered
     * anywhere - they are on the notifications page, and the unread count still
     * includes them. Without the flag the client would announce "25 new" when
     * forty had landed.
     */
    public function test_an_answer_that_hit_the_cap_says_so(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->adminRow($this->adminUser, ['title' => "Order {$i}"]);
        }

        $this->poll(now()->subMinute()->toIso8601String())
            ->assertOk()
            ->assertJsonPath('truncated', true)
            ->assertJsonPath('unread_count', 30);
    }

    public function test_an_answer_within_the_cap_is_not_marked_truncated(): void
    {
        $this->adminRow($this->adminUser);

        $this->poll(now()->subMinute()->toIso8601String())
            ->assertOk()
            ->assertJsonPath('truncated', false);
    }

    /**
     * The count is taken after the rows, so an answer can never carry a row the
     * count has not seen. The other order left the bell reading one behind the
     * list it had just been given.
     */
    public function test_the_count_is_never_behind_the_rows_in_the_same_answer(): void
    {
        $this->adminRow($this->adminUser, ['title' => 'One']);
        $this->adminRow($this->adminUser, ['title' => 'Two']);

        $response = $this->poll(now()->subMinute()->toIso8601String())->assertOk();

        $this->assertGreaterThanOrEqual(
            count($response->json('notifications')),
            $response->json('unread_count')
        );
    }

    /**
     * The poll is the one admin route a browser calls by itself, so it carries a
     * ceiling. A named limiter, not `throttle:60,1`: an unnamed one's bucket key
     * is the user id alone, which would put the poll in the same counter as the
     * storefront's checkout and coupon limits for an admin who also shops.
     */
    public function test_the_poll_is_rate_limited_under_its_own_named_limiter(): void
    {
        $middleware = collect(app('router')->getRoutes()->getByName('admin.notifications.updates')->gatherMiddleware());

        $this->assertTrue(
            $middleware->contains('throttle:admin-notification-poll'),
            'The polling route must use the admin-notification-poll limiter.'
        );
        $this->assertFalse(
            $middleware->contains(fn ($m) => is_string($m) && preg_match('/^throttle:\d/', $m)),
            'An unnamed throttle shares one bucket per user across every route that uses one.'
        );
    }

    /**
     * The other half of the baseline: the poller seeds its already-seen set off
     * the rows the page has already drawn, so every rendered row has to carry
     * the same identifier the endpoint sends back.
     */
    public function test_the_rendered_bell_tags_its_rows_with_the_uuid_the_poller_dedupes_on(): void
    {
        $row = $this->adminRow($this->adminUser);

        $response = $this->actingAs($this->adminUser, 'admin')->get(route('admin.notifications'));

        $response->assertOk();
        $response->assertSee('data-notification-uuid="' . $row->uuid . '"', false);
    }

    /**
     * One timer for the admin session. The poller lives in the shell, so no page
     * can start a second one and navigating cannot leave one behind.
     */
    public function test_the_admin_shell_carries_exactly_one_poller(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')->get(route('admin.notifications'));

        $response->assertOk();
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'id="admin-notification-poller"'),
            'The admin shell must mount the notification poller exactly once.'
        );
        $response->assertSee('data-endpoint="' . route('admin.notifications.updates') . '"', false);
    }
}
