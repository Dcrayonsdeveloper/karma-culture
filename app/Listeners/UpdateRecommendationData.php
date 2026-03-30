<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\PosSaleCompleted;
use App\Services\RecommendationService;

class UpdateRecommendationData
{
    public function __construct(
        private RecommendationService $recommendationService
    ) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order;
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $this->recommendationService->clearCacheForProduct($item->product_id);
        }
    }

    public function handlePosSaleCompleted(PosSaleCompleted $event): void
    {
        $sale = $event->sale;
        $sale->loadMissing('items');

        foreach ($sale->items as $item) {
            $this->recommendationService->clearCacheForProduct($item->product_id);
        }
    }
}
