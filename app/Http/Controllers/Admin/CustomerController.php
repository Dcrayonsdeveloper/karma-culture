<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            // max:50, not the old max:255: users.first_name and users.last_name
            // are both varchar(50), so the previous rule waved through a name
            // twice the width of the column it was about to be written into.
            'first_name' => V::name(max: 50),

            // Optional, matching sign-up and the customer's own profile form.
            // RegisterController splits one "full name" field on the first
            // space, so an account created as "dev" has last_name = ''. Marking
            // this required meant staff could not save this form at all for
            // such a customer - not even to correct a phone number - without
            // inventing a surname and writing it onto the account.
            'last_name' => V::name(required: false, max: 50),

            // email:strict, matching registration. Plain 'email' is
            // RFC-permissive and accepts "dev@gmail" with no TLD, so the old
            // rule let staff change an address to one the customer could never
            // have signed up with - and could not then use to sign in.
            'email' => [
                ...V::email(),
                Rule::unique('users', 'email')->ignore($customer->id),
            ],

            // The old 'nullable|string|max:20' accepted anything twenty
            // characters wide and stored it verbatim, so this form was the way
            // "78657 86785" got into a column that holds bare ten-digit
            // numbers everywhere else - and the way a second account could end
            // up on a number already in use, since nothing checked uniqueness
            // on the canonical form here.
            'phone' => [
                ...V::mobile(required: false),
                function (string $attribute, mixed $value, Closure $fail) use ($customer): void {
                    $normalized = IndianMobile::normalize(is_scalar($value) ? (string) $value : null);

                    if ($normalized !== null
                        && User::where('phone', $normalized)->whereKeyNot($customer->id)->exists()) {
                        $fail('An account with this mobile number already exists.');
                    }
                },
            ],

            'is_active' => 'boolean',
        ], [
            // The admin layout loads app.js, so the site-wide validator runs on
            // this form too and names an empty box after its own <label>: "First
            // Name is required.", "Email is required." These are those labels.
            'first_name.required' => 'First Name is required.',
            'email.required' => 'Email is required.',
            // The example address was the only "name@example.com" in the
            // codebase; every other place that teaches the shape of an address -
            // app.js's own _EMAIL_GENERIC, the storefront profile form, the
            // contact form, registration - says "you@example.com". One example,
            // everywhere, or the two look like two different rules.
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.unique' => 'An account already exists for this email address.',
        ]);

        // Built field by field rather than mass-assigning $validated, so the
        // phone lands in the column canonicalised.
        $customer->update([
            'first_name' => $validated['first_name'],
            // The column is NOT NULL, and sign-up writes '' for a single-word
            // name; keep that shape rather than introducing nulls.
            'last_name' => $validated['last_name'] ?? '',
            'email' => $validated['email'],
            // Store the bare ten digits, not whatever spacing was typed.
            'phone' => IndianMobile::normalize($validated['phone'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

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
