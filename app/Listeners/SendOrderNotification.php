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
}
