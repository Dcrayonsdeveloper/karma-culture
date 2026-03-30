<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\FraudDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFraudCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(FraudDetectionService $fraudService): void
    {
        try {
            $result = $fraudService->assessOrder($this->order);

            if ($result['action'] === 'blocked') {
                $this->order->update(['status' => 'on_hold']);
                Log::warning('Order blocked by async fraud check', [
                    'order_id' => $this->order->id,
                    'score' => $result['score'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Async fraud check failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
