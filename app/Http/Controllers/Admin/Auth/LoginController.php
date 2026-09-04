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
        ], [
            // Without these three the admin sign-in was the one auth screen that
            // answered in Laravel's framework voice - "The email field is
            // required." - while the browser side, which loads app.js and has no
            // `novalidate` on the form, named the same empty box after its own
            // <label>: "Email address is required." Same rule, same field, two
            // wordings, and both are reachable in one attempt because a value of
            // spaces passes the native `required` and is only rejected once
            // Laravel trims it. The sentences below are the ones app.js prints,
            // so the field says the same thing whichever side rejected it.
            'email.required' => 'Email address is required.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'password.required' => 'Password is required.',
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
