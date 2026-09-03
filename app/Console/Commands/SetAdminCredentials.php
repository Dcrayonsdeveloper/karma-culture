<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use App\Rules\ValidationRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Set or reset the admin panel sign-in details.
 *
 * Admin credentials cannot live in a seeder: a seeder is committed, so the
 * password would be in git and in every clone, and re-seeding production is
 * not something anyone wants to do just to change an email. This command is
 * the supported way to move the account onto a real address and rotate its
 * password, on this machine or over ssh on the server.
 */
class SetAdminCredentials extends Command
{
    protected $signature = 'admin:credentials
        {--current= : Email of the account to change (defaults to the only admin, or asks)}
        {--email= : New email address}
        {--password= : New password (omit to have one generated)}
        {--first-name= : New first name}
        {--last-name= : New last name}
        {--create : Create the account if --current matches nothing}';

    protected $description = 'Set the admin panel email and password';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user && ! $this->option('create')) {
            return self::FAILURE;
        }

        $email = $this->option('email') ?: $user?->email;
        $password = $this->option('password') ?: $this->generatePassword();
        $generated = ! $this->option('password');

        $check = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => [
                    'required', 'email:rfc', 'max:255',
                    Rule::unique('users', 'email')->ignore($user?->id)->whereNull('deleted_at'),
                ],
                // Was a bare 'min:8'. This command sets a real admin login, so
                // it answers to the same site-wide policy as the forms do -
                // otherwise the documented way to rotate the production
                // password is also the one way to get under it.
                'password' => ['required', 'string', 'max:255', Password::defaults()],
            ],
            ValidationRules::passwordMessages()
        );

        if ($check->fails()) {
            foreach ($check->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use (&$user, $email, $password) {
            $attributes = [
                'email' => $email,
                'password' => $password, // hashed by the model cast
                'role' => 'admin',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ];

            if ($this->option('first-name')) {
                $attributes['first_name'] = $this->option('first-name');
            }

            if ($this->option('last-name')) {
                $attributes['last_name'] = $this->option('last-name');
            }

            if ($user) {
                $user->forceFill($attributes)->save();
            } else {
                $user = User::create($attributes + [
                    'first_name' => $this->option('first-name') ?: 'Site',
                    'last_name' => $this->option('last-name') ?: 'Admin',
                ]);
            }

            // Every existing session and "remember me" cookie for this account
            // was issued against the old password. Cycling the token is what
            // actually signs those devices out - without it, a browser still
            // holding the recaller cookie is logged straight back in and the
            // password change protects nothing.
            $user->forceFill(['remember_token' => Str::random(60)])->save();

            Admin::updateOrCreate(
                ['user_id' => $user->id],
                ['role' => 'super_admin', 'is_active' => true],
            );
        });

        $this->newLine();
        $this->info('Admin credentials updated.');
        $this->line('  Email    : '.$user->email);
        $this->line('  Password : '.$password);

        if ($generated) {
            $this->newLine();
            $this->warn('This password is shown once and is not stored anywhere in plain text.');
            $this->warn('Save it now, then sign in at '.url('/admin/login'));
        }

        return self::SUCCESS;
    }

    /**
     * A generated password that satisfies the site-wide policy.
     *
     * Str::password() was called with symbols: false, which cannot satisfy
     * ->symbols() at all - the command would have generated a password and
     * then rejected it. Symbols are no hardship here because the result is
     * printed for copying, not typed.
     *
     * The draw is repeated rather than trusted once: Str::password() guarantees
     * one character from each POOL it is handed, and upper and lower case share
     * a single "letters" pool, so roughly one generated password in a thousand
     * carries no capital and fails ->mixedCase(). Checking against the same rule
     * the command validates with means the two can never disagree.
     */
    private function generatePassword(): string
    {
        foreach (range(1, 20) as $ignored) {
            $candidate = Str::password(20);

            if (Validator::make(['password' => $candidate], ['password' => [Password::defaults()]])->passes()) {
                return $candidate;
            }
        }

        // Twenty consecutive misses is not chance; fall back to a password
        // assembled to satisfy each requirement outright.
        return Str::password(16) . 'Aa1!';
    }

    /**
     * Which account are we changing?
     *
     * Explicit --current wins. Otherwise: one admin means no ambiguity, and
     * several means we must be told rather than guess and lock someone out.
     */
    private function resolveUser(): ?User
    {
        if ($current = $this->option('current')) {
            $user = User::where('email', $current)->first();

            if (! $user && ! $this->option('create')) {
                $this->error("No user with the email {$current}. Pass --create to make one.");
            }

            return $user;
        }

        $admins = User::where('role', 'admin')->orderBy('id')->get();

        if ($admins->count() === 1) {
            return $admins->first();
        }

        if ($admins->isEmpty()) {
            if (! $this->option('create')) {
                $this->error('No admin account exists. Pass --create with --email to make one.');
            }

            return null;
        }

        $this->error('There is more than one admin account. Name the one to change with --current:');
        foreach ($admins as $admin) {
            $this->line('  - '.$admin->email);
        }

        return null;
    }
}
