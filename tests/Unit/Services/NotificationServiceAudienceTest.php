<?php

namespace Tests\Unit\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who a written notification is addressed to, and whether it should be written
 * at all.
 *
 * notifyInApp() used to insert unconditionally, and SendOrderNotification
 * calls it directly for order_cancelled and return_<status> rather than going
 * through notify() - so those two ignored the switch the customer had turned
 * off on the preferences screen. notifyAdmins() is new: it is what puts a row
 * on the admin side of the bell, and it deliberately does not consult customer
 * preferences, because a shopper muting order_placed must not silence the
 * store's new-order alerts.
 */
class NotificationServiceAudienceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NotificationService();
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    public function test_notify_in_app_respects_a_switched_off_preference(): void
    {
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'preferences' => ['in_app_order_cancelled' => false],
        ]);

        $this->service->notifyInApp(
            $this->user,
            'order_cancelled',
            'Order Cancelled',
            'Your order KK-1001 has been cancelled.',
            ['order_id' => 1]
        );

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'type' => 'order_cancelled',
        ]);
    }

    public function test_notify_in_app_still_writes_a_type_the_customer_left_on(): void
    {
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'preferences' => ['in_app_order_cancelled' => false],
        ]);

        $this->service->notifyInApp(
            $this->user,
            'order_shipped',
            'Order Shipped',
            'Your order KK-1001 has been shipped.'
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'order_shipped',
            'audience' => Notification::AUDIENCE_CUSTOMER,
        ]);
    }

    public function test_notify_admins_writes_one_admin_row_per_admin_and_none_for_customers(): void
    {
        $firstAdmin = User::factory()->create(['role' => 'admin']);
        $secondAdmin = User::factory()->create(['role' => 'admin']);

        $notified = $this->service->notifyAdmins(
            'new_order',
            'New Order',
            'Order KK-1001 placed by Asha Menon',
            ['order_id' => 7]
        );

        $this->assertEquals(2, $notified);

        foreach ([$firstAdmin, $secondAdmin] as $admin) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $admin->id,
                'type' => 'new_order',
                'audience' => Notification::AUDIENCE_ADMIN,
                'channel' => 'database',
            ]);
        }

        $this->assertDatabaseMissing('notifications', ['user_id' => $this->user->id]);
        $this->assertEquals(0, Notification::query()->forCustomer()->count());
        $this->assertEquals(2, Notification::query()->forAdmin()->count());
    }

    /**
     * The admin types have no preference UI of their own, so consulting the
     * customer toggles here would let a shopping admin mute the store's alerts.
     */
    public function test_notify_admins_ignores_customer_preferences(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        NotificationPreference::create([
            'user_id' => $admin->id,
            'preferences' => ['in_app_new_order' => false, 'in_app_order_placed' => false],
        ]);

        $this->assertEquals(1, $this->service->notifyAdmins('new_order', 'New Order', 'Order KK-1001 placed.'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
        ]);
    }

    public function test_notify_admins_is_safe_when_the_store_has_no_admins(): void
    {
        $this->assertEquals(0, $this->service->notifyAdmins('new_order', 'New Order', 'Order KK-1001 placed.'));
        $this->assertEquals(0, Notification::query()->count());
    }

    public function test_notify_writes_the_customer_audience(): void
    {
        $this->service->notify($this->user, 'order_placed', [
            'title' => 'Order Confirmed',
            'content' => 'Your order KK-1001 has been confirmed.',
            'order_id' => 1,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'order_placed',
            'audience' => Notification::AUDIENCE_CUSTOMER,
        ]);
    }
}
