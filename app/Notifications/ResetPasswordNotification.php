<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The password reset email, in the shop's own voice.
 *
 * Laravel's stock ResetPassword notification is deliberately generic — "Hello!
 * You are receiving this email because we received a password reset request."
 * with no sender identity in the body. Every other mail this shop sends
 * (welcome, order confirmation, refund, return) is branded and signed, so the
 * one email that asks a customer to click a link and type a new password was
 * the single least recognisable thing in their inbox. That is backwards: a
 * password email is exactly the one a customer should be able to recognise as
 * genuinely ours before they trust it.
 *
 * Not queued on purpose. Production runs no queue worker, so a ShouldQueue
 * notification here would be silently written to the jobs table and never
 * sent — the same dead end as the log mailer, just harder to spot.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(
        #[\SensitiveParameter] public string $token
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your '.config('app.name').' password')
            ->markdown('emails.reset-password', [
                'user' => $notifiable,
                'url' => $this->resetUrl($notifiable),
                'expiresInMinutes' => $this->expiresInMinutes(),
            ]);
    }

    /**
     * Built the same way the framework builds it, so the link keeps matching
     * the password.reset route: the token in the path and the address in the
     * query string, both of which the reset form posts back.
     */
    private function resetUrl(object $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    /**
     * Read from the broker rather than hard-coded, so the sentence in the
     * email cannot drift away from the expiry the broker actually enforces.
     */
    private function expiresInMinutes(): int
    {
        return (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60
        );
    }
}
