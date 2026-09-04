<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give the admin bell's ten-second poll an index it can seek on.
 *
 * The poll asks one question - "anything addressed to this admin since the
 * moment I last looked?" - which is
 * WHERE audience = 'admin' AND user_id = ? AND created_at >= ? ORDER BY created_at.
 *
 * notifications_audience_user_read_index (audience, user_id, is_read) can seek
 * only as far as the admin: from there MySQL walks every row that admin has ever
 * been sent, filters on created_at and sorts the survivors. That is a handful of
 * rows on day one and thousands after a busy quarter - re-read by every admin,
 * six times a minute, forever. notifications_user_id_is_read_created_at_index
 * cannot help either; is_read sits between the two columns the poll constrains,
 * so the created_at leg is not a range the optimiser can use.
 *
 * Putting created_at third turns the same question into a range scan that starts
 * at the cursor and stops at the limit, and hands the rows back already sorted,
 * so the cost is proportional to what is NEW rather than to the whole history.
 * The unread count keeps using the audience/user/is_read index, which already
 * matches it exactly.
 */
return new class extends Migration
{
    private const INDEX = 'notifications_audience_user_created_index';

    public function up(): void
    {
        // Guarded so a re-run cannot fail. Adding an index that is already there
        // is a hard error, and on this server `php artisan` is awkward enough to
        // reach that a migration which aborts half way is expensive to unpick -
        // the audience migration before it guards its column add for the same
        // reason.
        if (Schema::hasIndex('notifications', self::INDEX)) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            // Named explicitly for the same reason as the audience index: the
            // generated name would sit close to MySQL's 64-character limit.
            $table->index(['audience', 'user_id', 'created_at'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasIndex('notifications', self::INDEX)) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
