<?php

namespace Tests\Feature\Notification;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin bell and the admin notifications list.
 *
 * One notifications table serves both bells and config/auth.php gives the
 * `admin` guard the same `users` provider as `web`, so an admin is simply a
 * users row with role = 'admin'. Rows were keyed by user_id alone, which had
 * two consequences the audience column now fixes:
 *
 *  - an admin who also shops saw their own "Your order has been confirmed"
 *    sitting in the admin bell beside the store's new-order alerts;
 *  - the list itself was unfiltered, so it printed every customer's order
 *    numbers and ticket subjects to whichever admin opened it.
 *
 * The read route was equally open: it is a GET, so LogAdminActions skips it,
 * and route model binding resolves any id in the table - a hand-typed
 * /admin/notifications/{id}/read cleared a stranger's unread state silently.
 */
class AdminNotificationAudienceTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->admin('Bell', 'Admin');
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
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'title' => 'New Order',
            'content' => 'Order KK-1001 placed by Asha Menon',
            'channel' => 'database',
            'is_read' => false,
        ], $attributes));
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

    /**
     * The reported bug. Both rows belong to the same users row, so only the
     * audience can tell them apart. The header partial renders on this page
     * too, so the same response covers the bell and the list at once.
     */
    public function test_the_admin_page_never_shows_the_admins_own_customer_notifications(): void
    {
        $adminRow = $this->adminRow($this->adminUser);
        $this->customerRow($this->adminUser);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.notifications'));

        $response->assertOk();
        $response->assertSee('Order KK-1001 placed by Asha Menon');
        $response->assertDontSee('Your order KK-2002 has been confirmed.');
        $response->assertViewHas(
            'notifications',
            fn ($notifications) => $notifications->pluck('id')->all() === [$adminRow->id]
        );
        $response->assertViewHas('unreadCount', 1);
    }

    public function test_the_admin_page_does_not_leak_another_users_notifications(): void
    {
        $otherAdmin = $this->admin('Other', 'Admin');
        $this->adminRow($otherAdmin, [
            'type' => 'new_enquiry',
            'title' => 'New Enquiry',
            'content' => 'Enquiry from Ravi about bulk pricing',
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $this->customerRow($customer, ['content' => 'Your order KK-3003 has been shipped.']);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.notifications'));

        $response->assertOk();
        $response->assertDontSee('Enquiry from Ravi about bulk pricing');
        $response->assertDontSee('Your order KK-3003 has been shipped.');
        $response->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 0);
        $response->assertViewHas('unreadCount', 0);
    }

    public function test_an_admin_cannot_mark_another_admins_notification_read(): void
    {
        $otherAdmin = $this->admin('Other', 'Admin');
        $notification = $this->adminRow($otherAdmin);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.notifications.read', $notification))
            ->assertForbidden();

        $notification->refresh();
        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    /**
     * Ownership on its own is not enough: this row is the admin's, but it
     * belongs to their shopping account and is only clearable from there.
     */
    public function test_an_admin_cannot_clear_their_own_customer_row_from_the_admin_bell(): void
    {
        $notification = $this->customerRow($this->adminUser);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.notifications.read', $notification))
            ->assertForbidden();

        $notification->refresh();
        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    public function test_an_admin_can_mark_their_own_admin_notification_read(): void
    {
        $notification = $this->adminRow($this->adminUser, ['data' => []]);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.notifications.read', $notification))
            ->assertRedirect(route('admin.notifications'));

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }
}
