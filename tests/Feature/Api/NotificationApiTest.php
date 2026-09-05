<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The mobile/API side of the bell.
 *
 * markAsRead() here used to be update(['read_at' => now()]), which left
 * is_read false - and Notification::scopeUnread() and every badge and list
 * highlight in the product key off is_read, so reading a notification through
 * the API changed nothing the customer could see. markAllAsRead() then
 * selected on whereNull('read_at'), so it could not even repair those
 * half-updated rows: they already carried a read_at and were skipped.
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    private function customerRow(User $user, array $attributes = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => 'order_shipped',
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'title' => 'Order Shipped',
            'content' => 'Your order KK-7007 has been shipped.',
            'channel' => 'database',
            'is_read' => false,
        ], $attributes));
    }

    private function asUser(?string $token = null): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.($token ?? $this->token),
        ]);
    }

    public function test_marking_as_read_writes_both_columns_and_clears_the_unread_scope(): void
    {
        $notification = $this->customerRow($this->user);

        $this->asUser()
            ->putJson('/api/v1/notifications/'.$notification->id.'/read')
            ->assertStatus(200);

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);

        $this->assertFalse(
            Notification::query()->unread()->whereKey($notification->id)->exists()
        );
    }

    public function test_marking_all_as_read_repairs_a_half_read_row(): void
    {
        // Exactly the state the old markAsRead() left behind: a read_at with
        // is_read still false, which the old whereNull('read_at') skipped.
        $notification = $this->customerRow($this->user, [
            'is_read' => false,
            'read_at' => now(),
        ]);

        $this->asUser()
            ->putJson('/api/v1/notifications/read-all')
            ->assertStatus(200);

        $this->assertTrue($notification->refresh()->is_read);
    }

    public function test_a_user_cannot_mark_somebody_elses_notification_read(): void
    {
        $stranger = User::factory()->create(['role' => 'customer']);
        $notification = $this->customerRow($stranger);

        $this->asUser()
            ->putJson('/api/v1/notifications/'.$notification->id.'/read')
            ->assertStatus(403);

        $notification->refresh();
        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    public function test_the_listing_returns_only_the_customer_audience(): void
    {
        $this->customerRow($this->user);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'title' => 'New Order',
            'content' => 'Order KK-8008 placed by Asha Menon',
            'channel' => 'database',
            'is_read' => false,
        ]);

        $response = $this->asUser()->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'order_shipped');
    }
}
