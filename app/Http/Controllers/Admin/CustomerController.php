<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('role', 'customer')
            ->withCount('orders')
            ->withSum('orders', 'total');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = $request->input('per_page', 10);
        $customers = $query->latest()->paginate($perPage)->withQueryString();

        // Stats
        $stats = [
            'total' => User::where('role', 'customer')->count(),
            'active' => User::where('role', 'customer')->where('is_active', true)->count(),
            'new_this_month' => User::where('role', 'customer')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return view('admin.customers.index', compact('customers', 'stats'));
    }

    public function show(User $customer): View
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $customer->load(['orders.items', 'addresses', 'reviews']);

        $stats = [
            'total_orders' => $customer->orders->count(),
            'total_spent' => $customer->orders->sum('total'),
            'avg_order_value' => $customer->orders->count() > 0
                ? $customer->orders->sum('total') / $customer->orders->count()
                : 0,
        ];

        $recentOrders = $customer->orders()->with('items')->latest()->take(10)->get();

        return view('admin.customers.show', compact('customer', 'stats', 'recentOrders'));
    }

    public function edit(User $customer): View
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $customer->update($validated);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function toggleStatus(User $customer): RedirectResponse
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $customer->update(['is_active' => !$customer->is_active]);

        $status = $customer->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Customer account {$status}.");
    }

    public function orders(User $customer): View
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $perPage = request()->input('per_page', 10);
        $orders = $customer->orders()
            ->with('items')
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.customers.orders', compact('customer', 'orders'));
    }
}
