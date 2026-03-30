<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    public function trackPurchase(Order $order): void
    {
        $this->sendGA4PurchaseEvent($order);
        $this->sendFBPurchaseEvent($order);
    }

    public function trackEvent(string $event, array $params = [], ?User $user = null): void
    {
        $this->sendGA4Event($event, $params, $user);
        $this->sendFBEvent($event, $params, $user);
    }

    private function sendGA4PurchaseEvent(Order $order): void
    {
        $measurementId = config('services.ga4.measurement_id');
        $apiSecret = config('services.ga4.api_secret');

        if (!$measurementId || !$apiSecret) {
            return;
        }

        $items = $order->items->map(fn ($item) => [
            'item_id' => $item->sku ?? (string) $item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->price,
            'quantity' => $item->quantity,
        ])->toArray();

        $payload = [
            'client_id' => 'server.' . $order->user_id,
            'events' => [[
                'name' => 'purchase',
                'params' => [
                    'transaction_id' => $order->order_number,
                    'value' => (float) $order->total,
                    'currency' => 'INR',
                    'tax' => (float) $order->tax,
                    'shipping' => (float) $order->shipping_cost,
                    'items' => $items,
                ],
            ]],
        ];

        try {
            Http::post("https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}", $payload);
        } catch (\Throwable $e) {
            Log::warning('GA4 Measurement Protocol failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendFBPurchaseEvent(Order $order): void
    {
        $pixelId = config('services.facebook.pixel_id');
        $accessToken = config('services.facebook.access_token');

        if (!$pixelId || !$accessToken) {
            return;
        }

        $user = $order->user;
        $contents = $order->items->map(fn ($item) => [
            'id' => (string) $item->product_id,
            'quantity' => $item->quantity,
            'item_price' => (float) $item->price,
        ])->toArray();

        $eventData = [
            'data' => [[
                'event_name' => 'Purchase',
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'event_source_url' => config('app.url'),
                'user_data' => [
                    'em' => [hash('sha256', strtolower(trim($user->email)))],
                    'ph' => $user->phone ? [hash('sha256', preg_replace('/\D/', '', $user->phone))] : [],
                    'fn' => [hash('sha256', strtolower(trim($user->first_name)))],
                    'ln' => [hash('sha256', strtolower(trim($user->last_name)))],
                    'country' => [hash('sha256', 'in')],
                ],
                'custom_data' => [
                    'currency' => 'INR',
                    'value' => (float) $order->total,
                    'content_type' => 'product',
                    'contents' => $contents,
                    'order_id' => $order->order_number,
                    'num_items' => $order->items->sum('quantity'),
                ],
            ]],
        ];

        $testCode = config('services.facebook.test_event_code');
        if ($testCode) {
            $eventData['test_event_code'] = $testCode;
        }

        try {
            Http::post("https://graph.facebook.com/v21.0/{$pixelId}/events?access_token={$accessToken}", $eventData);
        } catch (\Throwable $e) {
            Log::warning('Facebook CAPI failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendGA4Event(string $eventName, array $params, ?User $user): void
    {
        $measurementId = config('services.ga4.measurement_id');
        $apiSecret = config('services.ga4.api_secret');

        if (!$measurementId || !$apiSecret) {
            return;
        }

        $clientId = $user ? 'server.' . $user->id : 'server.' . uniqid();

        $payload = [
            'client_id' => $clientId,
            'events' => [[
                'name' => $eventName,
                'params' => $params,
            ]],
        ];

        try {
            Http::post("https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}", $payload);
        } catch (\Throwable $e) {
            Log::warning("GA4 event '{$eventName}' failed", ['error' => $e->getMessage()]);
        }
    }

    private function sendFBEvent(string $eventName, array $params, ?User $user): void
    {
        $pixelId = config('services.facebook.pixel_id');
        $accessToken = config('services.facebook.access_token');

        if (!$pixelId || !$accessToken) {
            return;
        }

        $userData = [];
        if ($user) {
            $userData = [
                'em' => [hash('sha256', strtolower(trim($user->email)))],
                'fn' => [hash('sha256', strtolower(trim($user->first_name)))],
                'ln' => [hash('sha256', strtolower(trim($user->last_name)))],
            ];
        }

        $eventData = [
            'data' => [[
                'event_name' => $eventName,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'event_source_url' => config('app.url'),
                'user_data' => $userData,
                'custom_data' => $params,
            ]],
        ];

        $testCode = config('services.facebook.test_event_code');
        if ($testCode) {
            $eventData['test_event_code'] = $testCode;
        }

        try {
            Http::post("https://graph.facebook.com/v21.0/{$pixelId}/events?access_token={$accessToken}", $eventData);
        } catch (\Throwable $e) {
            Log::warning("Facebook CAPI event '{$eventName}' failed", ['error' => $e->getMessage()]);
        }
    }
}
