<?php

namespace Tests\Feature\Notification;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * One event, one run of each listener.
 *
 * Every notification arrived twice - two "New Order" rows in the admin bell and
 * two in the customer's, two again on cancellation. Laravel registers its own
 * EventServiceProvider alongside ours, and that one auto-discovers every
 * handle* method in app/Listeners, so each listener was hooked up twice: once
 * as [Class::class, 'method'] from our $listen map and once as "Class@method"
 * from discovery. OrderPlaced carried six listeners where the map declares
 * three.
 *
 * It was never only the notifications: the fraud check ran twice on every
 * order, the analytics counted each delivery twice, and a delivered order sent
 * two review invitations.
 */
class SingleNotificationPerEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The map in App\Providers\EventServiceProvider is the whole story, so the
     * dispatcher must hold exactly what it declares and nothing more.
     */
    public function test_no_listener_is_registered_twice(): void
    {
        foreach ([
            OrderPlaced::class => 3,
            OrderStatusChanged::class => 1,
            OrderShipped::class => 1,
            OrderDelivered::class => 3,
        ] as $event => $declared) {
            $this->assertCount(
                $declared,
                Event::getListeners($event),
                class_basename($event).' has listeners the $listen map does not declare - discovery is on again.'
            );
        }
    }

    /**
     * The half of the doubling that turning discovery off did not reach.
     *
     * Registered is not in our $listen map, so this was never about discovery:
     * the parent provider's configureEmailVerification() hooks the verification
     * listener on whenever the map is silent about it, and both provider
     * instances ran that. App\Providers\EventServiceProvider now stands its own
     * copy down and leaves the framework's, so exactly one remains.
     */
    public function test_the_verification_listener_is_registered_once(): void
    {
        $this->assertCount(
            1,
            Event::getListeners(Registered::class),
            'Registration hooks SendEmailVerificationNotification more than once - every signup mails the customer twice.'
        );
    }

    /**
     * The symptom, end to end - and it has changed shape.
     *
     * When this was written, signup created an unverified account and mailed it
     * a link, and the bug was that it mailed TWO. Signup now proves the address
     * before the account exists, so the account is created verified and the
     * framework's listener - which is guarded on ! hasVerifiedEmail() - stands
     * itself down. The right number is therefore zero, and asserting it keeps
     * the original guarantee intact from the other side: if the listener were
     * ever hooked up twice again, or if signup stopped recording the address as
     * verified, this would go red.
     *
     * The listener registration itself is still pinned by the test above, which
     * is the half of this that discovery could break.
     */
    public function test_registering_does_not_re_ask_for_an_address_already_proved(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        \App\Models\SignupEmailVerification::create([
            'email' => 'asha.menon@example.com',
            'token_hash' => \App\Models\SignupEmailVerification::hashToken(\Illuminate\Support\Str::random(64)),
            'expires_at' => now()->addDay(),
            'verified_at' => now(),
            'last_sent_at' => now()->subMinutes(5),
            'send_count' => 1,
        ]);

        $this->post(route('register'), [
            'full_name' => 'Asha Menon',
            'email' => 'asha.menon@example.com',
            'phone' => '9876543210',
            'password' => 'Correct-Horse-14',
            'password_confirmation' => 'Correct-Horse-14',
            'terms' => 'on',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'asha.menon@example.com')->firstOrFail();

        $this->assertNotNull($user->email_verified_at, 'A signup that proved its address created an unverified account.');
        \Illuminate\Support\Facades\Notification::assertSentToTimes($user, VerifyEmail::class, 0);
    }

    /** The symptom, end to end: one order, one row per person. */
    public function test_a_placed_order_notifies_each_admin_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $order = Order::create([
            'order_number' => 'KK-DUP-1',
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'subtotal' => 799,
            'total' => 799,
        ]);

        OrderPlaced::dispatch($order, 'web');

        $this->assertSame(
            1,
            \App\Models\Notification::where('user_id', $admin->id)->where('type', 'new_order')->count(),
            'The admin bell showed the same order twice.'
        );
    }

    /** And the same for a cancellation, which was doubled in both panels. */
    public function test_a_cancellation_notifies_each_side_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $order = Order::create([
            'order_number' => 'KK-DUP-2',
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'subtotal' => 500,
            'total' => 500,
        ]);

        $order->updateStatus('cancelled', null, 'Cancelled by the customer');

        $this->assertSame(
            1,
            \App\Models\Notification::where('user_id', $admin->id)->where('type', 'order_cancelled')->count(),
            'The admin bell showed the cancellation twice.'
        );
        $this->assertSame(
            1,
            \App\Models\Notification::where('user_id', $customer->id)->where('type', 'order_cancelled')->count(),
            'The customer was told twice that their order was cancelled.'
        );
    }
}
