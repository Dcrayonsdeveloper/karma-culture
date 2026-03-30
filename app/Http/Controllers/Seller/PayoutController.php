<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(Request $request): View
    {
        $seller = $request->user()->seller;

        $payouts = Payout::where('seller_id', $seller->id)
            ->latest()
            ->paginate(20);

        $stats = [
            'pending' => Payout::where('seller_id', $seller->id)->where('status', 'pending')->sum('amount'),
            'processing' => Payout::where('seller_id', $seller->id)->where('status', 'processing')->sum('amount'),
            'completed' => Payout::where('seller_id', $seller->id)->where('status', 'completed')->sum('amount'),
        ];

        $availableBalance = $seller->available_balance ?? 0;

        return view('seller.payouts.index', compact('payouts', 'stats', 'availableBalance', 'seller'));
    }

    public function create(Request $request): View
    {
        $seller = $request->user()->seller;
        $availableBalance = $seller->available_balance ?? 0;

        return view('seller.payouts.create', compact('seller', 'availableBalance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $seller = $request->user()->seller;

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:' . ($seller->available_balance ?? 0),
            'payout_method' => 'required|in:bank_transfer,paypal',
        ]);

        // Check minimum payout
        if ($validated['amount'] < 10) {
            return back()->withErrors(['amount' => 'Minimum payout amount is $10.']);
        }

        // Check available balance
        if ($validated['amount'] > ($seller->available_balance ?? 0)) {
            return back()->withErrors(['amount' => 'Amount exceeds available balance.']);
        }

        Payout::create([
            'seller_id' => $seller->id,
            'amount' => $validated['amount'],
            'payout_method' => $validated['payout_method'],
            'status' => 'pending',
        ]);

        // Deduct from available balance
        $seller->decrement('available_balance', $validated['amount']);

        return redirect()->route('seller.payouts.index')
            ->with('success', 'Payout request submitted successfully. It will be processed within 3-5 business days.');
    }

    public function show(Request $request, Payout $payout): View
    {
        $seller = $request->user()->seller;

        abort_if($payout->seller_id !== $seller->id, 403);

        return view('seller.payouts.show', compact('payout'));
    }
}
