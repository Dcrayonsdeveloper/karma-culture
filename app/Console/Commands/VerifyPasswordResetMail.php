<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

/**
 * Prove the password reset email actually leaves the server.
 *
 * This exists because the flow spent its whole life looking healthy from the
 * outside: the form validated, the controller returned its neutral banner, and
 * the test suite was green, while production sat on MAIL_MAILER=log and wrote
 * every reset link into a file nobody read. Nothing in the app surfaced that,
 * because the whole point of the neutral banner is to say the same thing
 * whatever happened underneath.
 *
 * So this reports the transport it is really using, sends a genuine reset link
 * through the real broker, and prints the transport's own error verbatim when
 * it fails - which is the one thing the web form must never do.
 */
class VerifyPasswordResetMail extends Command
{
    protected $signature = 'mail:verify-reset
                            {email : The address to send a real password reset link to}
                            {--dry : Report the mail configuration without sending anything}';

    protected $description = 'Send a real password reset email and report exactly what the mail transport did';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');

        $this->newLine();
        $this->line('  Transport   : '.$mailer);

        if ($mailer === 'smtp') {
            $this->line('  Host        : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));
            $this->line('  Scheme      : '.(config('mail.mailers.smtp.scheme') ?: '(none)'));
            $this->line('  Username    : '.(config('mail.mailers.smtp.username') ?: '(none)'));
            $this->line('  Password    : '.(config('mail.mailers.smtp.password') ? '(set)' : '(EMPTY)'));
        }

        $this->line('  From        : '.config('mail.from.address').' as "'.config('mail.from.name').'"');
        $this->line('  Link base   : '.config('app.url'));
        $this->newLine();

        if ($mailer === 'log') {
            $this->error('  MAIL_MAILER=log - reset links are written to storage/logs and no');
            $this->error('  customer will ever receive one. Nothing was sent.');

            return self::FAILURE;
        }

        // An smtp mailer with no credentials is the other quiet failure: it
        // reads as configured, and only turns into an authentication error at
        // the moment a customer is waiting on the mail.
        if ($mailer === 'smtp' && (! config('mail.mailers.smtp.username') || ! config('mail.mailers.smtp.password'))) {
            $this->error('  MAIL_MAILER=smtp but the username or password is empty.');
            $this->error('  The transport will be refused at authentication.');

            return self::FAILURE;
        }

        if ($this->option('dry')) {
            $this->info('  Configuration looks deliverable. Re-run without --dry to send.');

            return self::SUCCESS;
        }

        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error('  No account with that address, so there is nothing to reset.');
            $this->line('  Pass the address of a real customer account.');

            return self::FAILURE;
        }

        // Straight past the controller's per-address bucket and the broker's own
        // 60-second throttle, both of which would swallow a repeated check and
        // report success without sending anything.
        $token = Password::createToken($user);

        try {
            $user->notify(new ResetPasswordNotification($token));
        } catch (\Throwable $e) {
            $this->error('  The transport refused the message:');
            $this->newLine();
            $this->line('  '.$e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('  Sent. The transport accepted a real reset link for '.$email.'.');
        $this->line('  Check the inbox, and the spam folder - acceptance is not delivery.');
        $this->newLine();

        return self::SUCCESS;
    }
}
