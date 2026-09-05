<?php

namespace App\Providers;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
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
    ];

    /**
     * Leave the verification listener to the framework's own provider.
     *
     * Laravel registers Illuminate's EventServiceProvider as well as this one -
     * that is the same doubling `->withEvents(discover: false)` in
     * bootstrap/app.php was added to stop, except discovery is only half of it.
     * The parent's boot() also runs configureEmailVerification(), which hooks
     * SendEmailVerificationNotification onto Registered whenever the provider's
     * own $listen map does not mention it. Neither map does, so both instances
     * hooked it up and every signup sent the customer two identical "verify
     * your email" messages.
     *
     * Under MAIL_MAILER=log that was two lines in a file nobody read. It is two
     * real emails now.
     *
     * Silenced here rather than in bootstrap/app.php: dropping the
     * ->withEvents() call removes the framework instance's discovery guard and
     * puts six listeners back on OrderPlaced, and declaring Registered in the
     * map above only satisfies this instance's guard - the framework's map is
     * still empty, so it would still register its own. Standing this one down
     * and leaving the framework's is the only arrangement that ends up with
     * exactly one.
     */
    protected function configureEmailVerification(): void
    {
        //
    }
}
