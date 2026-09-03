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

    /**
     * What the customer is told at each step of fulfilment.
     *
     * Only cancellation used to be here, and the event that would have carried
     * it was never dispatched - so in practice a customer heard nothing between
     * placing an order and its delivery mail. Every status the shop actually
     * moves an order through now has a line.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const CUSTOMER_UPDATES = [
        'on_hold' => ['Order On Hold', 'is on hold - we will be in touch.'],
        'confirmed' => ['Order Confirmed', 'is confirmed and being prepared.'],
        'processing' => ['Order Being Prepared', 'is being prepared.'],
        'packed' => ['Order Packed', 'is packed and waiting to be collected by the carrier.'],
        'out_for_delivery' => ['Out For Delivery', 'is out for delivery today.'],
        'cancelled' => ['Order Cancelled', 'has been cancelled.'],
        'returned' => ['Return Received', 'has been marked returned.'],
    ];

    // No pickup line: orders.status is an enum of pending, on_hold, confirmed,
    // processing, packed, shipped, out_for_delivery, delivered, cancelled and
    // returned. There is no ready_for_pickup to notify about - adding in-store
    // collection means adding the status first, which is a schema change and a
    // fulfilment flow, not a notification.
    //
    // Refunds are not a status here either; they arrive as RefundProcessed and
    // are handled by handleRefundProcessed() below.

    /**
     * Statuses the shop needs to hear about, not just the customer.
     *
     * Placement already notifies admins from handleOrderPlaced. These are the
     * ones that happen afterwards and that somebody has to act on.
     */
    private const ADMIN_ALERTS = [
        'cancelled' => 'Order Cancelled',
        'returned' => 'Order Returned',
        'on_hold' => 'Order On Hold',
    ];

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $order = $event->order;

        // Shipped and delivered have their own events, with their own mail, so
        // announcing them here as well would tell the customer twice.
        if (in_array($event->newStatus, ['shipped', 'delivered'], true)) {
            return;
        }

        if ($alert = self::ADMIN_ALERTS[$event->newStatus] ?? null) {
            try {
                $this->notificationService->notifyAdmins(
                    'order_'.$event->newStatus,
                    $alert,
                    "Order #{$order->order_number} is now {$event->newStatus} (".$this->customerName($order).').',
                    ['order_id' => $order->id, 'order_number' => $order->order_number]
                );
            } catch (\Throwable $e) {
                Log::error('Failed to notify admins of an order status change', [
                    'order_id' => $order->id,
                    'status' => $event->newStatus,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $user = $order->user;
        if (! $user) {
            return;
        }

        if ($update = self::CUSTOMER_UPDATES[$event->newStatus] ?? null) {
            [$title, $sentence] = $update;

            $this->notificationService->notifyInApp(
                $user,
                'order_'.$event->newStatus,
                $title,
                "Your order #{$order->order_number} {$sentence}",
                ['order_id' => $order->id, 'order_number' => $order->order_number]
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
