<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth('admin')->user();
        $admin = $user->admin;

        return view('admin.profile.edit', compact('user', 'admin'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth('admin')->user();

        $validated = $request->validate([
            // Was 'string|max:255' - both wider than the varchar(50) columns
            // being written, and with no charset at all. V::name(max: 50)
            // matches the column and the same PersonName charset every other
            // name box uses, including the box in this very form.
            'first_name' => V::name(max: 50),
            'last_name' => V::name(max: 50),
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'nullable|required_with:password',
            // Was 'nullable|min:8|confirmed'. An admin's own password is the
            // most valuable one on the site and was the least constrained of
            // any; V::password() is the site-wide policy every other form
            // already applies.
            'password' => [...V::password(required: false), 'max:255'],
        ]);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];

        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect']);
            }
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully');
    }
}
