<?php

namespace Tests\Feature\Notification;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The bell shows the latest notifications; the badge counts the unread ones.
 *
 * The dropdown used to list unread rows only, so opening a notification took it
 * off the bell and the bell fell back to whatever was still unread underneath
 * it. On a busy morning that meant the newest order sat at the top of
 * /admin/notifications while the bell showed cancellations from ten minutes
 * earlier - which reads as the bell being broken, not as the newest ones
 * having been read.
 */
class AdminBellShowsLatestTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function row(string $title, bool $read, int $minutesAgo): Notification
    {
        $notification = Notification::create([
            'user_id' => $this->adminUser->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'title' => $title,
            'content' => $title.' body',
            'channel' => 'database',
            'is_read' => $read,
            'read_at' => $read ? now() : null,
        ]);

        // created_at is set behind the model: "latest" is only meaningful if the
        // rows are minutes apart rather than all written in the same second.
        $notification->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->save();

        return $notification;
    }

    public function test_the_bell_shows_a_read_notification_that_is_newer_than_the_unread_ones(): void
    {
        $this->row('Older Cancellation', false, 30);
        $this->row('Newest Order', true, 1);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Newest Order')
            ->assertSee('Older Cancellation');
    }

    /** The badge still counts only what has not been read. */
    public function test_the_badge_counts_unread_only(): void
    {
        $this->row('Read One', true, 5);
        $this->row('Unread One', false, 4);
        $this->row('Unread Two', false, 3);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('2 new')
            ->assertSee('Notifications, 2 unread', false);
    }

    /** Five, newest first - the bell is a peek, not the list. */
    public function test_the_bell_stops_at_five(): void
    {
        foreach (range(1, 7) as $i) {
            $this->row("Order Number {$i}", false, 20 - $i);
        }

        $response = $this->actingAs($this->adminUser, 'admin')->get(route('admin.dashboard'))->assertOk();

        // 7 is the newest (20 - 7 minutes ago), 3 is the fifth.
        foreach ([7, 6, 5, 4, 3] as $shown) {
            $response->assertSee("Order Number {$shown}");
        }
        foreach ([2, 1] as $hidden) {
            $response->assertDontSee("Order Number {$hidden}");
        }
    }

    /** An admin still never sees another admin's rows, or their own shopping. */
    public function test_the_bell_is_still_this_admins_admin_rows_only(): void
    {
        $other = User::factory()->create(['role' => 'admin']);
        Notification::create([
            'user_id' => $other->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'title' => 'Another Admins Order',
            'content' => 'body',
            'channel' => 'database',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $this->adminUser->id,
            'type' => 'order_placed',
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'title' => 'My Own Shopping',
            'content' => 'body',
            'channel' => 'database',
            'is_read' => false,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Another Admins Order')
            ->assertDontSee('My Own Shopping');
    }
}
