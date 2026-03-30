<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
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
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
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
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email,' . $staff->user_id,
            'password' => 'nullable|min:8|confirmed',
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
