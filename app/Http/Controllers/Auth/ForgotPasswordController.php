<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * The one answer this endpoint ever gives.
     *
     * It used to reply "We can't find a user with that email address." for an
     * unknown address and a success banner for a known one, which turns the
     * form into a free membership check: submit a list, keep the addresses
     * that come back as "sent", and you have a target list for phishing or
     * credential stuffing that is already confirmed to shop here. Saying the
     * same thing either way removes the signal without hiding anything a real
     * customer needs — they check their inbox regardless.
     */
    private const NEUTRAL_STATUS = 'If an account exists for that email address, '
        .'we have sent a password reset link. Please check your inbox, including the spam folder.';

    /** Reset emails allowed per address per window, and the window in seconds. */
    private const PER_ADDRESS_LIMIT = 3;

    private const PER_ADDRESS_WINDOW = 900;

    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Permissive `email` on purpose: this has to match an address that
            // is already stored, and an account created before the strict rule
            // existed must still be able to recover its password.
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            // "Email Address is required." is the field's own <label>, because
            // that is the sentence the browser side already prints. This form
            // has no `novalidate`, so the site-wide validator in app.js owns the
            // box and builds its message from the label; saying "Please enter
            // your email address." here made the same rule on the same field
            // read as two different complaints depending on which side rejected
            // it - and a whitespace-only value reaches the server, so both can
            // be seen within one attempt. One rule, one sentence.
            'email.required' => 'Email Address is required.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.max' => 'That email address is too long.',
        ]);

        // The route limiter is keyed on IP, which does nothing against a
        // botnet pointed at one mailbox. This second bucket is keyed on the
        // address itself, so a victim cannot be mail-bombed however many
        // machines are asking.
        $key = 'password-reset-address:'.sha1(Str::lower(trim($validated['email'])));

        if (! RateLimiter::tooManyAttempts($key, self::PER_ADDRESS_LIMIT)) {
            try {
                $status = Password::sendResetLink(['email' => $validated['email']]);

                // Charge the bucket only when the broker actually did something.
                //
                // The broker keeps its own 60-second throttle (config/auth.php),
                // and returns RESET_THROTTLED without sending anything. Counting
                // those spent the quota on mail that was never sent: the form has
                // no submit guard, so three submissions inside a minute — an
                // impatient customer whose first email had not arrived yet — used
                // up all three attempts while only the first one posted a letter,
                // and then bought a full fifteen minutes of silence, each attempt
                // still cheerfully answering "check your inbox".
                //
                // INVALID_USER is still charged on purpose: that is the bucket
                // that stops someone enumerating addresses by sheer volume.
                if ($status !== Password::RESET_THROTTLED) {
                    RateLimiter::hit($key, self::PER_ADDRESS_WINDOW);
                }
            } catch (\Throwable $e) {
                // A transport failure still costs an attempt, so a broken mail
                // server cannot be used as an unlimited probe.
                RateLimiter::hit($key, self::PER_ADDRESS_WINDOW);

                // A mail transport failure must not become the difference
                // between "known address" and "unknown address" - an error
                // page for one and a success banner for the other is the same
                // oracle by another route. Log it and answer normally.
                Log::error('Password reset link could not be sent.', [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        // Same response for sent, unknown, and throttled.
        return back()->with('status', self::NEUTRAL_STATUS);
    }
}
