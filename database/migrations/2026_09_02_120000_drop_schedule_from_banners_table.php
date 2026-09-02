<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banners are shown or hidden by the Active switch alone.
 *
 * The start/end window was a second, invisible way for a banner to be off:
 * the switch said Active while the storefront showed nothing. Dropping the
 * columns removes that contradiction rather than leaving dead ones behind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Named explicitly: the index was created as a composite over both
            // columns, and MySQL will not drop a column an index still covers.
            $table->dropIndex('banners_starts_at_ends_at_index');
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('is_active');
            $table->timestamp('ends_at')->nullable()->after('starts_at');

            $table->index(['starts_at', 'ends_at']);
        });
    }
};
