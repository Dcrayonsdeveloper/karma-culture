<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'name',
        'phone',
        'source',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
        'ip_address',
    ];

    /**
     * Deliberately absent from $fillable. The token is the credential that
     * unsubscribes this row, so it is minted here and never accepted from
     * request data - a mass-assignment away from someone choosing another
     * subscriber's key.
     */
    protected $hidden = ['unsubscribe_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Mint the unsubscribe token on the way in, so every address on the list
     * has one however it got here - the popups, the footer form, the checkout
     * box, or a row an admin adds by hand.
     */
    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            $subscriber->unsubscribe_token ??= Str::random(48);
        });
    }

    /**
     * The token, minting and saving one if this row pre-dates the column.
     *
     * The migration backfilled every row that existed when it ran, so this is
     * the belt to that braces: a send must never be held up, or worse go out
     * with a dead unsubscribe link, because one row missed the backfill.
     */
    public function unsubscribeToken(): string
    {
        if (blank($this->unsubscribe_token)) {
            $this->forceFill(['unsubscribe_token' => Str::random(48)])->save();
        }

        return $this->unsubscribe_token;
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', $this->unsubscribeToken());
    }

    /**
     * Off the list, and staying off.
     *
     * The row is kept rather than deleted, and that is the whole point of it:
     * the record of having unsubscribed is what guarantees the address is
     * never mailed again. Delete it and the next time the same person types
     * their address into a popup - or the same list is imported again - they
     * are a brand new subscriber and the mail resumes.
     *
     * Idempotent, because an unsubscribe link gets clicked twice: by the
     * person, and then by whatever scans links in their mailbox.
     */
    public function unsubscribe(): void
    {
        if (! $this->is_active) {
            return;
        }

        $this->forceFill([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ])->save();
    }
}
