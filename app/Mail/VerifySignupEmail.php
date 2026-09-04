<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Confirm your email address" - the message the Create Account form sends
 * before there is an account to send it to.
 *
 * Not queued, like every other mailable here: production runs
 * QUEUE_CONNECTION=database with no worker and no cron, so anything queued is
 * written to `jobs` and stops there.
 *
 * The two constructor arguments are the whole payload, and that is the point:
 * nothing about the signup except the address being proved and the link that
 * proves it. No name, no mobile number, and above all no password - see
 * SignupEmailVerificationController for the rule this is one half of.
 */
class VerifySignupEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $verificationUrl,
        public int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your email address - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verify-signup-email',
        );
    }
}
