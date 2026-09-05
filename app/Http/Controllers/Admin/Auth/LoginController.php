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

        $this->endSessionKeeping($request, 'web');

        return redirect()->route('admin.login');
    }

    /**
     * Sign this guard out without taking the other one with it.
     *
     * One session cookie carries both guards: config/auth.php gives `admin` and
     * `web` the same users provider, and Laravel keeps each guard's login state
     * under its own `login_<guard>_<hash>` key in the one session. Throwing the
     * whole session away therefore signed out whoever was working in the other
     * tab as well - a shopper logging out took the admin panel down with them,
     * notification polling and all, and an admin logging out emptied a
     * shopper's session mid-checkout.
     *
     * Everything else still goes. This is invalidate() - flush plus a new
     * session id, so nothing the departing side left behind can be read by
     * whoever uses the browser next - with only the other guard's own login
     * keys carried across into the fresh session.
     */
    private function endSessionKeeping(Request $request, string $guard): void
    {
        $prefix = 'login_'.$guard.'_';

        $keep = [];
        foreach ($request->session()->all() as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $keep[$key] = $value;
            }
        }

        $request->session()->invalidate();

        foreach ($keep as $key => $value) {
            $request->session()->put($key, $value);
        }

        $request->session()->regenerateToken();
    }

}
