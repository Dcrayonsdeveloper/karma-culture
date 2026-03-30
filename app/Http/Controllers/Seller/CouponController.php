<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $coupons = Coupon::where('seller_id', $request->user()->seller->id)
            ->latest()
            ->paginate(20);

        return view('seller.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('seller.coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons',
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $validated['seller_id'] = $request->user()->seller->id;

        Coupon::create($validated);

        return redirect()->route('seller.coupons.index')->with('success', 'Coupon created');
    }

    public function edit(Request $request, Coupon $coupon): View
    {
        if ($coupon->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        return view('seller.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        if ($coupon->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $coupon->update($validated);

        return redirect()->route('seller.coupons.index')->with('success', 'Coupon updated');
    }

    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        if ($coupon->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        $coupon->delete();

        return redirect()->route('seller.coupons.index')->with('success', 'Coupon deleted');
    }
}
