<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\FraudDetectionService;
use Illuminate\Support\Facades\Log;

class CheckOrderFraud
{
    public function __construct(
        private FraudDetectionService $fraudService
    ) {}

    public function handle(OrderPlaced $event): void
    {
        try {
            $result = $this->fraudService->assessOrder($event->order);

            if ($result['action'] === 'blocked') {
                $event->order->update(['status' => 'on_hold']);
                Log::warning('Order blocked by fraud detection', [
                    'order_id' => $event->order->id,
                    'score' => $result['score'],
                ]);
            } elseif ($result['action'] === 'flagged') {
                Log::info('Order flagged for review', [
                    'order_id' => $event->order->id,
                    'score' => $result['score'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Fraud check failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
