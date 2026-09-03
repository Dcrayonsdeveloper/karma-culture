<?php

namespace Tests\Feature\Notification;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
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
