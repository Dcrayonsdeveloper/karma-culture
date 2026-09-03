<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Events\RefundProcessed;
use App\Events\ReturnRequested;
use App\Mail\OrderConfirmation;
use App\Mail\OrderDelivered as OrderDeliveredMail;
use App\Mail\OrderShipped as OrderShippedMail;
use App\Mail\RefundProcessed as RefundProcessedMail;
use App\Mail\ReturnApproved;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendOrderNotification
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order;

        // A new order used to reach no admin at all. Both web and API checkout
        // dispatch OrderPlaced, so the shop's own alert belongs here rather
        // than in either controller: one implementation covers both, and it is
        // the only placement that cannot notify twice for one order.
        //
        // It runs before the customer's confirmation, and above the guest early
        // return below, because an order placed without an account is still an
        // order the shop has to hear about.
        //
        // Its failure is logged and dropped. Both controllers dispatch this
        // event after the order transaction has committed, so a notification
        // that throws cannot roll the order back - it would only turn a placed
        // order into a 500 in front of the customer, and cost them the
        // confirmation for the order they just paid for.
        try {
            $this->notificationService->notifyAdmins(
                'new_order',
                'New Order',
                "Order #{$order->order_number} placed for ".format_price($order->total).' by '.$this->customerName($order),
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => (float) $order->total,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify admins of a new order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        $user = $order->user;
        if (! $user) {
            return;
        }

        $this->notificationService->notify($user, 'order_placed', [
            'title' => 'Order Confirmed',
            'content' => "Your order #{$order->order_number} has been confirmed.",
            'order_id' => $order->id,
        ], new OrderConfirmation($order));
    }

    public function handleOrderShipped(OrderShipped $event): void
    {
        $order = $event->order;
        $user = $order->user;
        if (! $user) {
            return;
        }

        $this->notificationService->notify($user, 'order_shipped', [
            'title' => 'Order Shipped',
            'content' => "Your order #{$order->order_number} has been shipped.",
            'order_id' => $order->id,
            'tracking_number' => $event->trackingNumber,
        ], new OrderShippedMail($order, $event->trackingNumber));
    }

    public function handleOrderDelivered(OrderDelivered $event): void
    {
        $order = $event->order;
        $user = $order->user;
        if (! $user) {
            return;
        }

        $this->notificationService->notify($user, 'order_delivered', [
            'title' => 'Order Delivered',
            'content' => "Your order #{$order->order_number} has been delivered.",
            'order_id' => $order->id,
        ], new OrderDeliveredMail($order));
    }

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $user = $order->user;
        if (! $user) {
            return;
        }

        if ($event->newStatus === 'cancelled') {
            $this->notificationService->notifyInApp($user, 'order_cancelled',
                'Order Cancelled',
                "Your order #{$order->order_number} has been cancelled.",
                ['order_id' => $order->id]
            );
        }
    }

    public function handleReturnRequested(ReturnRequested $event): void
    {
        $return = $event->return;
        $user = $return->order?->user;
        if (! $user) {
            return;
        }

        if ($return->status === 'approved') {
            $this->notificationService->notify($user, 'return_approved', [
                'title' => 'Return Approved',
                'content' => "Your return request #{$return->return_number} has been approved.",
                'return_id' => $return->id,
            ], new ReturnApproved($return));
        } else {
            $this->notificationService->notifyInApp($user, 'return_' . $return->status,
                'Return Update',
                "Your return request #{$return->return_number} status: {$return->status}.",
                ['return_id' => $return->id]
            );
        }
    }

    public function handleRefundProcessed(RefundProcessed $event): void
    {
        $return = $event->return;
        $user = $return->order?->user;
        if (! $user) {
            return;
        }

        $this->notificationService->notify($user, 'refund_processed', [
            'title' => 'Refund Processed',
            'content' => 'Your refund of ' . format_price($event->amount) . ' has been processed.',
            'return_id' => $return->id,
            'amount' => $event->amount,
        ], new RefundProcessedMail($return, $event->amount));
    }

    /**
     * Who to name in the admin alert for an order.
     *
     * Guest checkout leaves user_id null, so the account is not always there to
     * ask. The address snapshot taken at checkout always is, and it carries the
     * same name the order screens already show.
     */
    private function customerName(Order $order): string
    {
        return trim((string) ($order->user?->full_name ?? ''))
            ?: trim((string) ($order->shipping_address_snapshot['name'] ?? ''))
            ?: 'Guest';
    }
}
