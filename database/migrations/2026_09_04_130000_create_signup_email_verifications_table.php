<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A signup's proof that it owns the address it typed - held BEFORE any
     * account exists.
     *
     * The shop's other verification flow (users.email_verified_at, the signed
     * /email/verify/{id}/{hash} route) can only work on a row that is already
     * in `users`, which means creating the account first and proving the
     * address afterwards. Signup now runs the other way round: the address is
     * proved, and only then may an account be created. That needs somewhere to
     * keep the attempt, and it must NOT be the users table - a half-made
     * customer row is exactly what this change exists to avoid.
     *
     * ONE ROW PER ADDRESS, not per click. `email` is unique and holds the
     * normalised form (trimmed, lower-cased), so a resend rotates the token on
     * the existing row rather than leaving a trail of live tokens behind it.
     * That is also what makes "the old verification must not validate the new
     * address" true by construction: the attempt IS the address.
     *
     * THE TOKEN IS NOT STORED. `token_hash` is the sha256 of the 64-character
     * random string that went out in the email, and the string itself exists
     * only in that message. This departs from the app's own precedent -
     * review_invitations and cart recovery both keep their tokens in the clear
     * - and the docblock is here because a non-obvious schema decision is
     * supposed to say why: a plaintext token in this table is a working
     * verification link for anybody who can read a row, and unlike a review
     * invitation this one gates account creation. sha256 rather than bcrypt
     * because the input is already 381 bits of entropy, so there is nothing to
     * slow an attacker down for, and the lookup has to be an indexed equality
     * match rather than a scan-and-compare. Nulled once an account has been
     * created from the attempt, so the link cannot be replayed at all.
     *
     * `expires_at` carries both of the attempt's lifetimes, in order: while the
     * attempt is unverified it is the token's own deadline (an hour, matching
     * the framework's verification window); the moment it verifies it is
     * rewritten to the deadline for finishing the signup. One column because
     * both readings answer the same question - when does this attempt stop
     * being usable - and two would leave a caller having to know which to ask.
     *
     * Nothing points at this table and it points at nothing. It is deliberately
     * free of a users foreign key: an attempt exists precisely when its customer
     * does not.
     */
    public function up(): void
    {
        if (Schema::hasTable('signup_email_verifications')) {
            return;
        }

        Schema::create('signup_email_verifications', function (Blueprint $table) {
            $table->id();

            // Public identifier: the open signup form polls this attempt to
            // find out whether the link has been clicked yet, so it is
            // addressed by uuid and never by a guessable auto-increment.
            // Str::uuid7() in the model's creating hook, per ShopFilterExclusion.
            $table->uuid('uuid')->unique();

            // Normalised - App\Rules\ValidationRules::normalizeEmail(). The
            // unique index is what makes two browsers racing the same address
            // land on one attempt instead of two.
            $table->string('email')->unique();

            // sha256 hex of the emailed token; null once consumed. Unique so a
            // hash collision or a duplicated rotation cannot produce two rows
            // one link would open. Nullable, and MySQL allows any number of
            // NULLs under a unique index, so consumed rows do not collide.
            $table->char('token_hash', 64)->nullable()->unique();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Set when an account is actually created from this attempt. A
            // consumed attempt can never verify a second signup.
            $table->timestamp('consumed_at')->nullable();

            // The resend cooldown the form counts down against, and a plain
            // count of how many messages this address has cost us. SMTP here is
            // one Gmail app password with one daily quota shared with order
            // confirmations and password resets.
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedSmallInteger('send_count')->default(0);

            $table->string('last_request_ip', 45)->nullable();

            $table->timestamps();

            // Sweeping expired attempts.
            $table->index('expires_at', 'signup_email_verifications_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_email_verifications');
    }
};
