<?php

namespace Tests\Concerns;

use App\Models\SignupEmailVerification;
use Illuminate\Support\Str;

/**
 * Stand a signup's email verification up without going round the mailbox.
 *
 * Signup now refuses any address that has not been proved, which turns every
 * existing "post a valid payload to /register" test into a test of the
 * verification gate rather than of whatever it was written for. This puts the
 * proof in place directly so those tests go on asking their own question.
 *
 * It writes the same row the real flow writes and nothing more - notably it
 * does NOT reach into the controller, so a test using this is still exercising
 * the production check rather than a stub of it. The flow itself is tested end
 * to end, through the actual endpoints and the actual emailed link, in
 * tests/Feature/Auth/SignupEmailVerificationTest.php.
 */
trait VerifiesSignupEmails
{
    /** A proved, unspent, unexpired verification for this address. */
    protected function verifiedSignupEmail(string $email): SignupEmailVerification
    {
        return SignupEmailVerification::create([
            'email' => SignupEmailVerification::normalizeEmail($email),
            'token_hash' => SignupEmailVerification::hashToken(Str::random(64)),
            'expires_at' => now()->addMinutes(SignupEmailVerification::VERIFIED_TTL_MINUTES),
            'verified_at' => now(),
            'last_sent_at' => now()->subMinutes(5),
            'send_count' => 1,
        ]);
    }

    /**
     * A verification that has been asked for but never clicked.
     *
     * Returns the raw token, because the point of an unverified attempt in a
     * test is usually to open its link.
     *
     * @return array{0: SignupEmailVerification, 1: string}
     */
    protected function pendingSignupEmail(string $email, ?\DateTimeInterface $expiresAt = null): array
    {
        $token = Str::random(64);

        $attempt = SignupEmailVerification::create([
            'email' => SignupEmailVerification::normalizeEmail($email),
            'token_hash' => SignupEmailVerification::hashToken($token),
            'expires_at' => $expiresAt ?? now()->addMinutes(SignupEmailVerification::LINK_TTL_MINUTES),
            'last_sent_at' => now(),
            'send_count' => 1,
        ]);

        return [$attempt, $token];
    }
}
