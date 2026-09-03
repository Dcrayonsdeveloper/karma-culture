<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teach the notifications table who a row is talking to.
 *
 * One table serves both bells, and config/auth.php gives the `admin` guard the
 * same `users` provider as `web`, so an admin is simply a users row with
 * role = 'admin'. Rows were therefore keyed only by user_id, and an admin who
 * also shops saw "Your order has been confirmed" sitting in the admin bell
 * beside the new-order alerts. An audience column is what lets each bell ask
 * for its own rows.
 *
 * Existing rows are almost all customer traffic, so 'customer' is the default;
 * only the two admin-only types written so far are moved across.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'audience')) {
                $table->string('audience', 20)->default('customer')->after('type');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            // Both bells filter on all three columns at once - the admin bell
            // reads (audience, user_id, is_read) for its unread count and the
            // customer list reads exactly the same shape - so one composite
            // index serves both. The name is given explicitly because the
            // generated one would be long enough to risk MySQL's 64-character
            // identifier limit.
            $table->index(['audience', 'user_id', 'is_read'], 'notifications_audience_user_read_index');
        });

        // Safe on an empty table: this simply updates nothing.
        DB::table('notifications')
            ->whereIn('type', ['new_enquiry', 'new_ticket'])
            ->update(['audience' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_audience_user_read_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'audience')) {
                $table->dropColumn('audience');
            }
        });
    }
};
