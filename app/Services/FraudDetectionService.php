<?php

namespace App\Services;

use App\Models\FraudLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FraudDetectionService
{
    private const THRESHOLD_FLAG = 70;
    private const THRESHOLD_BLOCK = 90;

    public function assessOrder(Order $order): array
    {
        $checks = [
            'velocity' => $this->velocityCheck($order),
            'order_value' => $this->orderValueCheck($order),
            'new_account' => $this->newAccountCheck($order),
            'address_mismatch' => $this->addressMismatchCheck($order),
            'payment_failure' => $this->paymentFailureCheck($order),
            'ip_reputation' => $this->ipReputationCheck($order),
            'duplicate_order' => $this->duplicateOrderCheck($order),
        ];

        $totalScore = collect($checks)->sum('score');
        $maxPossible = collect($checks)->sum('max');
        $normalizedScore = $maxPossible > 0 ? round(($totalScore / $maxPossible) * 100, 2) : 0;

        $action = 'allowed';
        if ($normalizedScore >= self::THRESHOLD_BLOCK) {
            $action = 'blocked';
        } elseif ($normalizedScore >= self::THRESHOLD_FLAG) {
            $action = 'flagged';
        }

        $fraudLog = FraudLog::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => $this->determineFraudType($checks),
            'risk_score' => $normalizedScore,
            'indicators' => $checks,
            'action' => $action,
        ]);

        return [
            'score' => $normalizedScore,
            'action' => $action,
            'checks' => $checks,
            'fraud_log_id' => $fraudLog->id,
        ];
    }

    private function velocityCheck(Order $order): array
    {
        $recentOrders = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        $score = 0;
        if ($recentOrders >= 5) {
            $score = 20;
        } elseif ($recentOrders >= 3) {
            $score = 10;
        } elseif ($recentOrders >= 2) {
            $score = 5;
        }

        return [
            'score' => $score,
            'max' => 20,
            'detail' => "{$recentOrders} orders in last hour",
        ];
    }

    private function orderValueCheck(Order $order): array
    {
        $avgOrderValue = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->where('payment_status', 'paid')
            ->avg('total') ?? 0;

        $score = 0;
        if ($avgOrderValue > 0 && $order->total > $avgOrderValue * 5) {
            $score = 15;
        } elseif ($avgOrderValue > 0 && $order->total > $avgOrderValue * 3) {
            $score = 8;
        } elseif ($order->total > 50000) {
            $score = 5;
        }

        return [
            'score' => $score,
            'max' => 15,
            'detail' => "Order: {$order->total}, Avg: {$avgOrderValue}",
        ];
    }

    private function newAccountCheck(Order $order): array
    {
        $user = $order->user;
        if (! $user) {
            return ['score' => 10, 'max' => 15, 'detail' => 'Guest order'];
        }

        $accountAge = $user->created_at->diffInHours(now());
        $score = 0;

        if ($accountAge < 1 && $order->total > 5000) {
            $score = 15;
        } elseif ($accountAge < 24 && $order->total > 10000) {
            $score = 10;
        } elseif ($accountAge < 48) {
            $score = 3;
        }

        return [
            'score' => $score,
            'max' => 15,
            'detail' => "Account age: {$accountAge}h, Order: {$order->total}",
        ];
    }

    private function addressMismatchCheck(Order $order): array
    {
        $shipping = $order->shipping_address_snapshot ?? [];
        $billing = $order->billing_address_snapshot ?? [];

        $score = 0;

        if (! empty($shipping) && ! empty($billing)) {
            $shipCity = strtolower($shipping['city'] ?? '');
            $billCity = strtolower($billing['city'] ?? '');
            $shipState = strtolower($shipping['state'] ?? '');
            $billState = strtolower($billing['state'] ?? '');

            if ($shipState !== $billState && $shipState && $billState) {
                $score = 10;
            } elseif ($shipCity !== $billCity && $shipCity && $billCity) {
                $score = 5;
            }
        }

        return [
            'score' => $score,
            'max' => 10,
            'detail' => $score > 0 ? 'Address mismatch detected' : 'Addresses match',
        ];
    }

    private function paymentFailureCheck(Order $order): array
    {
        $failedAttempts = DB::table('payments')
            ->where('order_id', $order->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Also check other orders by this user
        $userOrderIds = Order::where('user_id', $order->user_id)->pluck('id');
        $failedAttempts = DB::table('payments')
            ->whereIn('order_id', $userOrderIds)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $score = 0;
        if ($failedAttempts >= 5) {
            $score = 20;
        } elseif ($failedAttempts >= 3) {
            $score = 12;
        } elseif ($failedAttempts >= 1) {
            $score = 3;
        }

        return [
            'score' => $score,
            'max' => 20,
            'detail' => "{$failedAttempts} failed payments in 24h",
        ];
    }

    private function ipReputationCheck(Order $order): array
    {
        $ip = $order->ip_address ?? null;
        if (! $ip) {
            return ['score' => 0, 'max' => 10, 'detail' => 'No IP recorded'];
        }

        $accountsFromIp = User::where('last_login_ip', $ip)
            ->where('id', '!=', $order->user_id)
            ->count();

        $score = 0;
        if ($accountsFromIp >= 5) {
            $score = 10;
        } elseif ($accountsFromIp >= 3) {
            $score = 5;
        }

        return [
            'score' => $score,
            'max' => 10,
            'detail' => "{$accountsFromIp} other accounts from same IP",
        ];
    }

    private function duplicateOrderCheck(Order $order): array
    {
        $duplicates = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->where('total', $order->total)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();

        $score = 0;
        if ($duplicates >= 2) {
            $score = 10;
        } elseif ($duplicates >= 1) {
            $score = 5;
        }

        return [
            'score' => $score,
            'max' => 10,
            'detail' => "{$duplicates} duplicate orders in 30min",
        ];
    }

    private function determineFraudType(array $checks): string
    {
        $maxCheck = collect($checks)->sortByDesc('score')->keys()->first();

        return match ($maxCheck) {
            'velocity', 'duplicate_order' => 'unusual_activity',
            'payment_failure' => 'suspicious_payment',
            'ip_reputation' => 'multiple_accounts',
            default => 'unusual_activity',
        };
    }
}
