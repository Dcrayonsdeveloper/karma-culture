<?php

namespace Tests\Feature\Notification;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The customer side of the bell.
 *
 * The account area had an index() and nothing else: there was no read action
 * of any kind on the website, so every notification a customer had ever
 * received stayed unread for good and the read/unread styling the list is
 * built around never fired. The route exists now, and because an admin is a
 * users row with role = 'admin', it has to refuse both a stranger's row and
 * the admin-audience rows the same users row can own.
 */
class CustomerNotificationReadTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    private function customerRow(User $user, array $attributes = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => 'price_drop',
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'title' => 'Price Drop',
            'content' => 'A product on your wishlist is cheaper today.',
            'channel' => 'database',
            'is_read' => false,
        ], $attributes));
    }

    public function test_a_customer_can_mark_their_own_notification_read(): void
    {
        $notification = $this->customerRow($this->customer);

        $this->actingAs($this->customer)
            ->get(route('account.notifications.read', $notification))
            ->assertRedirect(route('account.notifications'));

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    public function test_a_customer_cannot_mark_somebody_elses_notification_read(): void
    {
        $stranger = User::factory()->create(['role' => 'customer']);
        $notification = $this->customerRow($stranger);

        $this->actingAs($this->customer)
            ->get(route('account.notifications.read', $notification))
            ->assertForbidden();

        $notification->refresh();
        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    /**
     * The mirror of the admin bell bug: the store's own new-order alerts are
     * written against the admin's user_id, so without the audience filter they
     * would surface on that admin's personal account page.
     */
    public function test_the_account_list_leaves_the_admin_audience_alone(): void
    {
        $adminUser = User::factory()->create(['role' => 'admin']);

        Admin::create([
            'user_id' => $adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Notification::create([
            'user_id' => $adminUser->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'title' => 'New Order',
            'content' => 'Order KK-4004 placed by Asha Menon',
            'channel' => 'database',
            'is_read' => false,
        ]);

        $ownRow = $this->customerRow($adminUser, [
            'type' => 'order_placed',
            'title' => 'Order Confirmed',
            'content' => 'Your order KK-5005 has been confirmed.',
        ]);

        $response = $this->actingAs($adminUser)->get(route('account.notifications'));

        $response->assertOk();
        $response->assertSee('Your order KK-5005 has been confirmed.');
        $response->assertDontSee('Order KK-4004 placed by Asha Menon');
        $response->assertViewHas(
            'notifications',
            fn ($notifications) => $notifications->pluck('id')->all() === [$ownRow->id]
        );
        $response->assertViewHas('unreadCount', 1);
    }

    public function test_an_admin_audience_row_cannot_be_cleared_from_the_account_page(): void
    {
        $adminUser = User::factory()->create(['role' => 'admin']);

        $notification = Notification::create([
            'user_id' => $adminUser->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'title' => 'New Order',
            'content' => 'Order KK-6006 placed by Asha Menon',
            'channel' => 'database',
            'is_read' => false,
        ]);

        $this->actingAs($adminUser)
            ->get(route('account.notifications.read', $notification))
            ->assertForbidden();

        $notification->refresh();
        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    public function test_mark_all_as_read_only_touches_the_customers_own_unread_rows(): void
    {
        $mine = $this->customerRow($this->customer);
        $stranger = User::factory()->create(['role' => 'customer']);
        $theirs = $this->customerRow($stranger);

        $this->actingAs($this->customer)
            ->from(route('account.notifications'))
            ->post(route('account.notifications.read-all'))
            ->assertRedirect(route('account.notifications'));

        $mine->refresh();
        $this->assertTrue($mine->is_read);
        $this->assertNotNull($mine->read_at);

        $this->assertFalse($theirs->refresh()->is_read);
    }
}
