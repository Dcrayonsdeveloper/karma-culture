<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifySignupEmail;
use App\Models\SignupEmailVerification;
use App\Models\User;
use App\Rules\ValidationRules as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The address half of Create Account, proved before the account is made.
 *
 * Three endpoints, one resource - a pending signup's email verification:
 *
 *   POST /signup/email-verifications         start one, or resend it
 *   GET  /signup/email-verifications/{uuid}  has the link been clicked yet
 *   GET  /verify-email/{token}               the link itself
 *
 * None of them creates, reads or changes a user account. The only thing this
 * controller can do to `users` is refuse to email an address that is already
 * one, which is the check that has to happen before a message goes out rather
 * than after.
 *
 * WHAT THIS ENDPOINT IS ALLOWED TO KNOW. It takes an email address and nothing
 * else. The signup form has a password in it by the time this runs and must
 * never send it here: there is no field for it, nothing is stored beyond the
 * address, and nothing about the signup travels in the link or the message. A
 * verification email that carried a password would put it in the customer's
 * inbox, in every relay along the way, and in whatever indexes their provider
 * keeps.
 *
 * ON TELLING SOMEONE THE ADDRESS IS TAKEN. Answering "that address already has
 * an account" is a membership check, and this shop closed exactly that hole on
 * the password-reset form, which now says the same neutral sentence whether or
 * not the address is known. The difference here is that signup has always had
 * to answer it - `Rule::unique('users','email')` on the Create Account post has
 * been telling people "An account already exists for this email address" for as
 * long as the form has existed, because a signup form that will not say why it
 * refused you is not a signup form. This moves that same answer one step
 * earlier so it arrives before a message is sent rather than after, and it is
 * metered per address and per IP; it does not disclose anything the existing
 * form did not.
 */
class SignupEmailVerificationController extends Controller
{
    /** Verification emails one address may cost us, and the window in seconds. */
    private const PER_ADDRESS_LIMIT = 5;

    private const PER_ADDRESS_WINDOW = 3600;

    /** Said in exactly these words by the form, per the signup spec. */
    private const EMAIL_TAKEN = 'Email address already exists. Please log in or use another email.';

    /**
     * Start - or resend - the verification for one address.
     *
     * Resend is the same call: posting an address that already has a live
     * attempt rotates its token and sends the new one, which is what makes the
     * previous link stop working the moment a replacement is asked for.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // The same rule set the Create Account post applies, strictShape
            // included: an address this endpoint accepts but registration would
            // reject is a verification the customer can never spend.
            'email' => V::email(strictShape: true),
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.max' => 'That email address is too long.',
        ]);

        $email = SignupEmailVerification::normalizeEmail($validated['email']);

        if ($email === null) {
            return response()->json([
                'message' => 'Please enter your email address.',
                'errors' => ['email' => ['Please enter your email address.']],
            ], 422);
        }

        if (self::addressIsRegistered($email)) {
            // No message goes out, and no attempt row is written. 409 rather
            // than 422: the address is well-formed and the request was
            // understood - it conflicts with something that already exists.
            return response()->json([
                'message' => self::EMAIL_TAKEN,
                'errors' => ['email' => [self::EMAIL_TAKEN]],
            ], 409);
        }

        $attempt = SignupEmailVerification::firstOrNew(['email' => $email]);

        // Already proved, still good, not yet spent: say so instead of sending
        // another message. A customer whose tab lost the state (a reload, a
        // second window) gets their verified badge straight back.
        if ($attempt->exists && $attempt->provesOwnership()) {
            return response()->json(self::statusPayload($attempt), 200);
        }

        if (($wait = $attempt->exists ? $attempt->resendCooldownRemaining() : 0) > 0) {
            return self::tooMany(
                'Please wait a few seconds before asking for another verification email.',
                $wait,
                $attempt,
            );
        }

        // The route limiter is keyed on the IP, and bootstrap/app.php trusts
        // every proxy - so request()->ip() is whatever X-Forwarded-For claims
        // and a single machine can wear as many as it likes. This bucket is
        // keyed on the address instead, which is the thing being mail-bombed
        // and the one part of the request the sender cannot vary for free.
        $bucket = 'signup-email-verification:'.sha1($email);

        if (RateLimiter::tooManyAttempts($bucket, self::PER_ADDRESS_LIMIT)) {
            return self::tooMany(
                'Too many verification emails have been sent to this address. Please try again later.',
                RateLimiter::availableIn($bucket),
                $attempt->exists ? $attempt : null,
            );
        }

        $token = Str::random(64);

        $attempt->fill([
            'token_hash' => SignupEmailVerification::hashToken($token),
            'expires_at' => now()->addMinutes(SignupEmailVerification::LINK_TTL_MINUTES),
            // A resend re-arms the attempt: whatever was proved by the old link
            // is stood down with it, so a rotation can never leave a verified
            // flag behind a token nobody holds any more.
            'verified_at' => null,
            'consumed_at' => null,
            'last_sent_at' => now(),
            'send_count' => ($attempt->send_count ?? 0) + 1,
            'last_request_ip' => $request->ip(),
        ]);

        $attempt->save();

        RateLimiter::hit($bucket, self::PER_ADDRESS_WINDOW);

        try {
            Mail::to($email)->send(new VerifySignupEmail(
                email: $email,
                verificationUrl: self::verificationUrl($token),
                expiresInMinutes: SignupEmailVerification::LINK_TTL_MINUTES,
            ));
        } catch (\Throwable $e) {
            // The address is never in the log line. Everything else here is,
            // because a silent transport failure on this route reads to the
            // customer as "the email never arrived" and to us as nothing at all.
            Log::error('Signup verification email could not be sent.', [
                'attempt' => $attempt->uuid,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'We could not send the verification email just now. Please try again in a moment.',
            ], 502);
        }

        // 202: the message has been handed to the transport, and the thing the
        // caller is actually waiting for - a click - has not happened yet.
        return response()->json(self::statusPayload($attempt), 202);
    }

    /**
     * Whether the link has been clicked yet.
     *
     * What the still-open signup form polls, and what it re-asks when the tab
     * comes back to the front. Addressed by the attempt's uuid, which only the
     * browser that asked for the message has.
     */
    public function show(string $uuid): JsonResponse
    {
        $attempt = SignupEmailVerification::where('uuid', $uuid)->first();

        if ($attempt === null) {
            return self::uncached(response()->json(['message' => 'Unknown verification request.'], 404));
        }

        return self::uncached(response()->json(self::statusPayload($attempt), 200));
    }

    /**
     * The link in the email.
     *
     * Every ending is a rendered page with a status code, and none of them is a
     * 500: an expired link, a link that has already been used and a token that
     * was never issued are all ordinary things for this route to be handed.
     */
    public function verify(string $token): Response
    {
        $attempt = SignupEmailVerification::findByToken($token);

        // Unknown, or already spent on an account - a consumed attempt has its
        // hash cleared, so it stops resolving rather than merely being refused.
        if ($attempt === null || $attempt->isConsumed()) {
            return self::result('invalid', 404);
        }

        if ($attempt->isVerified()) {
            // Clicking a link that has already done its job is not an error.
            return $attempt->isExpired()
                ? self::result('expired', 410, $attempt->email)
                : self::result('already_verified', 200, $attempt->email);
        }

        if ($attempt->isExpired()) {
            return self::result('expired', 410, $attempt->email);
        }

        // Conditional, so two clicks landing together still produce one
        // verification and one rewritten deadline.
        SignupEmailVerification::query()
            ->whereKey($attempt->getKey())
            ->whereNull('verified_at')
            ->whereNull('consumed_at')
            ->update([
                'verified_at' => now(),
                // expires_at changes meaning here: it stops being the link's
                // deadline and becomes the deadline for finishing the signup.
                'expires_at' => now()->addMinutes(SignupEmailVerification::VERIFIED_TTL_MINUTES),
                'updated_at' => now(),
            ]);

        return self::result('verified', 200, $attempt->email);
    }

    /**
     * Is this address already an account?
     *
     * LOWER(TRIM(...)) rather than a plain where: the production collation is
     * utf8mb4_unicode_ci and would compare case-insensitively on its own, but
     * the app does not lower-case emails on write and GuestReviewController hit
     * exactly this and settled on the explicit form. withTrashed() because
     * users are soft-deleted and the unique index is not - a deleted account
     * still owns its address as far as an INSERT is concerned, so a check that
     * ignores it would promise an address it cannot deliver.
     */
    private static function addressIsRegistered(string $normalizedEmail): bool
    {
        return User::withTrashed()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->exists();
    }

    /** The absolute link, anchored to the configured site address. */
    private static function verificationUrl(string $token): string
    {
        // NOT route(..., absolute: true). bootstrap/app.php trusts every proxy
        // and honours X-Forwarded-Host, and no trusted-host list is registered,
        // so an absolute URL built from the request would be whatever the
        // caller's header said - and this endpoint posts that URL to a real
        // person's inbox in a genuine message from this shop. config('app.url')
        // is set on the server and no header can move it. The same reasoning,
        // and the same fix, as VerifyEmail::createUrlUsing in AppServiceProvider;
        // this route carries no signature, so stitching the relative path on is
        // enough and there is nothing for a forced root to have to cover.
        return rtrim((string) config('app.url'), '/')
            .route('signup.verify-email', ['token' => $token], absolute: false);
    }

    /**
     * What the form is told about an attempt.
     *
     * The normalised address travels with the status on purpose: it is what
     * lets the form check that the badge it is about to show belongs to the
     * address currently in the box. The server does not take the form's word
     * for that either - RegisterController re-checks it - but a UI that can go
     * wrong quietly is worse than one that cannot.
     */
    private static function statusPayload(SignupEmailVerification $attempt): array
    {
        return [
            'id' => $attempt->uuid,
            'email' => $attempt->email,
            'status' => $attempt->publicStatus(),
            'resend_available_in' => $attempt->resendCooldownRemaining(),
        ];
    }

    /**
     * Keep an answer out of every cache between here and the browser.
     *
     * App\Http\Middleware\SecurityHeaders sets no-store only for a signed-in
     * request, and every request in this flow is a guest's. Without it the
     * status poll is an ordinary cacheable GET, and a shared cache that held on
     * to one "pending" would keep telling a customer their link has not been
     * clicked long after it has.
     */
    private static function uncached(JsonResponse $response): JsonResponse
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private static function tooMany(string $message, int $retryAfter, ?SignupEmailVerification $attempt): JsonResponse
    {
        $payload = ['message' => $message, 'retry_after' => $retryAfter];

        if ($attempt !== null) {
            $payload += self::statusPayload($attempt);
            $payload['message'] = $message;
            $payload['retry_after'] = $retryAfter;
        }

        return response()->json($payload, 429)->header('Retry-After', (string) $retryAfter);
    }

    private static function result(string $state, int $status, ?string $email = null): Response
    {
        return response()->view('auth.verify-signup-email', [
            'state' => $state,
            'email' => $email,
        ], $status);
    }
}
