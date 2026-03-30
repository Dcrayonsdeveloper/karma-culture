<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $request->input('per_page', 10);

        $query = Coupon::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'active' => $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    }),
                'expired' => $query->where('expires_at', '<', now()),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        $coupons = $query->latest()->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'expired' => Coupon::where('expires_at', '<', now())->count(),
            'auto_apply' => Coupon::where('auto_apply', true)->where('is_active', true)->count(),
        ];

        return view('admin.coupons.index', compact('coupons', 'stats'));
    }

    public function create(): View
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        return view('admin.coupons.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'code' => 'required|string|max:50|unique:coupons',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_shipping,buy_x_get_y',
            'value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'auto_apply' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
        ];

        if ($request->input('type') === 'buy_x_get_y') {
            $rules['conditions.buy_qty'] = 'required|integer|min:1';
            $rules['conditions.get_qty'] = 'required|integer|min:1';
        }

        $validated = $request->validate($rules);

        // Ensure boolean defaults
        $validated['is_active'] = $request->boolean('is_active');
        $validated['auto_apply'] = $request->boolean('auto_apply');

        // Build conditions for BOGO
        if ($request->input('type') === 'buy_x_get_y') {
            $validated['conditions'] = [
                'buy_qty' => (int) $request->input('conditions.buy_qty'),
                'get_qty' => (int) $request->input('conditions.get_qty'),
            ];
        } else {
            $validated['conditions'] = null;
        }

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully');
    }

    public function edit(Coupon $coupon): View
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        return view('admin.coupons.edit', compact('coupon', 'categories'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $rules = [
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_shipping,buy_x_get_y',
            'value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'auto_apply' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
        ];

        if ($request->input('type') === 'buy_x_get_y') {
            $rules['conditions.buy_qty'] = 'required|integer|min:1';
            $rules['conditions.get_qty'] = 'required|integer|min:1';
        }

        $validated = $request->validate($rules);

        // Ensure boolean defaults
        $validated['is_active'] = $request->boolean('is_active');
        $validated['auto_apply'] = $request->boolean('auto_apply');

        // Build conditions for BOGO
        if ($request->input('type') === 'buy_x_get_y') {
            $validated['conditions'] = [
                'buy_qty' => (int) $request->input('conditions.buy_qty'),
                'get_qty' => (int) $request->input('conditions.get_qty'),
            ];
        } else {
            $validated['conditions'] = null;
        }

        // Clear arrays if not sent (unchecked)
        if (!$request->has('applicable_products')) {
            $validated['applicable_products'] = null;
        }
        if (!$request->has('applicable_categories')) {
            $validated['applicable_categories'] = null;
        }

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully');
    }
}
