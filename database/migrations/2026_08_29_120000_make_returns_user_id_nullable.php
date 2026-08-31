<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Checkout is guest-friendly, so an order can exist without a user. Returns
     * were tied to a user id, which meant a guest could never request one.
     */
    public function up(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Guest returns have no user, so they must go before the column can be
        // made NOT NULL again.
        \Illuminate\Support\Facades\DB::table('returns')->whereNull('user_id')->delete();

        Schema::table('returns', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
