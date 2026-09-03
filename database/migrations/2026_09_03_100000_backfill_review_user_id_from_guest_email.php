<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give the reviews already on the site their authors back.
     *
     * The product page posts its review form to product.guest-review for signed-in
     * and signed-out visitors alike, and GuestReviewController wrote guest_name and
     * guest_email and nothing else - so reviews.user_id was NULL on every row the
     * site has ever collected. Account\ReviewController::index reads
     * $request->user()->reviews(), a hasMany on user_id, so My Reviews told every
     * customer they had never reviewed anything, including ones whose review was
     * live on the product page.
     *
     * The controller now records user_id, which fixes reviews written from here on.
     * This repairs the rows written before it.
     *
     * guest_email is the only link back to an account, and it is a sound one to
     * follow once: users.email is unique, so an address resolves to at most one
     * account. It is emphatically NOT sound to follow at read time - the column is
     * free text that nobody ever proved ownership of, so a query that matched on it
     * live would show one person's review inside another person's My Reviews for the
     * price of typing their address into the form.
     *
     * Matching is done on the trimmed, lower-cased address rather than on the
     * table's collation. utf8mb4_unicode_ci would compare case-insensitively by
     * itself, but guest_email was stored exactly as typed, and the test database is
     * SQLite, where = is case-sensitive. Leaning on the collation would make this
     * migration work on the server and quietly do nothing under test.
     *
     * guest_name and guest_email are left in place. They are the record of what the
     * reviewer actually typed, Review::getReviewerNameAttribute publishes guest_name
     * ahead of the account name precisely so this backfill cannot rename a review
     * that is already public, and the duplicate check in GuestReviewController still
     * reads guest_email.
     *
     * DB::table, not Eloquent: Review::booted() hooks created/updated to
     * Product::updateRating(), which recomputes products.rating and review_count
     * from is_approved. user_id is not part of that aggregate, so firing the hooks
     * would be a pointless rebuild over every row - and it would bump every
     * review's updated_at, destroying the audit trail on rows whose content nobody
     * touched.
     */
    public function up(): void
    {
        // One lookup per distinct address rather than per row: the lookup is the
        // expensive half and the same person's address repeats across their reviews.
        //
        // Deliberately not chunked. A chunk() whose WHERE includes
        // whereNull('user_id') while the body fills user_id shrinks its own result
        // set under the cursor and skips rows.
        $addresses = DB::table('reviews')
            ->whereNull('user_id')
            ->whereNotNull('guest_email')
            ->distinct()
            ->pluck('guest_email');

        foreach ($addresses as $stored) {
            $normalised = mb_strtolower(trim((string) $stored));

            if ($normalised === '') {
                continue;
            }

            $userId = DB::table('users')
                // users soft-delete, and a closed account cannot sign in to reach
                // the page this repairs. The review still publishes under its
                // guest_name either way.
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalised])
                // users.email is unique so there is nothing to disambiguate, but a
                // hand-patched database should resolve to the oldest account rather
                // than to whichever row the engine happens to return first.
                ->orderBy('id')
                ->value('id');

            // A guest who never opened an account. Nothing to attribute to.
            if (! $userId) {
                continue;
            }

            // Matched back on the stored value, not the normalised one, so the rows
            // updated are exactly the rows the address came from. whereNull('user_id')
            // is what makes a second run a no-op.
            DB::table('reviews')
                ->whereNull('user_id')
                ->where('guest_email', $stored)
                ->update(['user_id' => $userId]);
        }
    }

    /**
     * A data repair, and not a reversible one. Clearing user_id again would not
     * restore the previous state, it would manufacture a new wrong one: the product
     * page form now records user_id and guest_email together, so an inverse UPDATE
     * would strip the attribution off reviews this migration never touched.
     */
    public function down(): void
    {
        //
    }
};
