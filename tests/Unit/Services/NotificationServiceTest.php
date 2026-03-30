<?php

namespace Tests\Unit\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
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

    public function test_notify_in_app_creates_notification(): void
    {
        $this->service->notifyInApp(
            $this->user,
            'order_placed',
            'Order Confirmed',
            'Your order ORD-001 has been confirmed.',
            ['order_id' => 1]
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'order_placed',
            'title' => 'Order Confirmed',
            'channel' => 'database',
        ]);
    }

    public function test_notify_creates_in_app_notification_by_default(): void
    {
        $this->service->notify($this->user, 'order_placed', [
            'title' => 'Order Placed',
            'content' => 'Your order has been placed successfully.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'order_placed',
        ]);
    }

    public function test_get_default_preferences(): void
    {
        $defaults = $this->service->getDefaultPreferences();

        $this->assertArrayHasKey('email_order_placed', $defaults);
        $this->assertArrayHasKey('email_order_shipped', $defaults);
        $this->assertArrayHasKey('in_app_order_placed', $defaults);
        $this->assertArrayHasKey('in_app_order_shipped', $defaults);
        $this->assertTrue($defaults['email_order_placed']);
        $this->assertTrue($defaults['in_app_order_placed']);
    }

    public function test_get_user_preferences_returns_defaults_when_none_set(): void
    {
        $preferences = $this->service->getUserPreferences($this->user->id);

        $this->assertTrue($preferences->get('email_order_placed'));
        $this->assertTrue($preferences->get('in_app_order_shipped'));
    }

    public function test_get_user_preferences_returns_custom_preferences(): void
    {
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'preferences' => [
                'email_order_placed' => false,
                'email_price_drop' => false,
                'in_app_order_placed' => true,
            ],
        ]);

        $preferences = $this->service->getUserPreferences($this->user->id);

        $this->assertFalse($preferences->get('email_order_placed'));
        $this->assertFalse($preferences->get('email_price_drop'));
        $this->assertTrue($preferences->get('in_app_order_placed'));
    }

    public function test_update_preferences_creates_record(): void
    {
        $newPrefs = [
            'email_order_placed' => true,
            'email_order_shipped' => false,
            'email_price_drop' => false,
            'in_app_order_placed' => true,
            'in_app_order_shipped' => true,
        ];

        $result = $this->service->updatePreferences($this->user->id, $newPrefs);

        $this->assertInstanceOf(NotificationPreference::class, $result);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_update_preferences_updates_existing_record(): void
    {
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'preferences' => ['email_order_placed' => true],
        ]);

        $this->service->updatePreferences($this->user->id, [
            'email_order_placed' => false,
        ]);

        $pref = NotificationPreference::where('user_id', $this->user->id)->first();
        $this->assertFalse($pref->preferences['email_order_placed']);
    }

    public function test_bulk_notify_sends_to_multiple_users(): void
    {
        $user2 = User::factory()->create(['role' => 'customer']);
        $user3 = User::factory()->create(['role' => 'customer']);

        $sent = $this->service->bulkNotify(
            [$this->user->id, $user2->id, $user3->id],
            'price_drop',
            [
                'title' => 'Price Drop Alert',
                'content' => 'A product on your wishlist has dropped in price!',
            ]
        );

        $this->assertEquals(3, $sent);
        $this->assertEquals(3, Notification::where('type', 'price_drop')->count());
    }

    public function test_bulk_notify_skips_invalid_user_ids(): void
    {
        $sent = $this->service->bulkNotify(
            [$this->user->id, 999999],
            'back_in_stock',
            [
                'title' => 'Back in Stock',
                'content' => 'A product is back in stock!',
            ]
        );

        $this->assertEquals(1, $sent);
    }
}
