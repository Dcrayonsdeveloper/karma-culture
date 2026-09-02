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

        // The mirror case: status says approved but the flag the storefront
        // reads was never flipped, so the review stayed hidden.
        DB::table('reviews')
            ->where('status', 'approved')
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

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
