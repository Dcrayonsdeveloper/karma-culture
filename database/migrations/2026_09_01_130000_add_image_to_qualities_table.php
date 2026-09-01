<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The quality cards were always styled as media cards — a 3:4 tile with a
     * cover image behind a bottom-up gradient — but there was nowhere to store
     * the image, so every card rendered as an empty brown box. This gives them
     * one.
     */
    public function up(): void
    {
        Schema::table('qualities', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('qualities', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
