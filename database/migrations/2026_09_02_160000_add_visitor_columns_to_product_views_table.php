<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give product_views enough to answer the Analytics screen.
 *
 * The table was only ever written for signed-in customers, one row per
 * (user, product), so it could not say how many people were on the site, what
 * brought them, or what they browsed on. The tracking fix starts recording a
 * row per view including guests, which needs a device signal (user_agent) and
 * an index that suits "everything since date X" rather than the per-product
 * lookups the original indexes were built for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_views', function (Blueprint $table) {
            if (! Schema::hasColumn('product_views', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('referrer');
            }
        });

        Schema::table('product_views', function (Blueprint $table) {
            // Every Analytics query is a date-window scan; without this they
            // are full table scans once a row exists per page view.
            $table->index('created_at', 'product_views_created_at_index');
            $table->index(['session_id', 'created_at'], 'product_views_session_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_views', function (Blueprint $table) {
            $table->dropIndex('product_views_created_at_index');
            $table->dropIndex('product_views_session_created_index');
        });

        Schema::table('product_views', function (Blueprint $table) {
            if (Schema::hasColumn('product_views', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
        });
    }
};
