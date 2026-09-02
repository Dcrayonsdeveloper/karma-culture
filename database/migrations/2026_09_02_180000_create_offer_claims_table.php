<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The exit-intent popup's discount code, made real.
 *
 * The popup has handed out `exit_popup_code` as display text since it was
 * written: it told the customer to type KARMAA10 at checkout and nothing
 * anywhere linked that string to a coupon, an account or a cart. This table is
 * the missing link - one row per (email, code) recording that somebody asked
 * for the offer.
 *
 * It is a table rather than a session key because of when the popup fires.
 * Exit intent catches people who are NOT signed in, on every storefront page,
 * and /checkout is auth-gated - so the moment an email can be matched against
 * an account is almost always minutes or days after the claim, possibly on
 * another device. A session (120 minutes, one browser) cannot span that gap.
 *
 * `code` is the identity, not `coupon_id`: Admin\SettingController::popups()
 * deliberately lets an admin set a code before the Coupon row exists ("the
 * coupon may be created afterwards"), so a claim has to be recordable against
 * a code that resolves to nothing yet, and start working when it does.
 *
 * There is deliberately no `redeemed_at`. Whether a coupon has been spent is
 * already answered by coupon_usage / usage_per_user / usage_limit, which
 * checkout writes and Coupon::canBeUsedBy() reads. A second redemption ledger
 * here would be a fifth copy of a predicate this codebase has already been
 * bitten by duplicating - and it would burn the claim at order CREATION, so an
 * abandoned PayU payment would silently cost the customer the offer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offer_claims')) {
            return;
        }

        Schema::create('offer_claims', function (Blueprint $table) {
            $table->id();

            // Stored lower-cased (ValidationRules::normalizeEmail) so the
            // lookup against users.email is case-insensitive without relying
            // on the database collation.
            $table->string('email');

            // Recorded when the claimer was signed in as this address, for the
            // admin's benefit only. Lookups go by email: users.email is unique,
            // and matching a mutable id against a stable one in a single OR is
            // how one account inherits another's claim.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('code', 50);

            // Back-filled the first time the code resolves to a coupon.
            // nullOnDelete so a deleted-and-recreated coupon self-heals through
            // the code lookup rather than pointing at a gone row.
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();

            $table->string('source', 50)->default('exit_intent');
            $table->timestamp('claimed_at')->nullable();

            // The per-customer deadline. Without it the offer would be "claimed
            // once, discounted forever": KARMAA10 is an evergreen shared code
            // with no expires_at of its own, so the only way to time-limit it
            // would be to expire it for everybody at once.
            $table->timestamp('expires_at')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // One live claim per address per code. Re-claiming refreshes the
            // row rather than stacking rows, and the unique key is what makes
            // that refresh a single statement. No index on coupon_id: the
            // foreign key above already creates one, and the storefront looks
            // claims up by email anyway.
            $table->unique(['email', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_claims');
    }
};
