<?php

namespace App\Http\Controllers\Delivery\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('delivery.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('delivery')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('delivery')->user();

            if (!$user->isDeliveryPartner()) {
                Auth::guard('delivery')->logout();
                return back()->withErrors([
                    'email' => 'This account is not a delivery partner.',
                ])->onlyInput('email');
            }

            if (!$user->deliveryPartner->is_active) {
                Auth::guard('delivery')->logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            // Redirect unverified partners to document upload page
            if (!$user->deliveryPartner->hasDocuments()) {
                return redirect()->route('delivery.documents');
            }

            if (!$user->deliveryPartner->isVerified()) {
                return redirect()->route('delivery.documents');
            }

            return redirect()->intended(route('delivery.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('delivery')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('delivery.login');
    }
}
