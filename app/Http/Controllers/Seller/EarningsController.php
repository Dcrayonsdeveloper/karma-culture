<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(Request $request): View
    {
        $seller = $request->user()->seller;

        // Earnings stats
        $totalEarnings = $this->calculateEarnings($seller, null, null);
        $pendingEarnings = $this->calculateEarnings($seller, null, null, 'pending');
        $availableBalance = $seller->available_balance ?? 0;
        $totalPaidOut = Payout::where('seller_id', $seller->id)
            ->where('status', 'completed')
            ->sum('amount');

        // Monthly earnings (last 6 months)
        $monthlyEarnings = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $earnings = $this->calculateEarnings(
                $seller,
                $month->startOfMonth()->toDateString(),
                $month->endOfMonth()->toDateString()
            );
            $monthlyEarnings->push([
                'month' => $month->format('M Y'),
                'earnings' => $earnings,
            ]);
        }

        // Recent transactions
        $recentOrders = Order::whereHas('items.product', fn ($q) => $q->where('seller_id', $seller->id))
            ->where('payment_status', 'paid')
            ->with(['items' => fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('seller_id', $seller->id))])
            ->latest()
            ->take(10)
            ->get();

        return view('seller.earnings.index', compact(
            'totalEarnings',
            'pendingEarnings',
            'availableBalance',
            'totalPaidOut',
            'monthlyEarnings',
            'recentOrders',
            'seller'
        ));
    }

    private function calculateEarnings($seller, $from = null, $to = null, $paymentStatus = 'paid')
    {
        $query = Order::whereHas('items.product', fn ($q) => $q->where('seller_id', $seller->id))
            ->where('payment_status', $paymentStatus)
            ->with(['items' => fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('seller_id', $seller->id))]);

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->get();

        $total = 0;
        foreach ($orders as $order) {
            $total += $order->items->sum('total');
        }

        // Apply commission rate (e.g., 15% to platform)
        $commissionRate = $seller->commission_rate ?? 15;
        return $total * (1 - $commissionRate / 100);
    }
}
