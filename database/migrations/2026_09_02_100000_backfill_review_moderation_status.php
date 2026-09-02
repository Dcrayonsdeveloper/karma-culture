<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Admin moderation used to write only is_approved and leave status at its
     * 'pending' default, so the two columns drifted apart. Pull them back
     * together before the screens start trusting status.
     *
     * Every repair below resolves a disagreement the same way: is_approved wins.
     * The old admin screen wrote that column and nothing else, so on a row where
     * the two disagree the boolean is the moderator's last decision and status is
     * the stale one. Rewriting status to match therefore preserves what the
     * moderator actually did, in both directions.
     *
     * That also means is_approved is never written here. It is the column
     * Product::updateRating() aggregates into products.rating and
     * products.review_count, and those are only recomputed by the Review model's
     * events - which the query builder does not fire. Leaving the boolean alone
     * keeps the denormalised totals correct without a rebuild pass.
     */
    public function up(): void
    {
        // Approved through the admin screen, but the badge and tab counts read
        // status - which never moved off 'pending'.
        DB::table('reviews')
            ->where('is_approved', true)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);

        // The mirror case is a rejection, not a stalled approval: both writers of
        // status='approved' set is_approved alongside it, so a row that says
        // approved while hidden is one the old Reject cleared the flag on.
        // Publishing it again would undo that decision; record it instead.
        DB::table('reviews')
            ->where('status', 'approved')
            ->where('is_approved', false)
            ->update(['status' => 'rejected']);

        // Same rule once more: a visible review still labelled rejected or
        // flagged was approved after that label was applied. Clearing the flag
        // here would silently unpublish it.
        DB::table('reviews')
            ->whereIn('status', ['rejected', 'flagged'])
            ->where('is_approved', true)
            ->update(['status' => 'approved']);
    }

    /**
     * A data repair - the previous inconsistent state is not worth restoring.
     */
    public function down(): void
    {
        //
    }
};
