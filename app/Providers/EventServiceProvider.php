<?php

namespace App\Providers;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Events\PosSaleCompleted;
use App\Events\RefundProcessed;
use App\Events\ReturnRequested;
use App\Listeners\CheckOrderFraud;
use App\Listeners\SendOrderNotification;
use App\Listeners\SendReviewInvitationAfterDelivery;
use App\Listeners\TrackOrderAnalytics;
use App\Listeners\UpdateRecommendationData;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            [CheckOrderFraud::class, 'handle'],
            [SendOrderNotification::class, 'handleOrderPlaced'],
            [UpdateRecommendationData::class, 'handleOrderPlaced'],
        ],
        OrderStatusChanged::class => [
            [SendOrderNotification::class, 'handleOrderStatusChanged'],
        ],
        OrderShipped::class => [
            [SendOrderNotification::class, 'handleOrderShipped'],
        ],
        OrderDelivered::class => [
            [SendOrderNotification::class, 'handleOrderDelivered'],
            TrackOrderAnalytics::class,
            SendReviewInvitationAfterDelivery::class,
        ],
        ReturnRequested::class => [
            [SendOrderNotification::class, 'handleReturnRequested'],
        ],
        RefundProcessed::class => [
            [SendOrderNotification::class, 'handleRefundProcessed'],
        ],
        PosSaleCompleted::class => [
            [UpdateRecommendationData::class, 'handlePosSaleCompleted'],
        ],
    ];
}
