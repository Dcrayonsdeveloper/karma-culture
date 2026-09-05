<?php

return [
    App\Providers\AppServiceProvider::class,

    // Laravel 12 does not auto-discover EventServiceProvider the way the older
    // skeletons did - a provider only runs if it is listed here. Without this
    // line the entire $listen map in App\Providers\EventServiceProvider is
    // never registered, so OrderPlaced, OrderStatusChanged, OrderShipped,
    // OrderDelivered, ReturnRequested and RefundProcessed all dispatch into the
    // void: no order confirmation, shipping, delivery, cancellation,
    // return-approved or refund notification or email was ever created, and the
    // fraud check, order analytics, recommendation update and review invitation
    // listeners never ran either.
    App\Providers\EventServiceProvider::class,
];
