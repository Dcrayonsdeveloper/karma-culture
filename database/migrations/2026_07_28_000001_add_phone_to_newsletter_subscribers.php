<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_subscribers', 'phone')) {
                $table->string('phone', 20)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            if (Schema::hasColumn('newsletter_subscribers', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
