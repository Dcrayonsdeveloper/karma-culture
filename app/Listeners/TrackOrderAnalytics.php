<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Services\AnalyticsService;

class TrackOrderAnalytics
{
    public function __construct(private AnalyticsService $analytics) {}

    public function handle(OrderDelivered $event): void
    {
        $event->order->load('items', 'user');
        $this->analytics->trackPurchase($event->order);
    }
}
