<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Admin moderation used to write only is_approved and leave status at its
     * 'pending' default, so the two columns drifted apart. Pull them back
     * together before the screens start trusting status.
     */
    public function up(): void
    {
        // Approved through the admin screen, but the badge and tab counts read
        // status - which never moved off 'pending'.
        DB::table('reviews')
            ->where('is_approved', true)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);

        // The mirror case is a rejection, not a stalled approval. Only two paths
        // ever wrote status='approved' - the generator and Review::approve() -
        // and both set is_approved with it. So a row that says 'approved' while
        // hidden is one the old admin Reject cleared the flag on without moving
        // status. Publishing it again would undo that decision; record it instead.
        DB::table('reviews')
            ->where('status', 'approved')
            ->where('is_approved', false)
            ->update(['status' => 'rejected']);

        // Rejected or flagged reviews must not stay publicly visible.
        DB::table('reviews')
            ->whereIn('status', ['rejected', 'flagged'])
            ->where('is_approved', true)
            ->update(['is_approved' => false]);
    }

    /**
     * A data repair - the previous inconsistent state is not worth restoring.
     */
    public function down(): void
    {
        //
    }
};
