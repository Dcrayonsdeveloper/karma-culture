<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\ValidationRules as V;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // The two required sentences are the fields' own labels - "Email
            // Address" and "New Password" - because the form carries no
            // `novalidate` and the site-wide validator in app.js therefore owns
            // both boxes and names an empty one after its label. Saying "Please
            // enter your email address." / "Please choose a new password." here
            // made the same rule on the same field arrive in two different
            // wordings depending on which side caught it, which is exactly the
            // drift V::passwordMessages() below was introduced to end for the
            // password POLICY; only the required case had been left behind.
            'email.required' => 'Email Address is required.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            // This screen validates `max:255` on the address just as sign-in and
            // forgot-password do, but it alone had no sentence for it, so an
            // over-long address was answered here in Laravel's framework voice
            // ("The email field must not be greater than 255 characters.") and
            // in the site's own on the other two auth screens. The email box on
            // reset-password.blade.php has no maxlength either, so nothing stops
            // an over-long address being sent and the mismatch being read.
            'email.max' => 'That email address is too long.',
            'password.required' => 'New Password is required.',
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

                // Rotating remember_token only kills remember-me cookies; the
                // session store is separate, and sessions live in the database
                // with a rolling lifetime. Without this, someone who had got
                // into the account kept their signed-in session through the
                // reset — which is the one thing a customer resetting after a
                // compromise believes they are stopping.
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // 'success', not 'status'. The login page renders session('success')
            // and session('error') and nothing else, so the confirmation this
            // line has always produced was flashed and then silently dropped:
            // the customer chose a new password, pressed the button, and landed
            // on a blank sign-in form with no acknowledgement that anything had
            // happened. ('status' is read on the forgot-password page, which is
            // where that key came from.)
            return redirect()->route('login')->with('success', __($status));
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
