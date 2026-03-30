<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Seller::with(['user', 'products']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->input('per_page', 10);
        $sellers = $query->latest()->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Seller::count(),
            'approved' => Seller::where('status', 'approved')->count(),
            'pending' => Seller::where('status', 'pending')->count(),
            'suspended' => Seller::where('status', 'suspended')->count(),
        ];

        return view('admin.sellers.index', compact('sellers', 'stats'));
    }

    public function pending(Request $request): View
    {
        $perPage = $request->input('per_page', 10);
        $sellers = Seller::with('user')
            ->where('status', 'pending')
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.sellers.pending', compact('sellers'));
    }

    public function show(Seller $seller): View
    {
        $seller->load(['user', 'products']);

        $stats = [
            'total_products' => $seller->products()->count(),
            'active_products' => $seller->products()->where('is_active', true)->count(),
            'total_orders' => $seller->orders()->count(),
            'total_revenue' => $seller->orders()->where('payment_status', 'paid')->sum('total'),
            'pending_payouts' => Payout::where('seller_id', $seller->id)->where('status', 'pending')->sum('amount'),
        ];

        $recentProducts = $seller->products()->latest()->take(5)->get();
        $recentPayouts = Payout::where('seller_id', $seller->id)->latest()->take(5)->get();

        return view('admin.sellers.show', compact('seller', 'stats', 'recentProducts', 'recentPayouts'));
    }

    public function update(Request $request, Seller $seller): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:pending,approved,suspended,rejected',
        ]);

        $seller->update($validated);

        return back()->with('success', 'Seller updated successfully.');
    }

    public function approve(Request $request, Seller $seller): RedirectResponse
    {
        $seller->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // TODO: Send approval notification email

        return back()->with('success', 'Seller has been approved.');
    }

    public function reject(Request $request, Seller $seller): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $seller->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        // TODO: Send rejection notification email

        return back()->with('success', 'Seller has been rejected.');
    }

    public function suspend(Request $request, Seller $seller): RedirectResponse
    {
        $request->validate([
            'suspension_reason' => 'required|string|max:1000',
        ]);

        $seller->update([
            'status' => 'suspended',
            'suspension_reason' => $request->input('suspension_reason'),
            'suspended_at' => now(),
        ]);

        // TODO: Send suspension notification email

        return back()->with('success', 'Seller has been suspended.');
    }

    public function products(Seller $seller): View
    {
        $perPage = request()->input('per_page', 10);
        $products = Product::where('seller_id', $seller->id)
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.sellers.products', compact('seller', 'products'));
    }

    public function payouts(Seller $seller): View
    {
        $perPage = request()->input('per_page', 10);
        $payouts = Payout::where('seller_id', $seller->id)
            ->latest()
            ->paginate($perPage)->withQueryString();

        $stats = [
            'pending' => Payout::where('seller_id', $seller->id)->where('status', 'pending')->sum('amount'),
            'processing' => Payout::where('seller_id', $seller->id)->where('status', 'processing')->sum('amount'),
            'completed' => Payout::where('seller_id', $seller->id)->where('status', 'completed')->sum('amount'),
        ];

        return view('admin.sellers.payouts', compact('seller', 'payouts', 'stats'));
    }
}
