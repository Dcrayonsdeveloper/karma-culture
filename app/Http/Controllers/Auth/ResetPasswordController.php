<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\ValidationRules as V;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    /**
     * One message for a bad token and for an address with no account.
     *
     * Password::reset() looks the user up before it looks at the token, so
     * "We can't find a user with that email address." comes back for any
     * made-up token — no valid link required. That made this endpoint an
     * account-existence oracle exactly like the forgot-password form was.
     */
    private const LINK_INVALID = 'This password reset link is invalid or has expired. '
        .'Please request a new one.';

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            // Echoed into a value="" attribute, which Blade escapes; bounded
            // here so a multi-megabyte query string cannot be rendered back.
            'email' => Str::limit((string) $request->query('email', ''), 255, ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            // Laravel's own tokens are 64 hex characters; the bound just stops
            // an unbounded field being hashed and compared.
            'token' => ['required', 'string', 'max:255'],
            // Permissive `email`: it has to match an address already stored.
            'email' => ['required', 'string', 'email', 'max:255'],
            // V::password() is Password::defaults() + confirmed - the site-wide
            // policy, defined once in AppServiceProvider.
            'password' => [...V::password(), 'max:255'],
        ], [
            'token.required' => 'This password reset link is incomplete. Please request a new one.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'password.required' => 'Please choose a new password.',
            'password.confirmed' => 'The two passwords do not match.',
            'password.max' => 'Your password must be 255 characters or fewer.',
            // Word for word what the box already said while the password was
            // being typed (_passwordError in app.js), so the same complaint
            // arriving from the server does not read as a different one.
            ...V::passwordMessages(),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    // Rotating this signs out every "remember me" cookie the
                    // account has, which is the point of resetting after a
                    // compromise.
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        $message = in_array($status, [Password::INVALID_USER, Password::INVALID_TOKEN], true)
            ? self::LINK_INVALID
            : __($status);

        // The email comes back so the form is not blank on the retry; the two
        // password fields deliberately do not.
        return back()
            ->withErrors(['email' => [$message]])
            ->withInput($request->only('email', 'token'));
    }
}
