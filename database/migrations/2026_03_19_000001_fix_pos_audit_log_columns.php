<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_audit_log', function (Blueprint $table) {
            // Add missing store_id (AuditController queries by it)
            $table->unsignedBigInteger('store_id')->nullable()->after('id');

            // Rename singular columns to plural to match AuditController code
            $table->renameColumn('old_value', 'old_values');
            $table->renameColumn('new_value', 'new_values');

            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('pos_audit_log', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'created_at']);
            $table->dropColumn('store_id');
            $table->renameColumn('old_values', 'old_value');
            $table->renameColumn('new_values', 'new_value');
        });
    }
};
