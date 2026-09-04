<?php

namespace App\Models;

use App\Rules\ValidationRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One address a would-be customer is proving they can read mail at.
 *
 * The row is the whole state of a pre-account email verification: which
 * address, the hash of the link that was posted to it, when that link dies,
 * whether it has been clicked, and whether an account has since been made from
 * it. There is one row per address, so "verified" is a fact about the address
 * rather than about a browser tab - which is what makes it impossible for a
 * verification of abc@example.com to stand in for xyz@example.com.
 *
 * The raw token is never held here; see the migration for why.
 */
class SignupEmailVerification extends Model
{
    /** How long an unclicked verification link stays good. */
    public const LINK_TTL_MINUTES = 60;

    /**
     * How long a verified address stays good for finishing the signup.
     *
     * Long enough that someone who verifies, wanders off and comes back to the
     * still-open tab is not asked to do it twice; short enough that a proof
     * cannot be banked indefinitely.
     */
    public const VERIFIED_TTL_MINUTES = 1440;

    /** Seconds a customer must wait between two verification emails. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    protected $fillable = [
        'uuid',
        'email',
        'token_hash',
        'expires_at',
        'verified_at',
        'consumed_at',
        'last_sent_at',
        'send_count',
        'last_request_ip',
    ];

    /**
     * The hash is a secret in its own right - it is the only thing standing
     * between a leaked API response and a verified address - so it never
     * reaches a serialised model.
     */
    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'consumed_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'send_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $attempt): void {
            if (empty($attempt->uuid)) {
                // v7 rather than v4, matching ShopFilterExclusion: the id sorts
                // by creation time, which is what a housekeeping sweep over
                // stale attempts wants.
                $attempt->uuid = (string) Str::uuid7();
            }
        });
    }

    /** Bound on the uuid: an attempt is never addressed by its row id. */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * The form the `email` column holds: trimmed and lower-cased.
     *
     * Every lookup and every comparison in this flow goes through here, so
     * "Asha@Example.com " and "asha@example.com" are one attempt and one
     * verified fact. Deliberately nothing beyond case and whitespace - no
     * stripping of Gmail dots or +aliases, which would silently merge
     * addresses their owners consider separate.
     */
    public static function normalizeEmail(?string $email): ?string
    {
        return ValidationRules::normalizeEmail($email);
    }

    /**
     * What goes in `token_hash` for a given emailed token.
     *
     * sha256, not bcrypt: a 64-character Str::random is already far past the
     * point where slowing an attacker down buys anything, and the lookup has to
     * be an indexed equality match rather than a row-by-row verify.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** Find the live attempt behind a link, or null. Never leaks WHY it failed. */
    public static function findByToken(?string $token): ?self
    {
        if ($token === null || $token === '') {
            return null;
        }

        return static::where('token_hash', static::hashToken($token))->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    /**
     * Whether this attempt still proves anything.
     *
     * Verified, not yet spent on an account, and inside the window that was
     * rewritten onto expires_at when it verified.
     */
    public function provesOwnership(): bool
    {
        return $this->isVerified() && ! $this->isConsumed() && ! $this->isExpired();
    }

    /** Seconds left before another verification email may be sent. */
    public function resendCooldownRemaining(): int
    {
        if ($this->last_sent_at === null) {
            return 0;
        }

        $ready = $this->last_sent_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        return max(0, (int) ceil(now()->diffInSeconds($ready, false)));
    }

    /**
     * The one word the signup form is told about this attempt.
     *
     * Deliberately coarse. A consumed attempt reads as `expired` rather than as
     * "an account already exists for this" - the form has no business learning
     * that from a status poll, and the customer is about to be told it properly
     * by the create-account response if it is true.
     */
    public function publicStatus(): string
    {
        if ($this->isConsumed()) {
            return 'expired';
        }

        if ($this->isVerified()) {
            return $this->isExpired() ? 'expired' : 'verified';
        }

        return $this->isExpired() ? 'expired' : 'pending';
    }

    /**
     * Spend this attempt on an account that has just been created.
     *
     * Conditional on the row still being unspent, so two requests that somehow
     * reach here for one attempt cannot both believe they consumed it. The
     * token hash goes with it: a consumed link is not merely refused, it stops
     * existing.
     */
    public function consume(): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->whereNull('consumed_at')
            ->update([
                'consumed_at' => now(),
                'token_hash' => null,
                'updated_at' => now(),
            ]) === 1;
    }
}
