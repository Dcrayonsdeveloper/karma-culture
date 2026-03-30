<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('title')->nullable()->after('name');
            $table->string('subtitle', 500)->nullable()->after('title');
            $table->string('button_text', 100)->nullable()->after('subtitle');
            $table->string('overlay_style', 50)->default('left-dark')->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn(['title', 'subtitle', 'button_text', 'overlay_style']);
        });
    }
};
