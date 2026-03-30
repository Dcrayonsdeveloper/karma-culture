<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FraudController extends Controller
{
    public function index(Request $request): View
    {
        $query = FraudLog::with(['user', 'order', 'reviewer']);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('reviewed')) {
            if ($request->reviewed === 'yes') {
                $query->whereNotNull('reviewed_by');
            } else {
                $query->whereNull('reviewed_by');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%"))
                  ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$search}%"));
            });
        }

        $fraudLogs = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => FraudLog::count(),
            'flagged' => FraudLog::where('action', 'flagged')->count(),
            'blocked' => FraudLog::where('action', 'blocked')->count(),
            'unreviewed' => FraudLog::whereNull('reviewed_by')->where('action', '!=', 'allowed')->count(),
        ];

        return view('admin.fraud.index', compact('fraudLogs', 'stats'));
    }

    public function show(FraudLog $fraudLog): View
    {
        $fraudLog->load(['user', 'order.items.product', 'reviewer']);

        $userHistory = FraudLog::where('user_id', $fraudLog->user_id)
            ->where('id', '!=', $fraudLog->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.fraud.show', compact('fraudLog', 'userHistory'));
    }

    public function review(Request $request, FraudLog $fraudLog): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:allowed,flagged,blocked',
            'notes' => 'nullable|string|max:1000',
        ]);

        $fraudLog->update([
            'action' => $validated['action'],
            'reviewed_by' => auth('admin')->id(),
            'notes' => $validated['notes'],
        ]);

        // If allowing a blocked order, restore its status
        if ($validated['action'] === 'allowed' && $fraudLog->order && $fraudLog->order->status === 'on_hold') {
            $fraudLog->order->update(['status' => 'confirmed']);
        }

        // If blocking, put order on hold
        if ($validated['action'] === 'blocked' && $fraudLog->order && !in_array($fraudLog->order->status, ['cancelled', 'on_hold'])) {
            $fraudLog->order->update(['status' => 'on_hold']);
        }

        return back()->with('success', "Fraud case marked as {$validated['action']}");
    }
}
