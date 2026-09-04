<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('admin')->user();

            // Only admin/staff accounts may use the admin panel - reject others
            // instead of creating a session that hits a 403 on every page.
            if (! $user->isAdmin() && ! $user->isStaff()) {
                Auth::guard('admin')->logout();

                return back()->withErrors([
                    'email' => 'This account does not have admin access.',
                ])->onlyInput('email');
            }

            // A deactivated account must not be able to sign in to the panel
            // either. The admin guard shares the users provider with `web`, so
            // without this the deactivate toggle stopped nobody here.
            if (! $user->is_active) {
                Auth::guard('admin')->logout();

                return back()->withErrors([
                    'email' => 'This account has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
