<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->foreignId('pickup_partner_id')->nullable()->after('processed_by')
                ->constrained('delivery_partners')->nullOnDelete();
            $table->timestamp('pickup_scheduled_at')->nullable()->after('approved_at');
            $table->timestamp('picked_up_at')->nullable()->after('pickup_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropForeign(['pickup_partner_id']);
            $table->dropColumn(['pickup_partner_id', 'pickup_scheduled_at', 'picked_up_at']);
        });
    }
};
