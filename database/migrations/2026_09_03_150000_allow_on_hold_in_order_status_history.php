<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * order_status_history.status could not hold every status an order can be in.
 *
 * orders.status gained `on_hold` and the history table never did, so
 * Order::updateStatus('on_hold') wrote the order and then threw on the history
 * insert - "Data truncated for column 'status'". Putting an order on hold from
 * the admin has been a 500 for as long as the two have disagreed.
 *
 * Widened to match rather than narrowing the order, because the value is
 * already in use on orders. The two lists are written out in full here so the
 * next person to add a status can see they have to add it in both places.
 */
return new class extends Migration
{
    private const STATUSES = "'pending','on_hold','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned'";

    private const WITHOUT_ON_HOLD = "'pending','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned'";

    public function up(): void
    {
        DB::statement(
            'ALTER TABLE order_status_history MODIFY status ENUM('.self::STATUSES.') NOT NULL'
        );
    }

    public function down(): void
    {
        // Rows recorded while on hold would not fit the narrower list.
        DB::table('order_status_history')->where('status', 'on_hold')->delete();

        DB::statement(
            'ALTER TABLE order_status_history MODIFY status ENUM('.self::WITHOUT_ON_HOLD.') NOT NULL'
        );
    }
};
