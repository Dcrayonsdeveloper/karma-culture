<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'session_id',
        'referrer',
        'user_agent',
    ];

    /**
     * How long the same person re-opening the same product counts as one view.
     *
     * Browsers prefetch, customers double-tap, and a back-then-forward lands on
     * the same page twice in a second. Without this the view count measures
     * impatience. Anything past the window is a genuine second visit to the
     * page and is counted again.
     */
    private const DEDUPE_SECONDS = 30;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record one view of a product page.
     *
     * This used to be an updateOrCreate() keyed on (user_id, product_id) that
     * only ran for signed-in customers, and whose update half wrote a
     * 'viewed_at' column that does not exist and is not fillable — so it was a
     * no-op. The table therefore held one row per customer per product, ever,
     * with no session, no referrer and no guests at all. Every Analytics
     * number built on it was wrong in the same direction: "Product Views" was
     * really "distinct products a logged-in customer has opened at some point",
     * and traffic sources were 100% Direct because referrer was always null.
     *
     * Returns null when the view was deliberately not recorded (bot, or a
     * repeat inside the dedupe window).
     */
    public static function record(Product $product, ?User $user = null): ?self
    {
        $request = request();
        $user ??= auth()->user();

        $userAgent = (string) $request->userAgent();

        if (self::looksLikeBot($userAgent)) {
            return null;
        }

        // Guests are the majority of storefront traffic and are identified by
        // their session; only start a session if one already exists, so a
        // crawler cannot make us mint sessions.
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        if (! $user && ! $sessionId) {
            return null;
        }

        if (self::recentlyRecorded($product->id, $user?->id, $sessionId)) {
            return null;
        }

        return static::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            // Column is a plain string; a pathological Referer header would
            // otherwise blow the row up on insert.
            'referrer' => self::truncate($request->headers->get('referer'), 500),
            'user_agent' => self::truncate($userAgent, 500),
        ]);
    }

    /**
     * SQL naming the person behind a row, for DISTINCT counts.
     *
     * Signed-in activity keys on the user so one customer stays one visitor
     * across sessions and devices; guests key on their session. The prefixes
     * matter: a bare COALESCE(user_id, session_id) lets user 5 and a session
     * literally called "5" collapse into one visitor, and mixes an integer with
     * a string so the comparison type depends on the driver.
     *
     * @param  string  $table  qualifier for the columns, e.g. 'carts'
     */
    public static function visitorKeySql(string $table = 'product_views'): string
    {
        return "CASE WHEN {$table}.user_id IS NOT NULL
                     THEN CONCAT('u:', {$table}.user_id)
                     ELSE CONCAT('s:', {$table}.session_id) END";
    }

    /** The same key, for a row already in memory. */
    public static function visitorKeyFor(?int $userId, ?string $sessionId): ?string
    {
        if ($userId !== null) {
            return 'u:' . $userId;
        }

        return $sessionId !== null ? 's:' . $sessionId : null;
    }

    /** Has this person opened this product in the last few seconds? */
    private static function recentlyRecorded(int $productId, ?int $userId, ?string $sessionId): bool
    {
        return static::query()
            ->where('product_id', $productId)
            ->where('created_at', '>=', now()->subSeconds(self::DEDUPE_SECONDS))
            ->when(
                $userId !== null,
                fn ($q) => $q->where('user_id', $userId),
                fn ($q) => $q->where('session_id', $sessionId)->whereNull('user_id'),
            )
            ->exists();
    }

    /**
     * Crawlers, uptime pingers and preview fetchers are not customers.
     *
     * Deliberately a coarse substring match rather than a bot database: the
     * cost of missing one is a slightly inflated view count, and the cost of a
     * false positive is a lost real visitor, so the list only names things
     * that are unambiguously not people.
     */
    private static function looksLikeBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegram|embedly|quora link preview|pingdom|uptimerobot|headlesschrome|lighthouse|gtmetrix|semrush|ahrefs|mj12|dotbot|petalbot|python-requests|curl|wget|go-http-client|okhttp|axios|postman/i',
            $userAgent
        );
    }

    private static function truncate(?string $value, int $length): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }
}
