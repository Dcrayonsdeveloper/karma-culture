<?php

namespace Tests\Feature\Notification;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);
    }

    public function test_notification_preferences_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/account/notification-preferences');

        $response->assertStatus(200);
    }

    public function test_notification_preferences_can_be_updated(): void
    {
        $response = $this->actingAs($this->user)
            ->put('/account/notification-preferences', [
                'email_order_placed' => true,
                'email_order_shipped' => false,
                'email_price_drop' => true,
                'in_app_order_placed' => true,
                'in_app_order_shipped' => true,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_notifications_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/account/notifications');

        $response->assertStatus(200);
    }

    public function test_in_app_notifications_are_displayed(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'order_placed',
            'title' => 'Order Confirmed',
            'content' => 'Your order has been confirmed.',
            'channel' => 'database',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/account/notifications');

        $response->assertStatus(200);
    }

    public function test_notification_preferences_require_authentication(): void
    {
        $response = $this->get('/account/notification-preferences');

        $response->assertRedirect('/login');
    }
}
