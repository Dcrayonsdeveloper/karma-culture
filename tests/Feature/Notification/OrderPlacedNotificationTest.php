<?php

namespace Tests\Feature\Notification;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmation;
use App\Models\Admin;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Providers\EventServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The root cause, and the regression test for it.
 *
 * App\Providers\EventServiceProvider was never listed in bootstrap/providers.php,
 * and Laravel 12 does not auto-discover it the way the older skeletons did - a
 * provider only runs if it is listed there. The whole $listen map was therefore
 * dead: OrderPlaced, OrderStatusChanged, OrderShipped, OrderDelivered,
 * ReturnRequested and RefundProcessed dispatched into the void, so no order
 * confirmation, shipping, delivery, cancellation, return-approved or refund
 * notification or email had ever been created by an event.
 *
 * This dispatches a real OrderPlaced rather than asserting on the listener map,
 * because the listener map was never the thing that was broken - the wiring
 * that makes it run was.
 */
class OrderPlacedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_event_service_provider_is_registered(): void
    {
        $this->assertNotEmpty(
            $this->app->getProviders(EventServiceProvider::class),
            'App\Providers\EventServiceProvider must be listed in bootstrap/providers.php, '
            .'or none of its $listen map is ever registered.'
        );
    }

    public function test_placing_an_order_notifies_the_customer_and_every_admin(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $admin->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'first_name' => 'Asha',
            'last_name' => 'Menon',
            'role' => 'customer',
        ]);

        $order = $this->order($customer);

        OrderPlaced::dispatch($order);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'order_placed',
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'channel' => 'database',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
            'channel' => 'database',
        ]);

        // assertSent, not assertQueued. Mail::fake() records both, so this
        // assertion passed for months while NotificationService pushed the
        // confirmation onto a database queue that has no worker and never had
        // one - the mail was queued, the test was green, and the customer got
        // nothing. Asserting the send is what ties the test to delivery.
        Mail::assertSent(OrderConfirmation::class);
        Mail::assertNothingQueued();
    }

    /**
     * A guest order has no account to confirm to, but it is still an order the
     * shop has to hear about - which is why the admin alert sits above the
     * listener's early return for a missing user.
     */
    public function test_a_guest_order_still_reaches_the_admins(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        OrderPlaced::dispatch($this->order(null));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'new_order',
            'audience' => Notification::AUDIENCE_ADMIN,
        ]);

        $this->assertEquals(0, Notification::query()->forCustomer()->count());
    }

    private function order(?User $customer): Order
    {
        return Order::create([
            'user_id' => $customer?->id,
            'status' => 'pending',
            'payment_status' => 'paid',
            'subtotal' => 1200,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 1200,
            'paid_amount' => 1200,
            'shipping_address_snapshot' => [
                'name' => 'Asha Menon',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
            ],
            'source' => 'web',
        ]);
    }
}
