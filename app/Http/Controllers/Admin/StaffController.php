<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $perPage = request()->input('per_page', 10);
        $staff = Staff::with('user')->latest()->paginate($perPage)->withQueryString();

        return view('admin.staff.index', compact('staff'));
    }

    public function create(): View
    {
        return view('admin.staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Was 'string|max:50', which let a staff name be saved as digits or
            // symbol soup. These write users.first_name/last_name - the same two
            // varchar(50) columns admin/customers and the customer's own profile
            // form already guard with V::name - so the rule matches those, and
            // matches the keystroke filter now on the two boxes in the blade.
            'first_name' => V::name(max: 50),
            'last_name' => V::name(max: 50),
            'email' => 'required|email|unique:users,email',
            // Was 'required|min:8|confirmed' - eight characters of anything -
            // while every other form on the site that mints a password goes
            // through V::password(). A staff row is a real login to the admin
            // panel, so it is held to the same policy as a customer's: ten
            // characters with mixed case, a number and a symbol.
            'password' => [...V::password(), 'max:255'],
            'role' => 'required|in:manager,cashier,support,warehouse',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:dashboard,orders,catalog,customers,sellers,staff,marketing,storefront,content,reports,settings',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'staff',
        ]);

        $employeeId = 'EMP-' . str_pad(Staff::max('id') + 1, 4, '0', STR_PAD_LEFT);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member created');
    }

    public function edit(Staff $staff): View
    {
        $staff->load('user');

        return view('admin.staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $validated = $request->validate([
            // Was 'string|max:50', which let a staff name be saved as digits or
            // symbol soup. These write users.first_name/last_name - the same two
            // varchar(50) columns admin/customers and the customer's own profile
            // form already guard with V::name - so the rule matches those, and
            // matches the keystroke filter now on the two boxes in the blade.
            'first_name' => V::name(max: 50),
            'last_name' => V::name(max: 50),
            'email' => 'required|email|unique:users,email,' . $staff->user_id,
            // Optional here - the box says "Leave blank to keep current" - but
            // a password that IS typed meets the same policy as a new one.
            'password' => [...V::password(required: false), 'max:255'],
            'role' => 'required|in:manager,cashier,support,warehouse',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:dashboard,orders,catalog,customers,sellers,staff,marketing,storefront,content,reports,settings',
        ]);

        $staff->user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        if ($request->filled('password')) {
            $staff->user->update(['password' => Hash::make($validated['password'])]);
        }

        $staff->update([
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $staff->user->delete();
        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff member deleted');
    }
}
