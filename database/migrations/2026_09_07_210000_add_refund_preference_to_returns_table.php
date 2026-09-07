<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the customer asked to be given back, as distinct from how the admin
 * later pays it.
 *
 * `refund_method` already existed, but it answers a different question and it
 * is answered by a different person at a different time: the ADMIN picks it
 * while processing, and it names a payout rail (original card, wallet, bank).
 * The customer never saw it. What the return form now asks - money back, or
 * store credit - is a request made at submission time, and overloading
 * refund_method with it would mean a row could no longer say both what was
 * wanted and what was done.
 *
 * Nullable, and left null for exchanges: an exchange is a replacement item,
 * so neither answer is true of it. Existing rows are null for the same
 * reason - they were never asked, and defaulting them to 'refund' would be
 * inventing an answer on the customer's behalf.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->enum('refund_preference', ['refund', 'coupon'])
                ->nullable()
                ->after('refund_method');

            // The credit actually issued, once one has been. This is what makes
            // issuing idempotent - processing a return twice must not mint a
            // second voucher - and it is the only way the admin screen and the
            // customer can be shown the code that was created.
            $table->foreignId('refund_coupon_id')
                ->nullable()
                ->after('refund_preference')
                ->constrained('coupons')
                ->nullOnDelete();
        });

        // A coupon is now one of the ways a refund is actually paid, so the
        // payout rail has to be able to say so. Raw SQL because Laravel's
        // change() cannot rewrite an enum's members; both this database and the
        // test database are MySQL, so MODIFY is available in each.
        DB::statement(
            "ALTER TABLE `returns` MODIFY `refund_method` "
            ."ENUM('original', 'wallet', 'bank', 'coupon') NOT NULL DEFAULT 'original'"
        );
    }

    public function down(): void
    {
        // Rows recorded as paid by coupon have no truthful value in the old
        // set. 'original' is the column's default and the least wrong of the
        // three, and it is only reachable by rolling back a released feature.
        DB::table('returns')->where('refund_method', 'coupon')->update(['refund_method' => 'original']);

        DB::statement(
            "ALTER TABLE `returns` MODIFY `refund_method` "
            ."ENUM('original', 'wallet', 'bank') NOT NULL DEFAULT 'original'"
        );

        Schema::table('returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refund_coupon_id');
            $table->dropColumn('refund_preference');
        });
    }
};
