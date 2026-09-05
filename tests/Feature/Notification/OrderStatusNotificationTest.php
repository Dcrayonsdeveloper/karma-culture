<?php

namespace Tests\Feature\Notification;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nobody was told anything between "order placed" and "delivered".
 *
 * OrderStatusChanged had a listener and no dispatcher - nothing in the app ever
 * fired it - so the one notification it carried (order cancelled) could not
 * happen, and no other status carried one at all. A customer whose order was
 * confirmed, packed, made ready for pickup or cancelled heard nothing, and the
 * shop was told about none of it either.
 *
 * A customer cancelling their own order also bypassed Order::updateStatus()
 * with a bare update, so it wrote no status history, stamped no cancelled_at
 * and settled no payment status.
 */
class OrderStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function order(string $status = 'confirmed'): Order
    {
        return Order::create([
            'user_id' => $this->customer->id,
            'order_number' => 'KK-'.uniqid(),
            'status' => $status,
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 1000,
        ]);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function customerStatuses(): array
    {
        return [
            'confirmed' => ['confirmed', 'Order Confirmed'],
            'packed' => ['packed', 'Order Packed'],
            'out for delivery' => ['out_for_delivery', 'Out For Delivery'],
            'cancelled' => ['cancelled', 'Order Cancelled'],
            'returned' => ['returned', 'Return Received'],
            'on hold' => ['on_hold', 'Order On Hold'],
        ];
    }

    /** @dataProvider customerStatuses */
    public function test_the_customer_is_told_about_each_status(string $status, string $title): void
    {
        $order = $this->order('processing');

        $order->updateStatus($status);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'order_'.$status,
            'title' => $title,
            'audience' => Notification::AUDIENCE_CUSTOMER,
        ]);
    }

    /**
     * Shipped and delivered have their own events and their own mail, so a
     * second announcement here would tell the customer twice.
     */
    public function test_shipped_is_left_to_its_own_event(): void
    {
        $order = $this->order('packed');

        $order->updateStatus('shipped');

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'order_shipped',
        ]);
    }

    public function test_the_shop_hears_about_a_cancellation(): void
    {
        $order = $this->order('processing');

        $order->updateStatus('cancelled');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->adminUser->id,
            'type' => 'order_cancelled',
            'audience' => Notification::AUDIENCE_ADMIN,
        ]);
    }

    /**
     * A status that has not changed is not news.
     */
    public function test_setting_the_same_status_again_notifies_nobody(): void
    {
        $order = $this->order('processing');

        $order->updateStatus('processing');

        $this->assertSame(0, Notification::where('type', 'order_processing')->count());
    }

    /**
     * A guest order has no account to notify, but the shop still has to hear
     * about the cancellation.
     */
    public function test_a_guest_order_still_alerts_the_shop(): void
    {
        $order = Order::create([
            'user_id' => null,
            'order_number' => 'KK-GUEST-1',
            'status' => 'processing',
            'payment_status' => 'paid',
            'subtotal' => 500, 'discount' => 0, 'shipping_cost' => 0, 'tax' => 0, 'total' => 500,
        ]);

        $order->updateStatus('cancelled');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->adminUser->id,
            'type' => 'order_cancelled',
        ]);
    }

    /**
     * The customer's own cancel button went through a bare update, so it wrote
     * no history and stamped no cancelled_at.
     */
    public function test_a_customer_cancelling_goes_through_the_same_funnel(): void
    {
        $order = $this->order('confirmed');

        $this->actingAs($this->customer)
            ->post(route('account.orders.cancel', $order), ['reason' => 'Changed my mind']);

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at, 'updateStatus() stamps this; a bare update did not.');
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->adminUser->id,
            'type' => 'order_cancelled',
        ]);
    }
}
