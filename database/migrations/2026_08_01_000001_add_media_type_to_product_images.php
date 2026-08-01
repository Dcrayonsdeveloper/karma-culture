<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'media_type')) {
                // 'image' | 'video' — existing rows default to image (backward compatible).
                $table->string('media_type', 10)->default('image')->after('variant_id');
            }
            if (! Schema::hasColumn('product_images', 'thumbnail_url')) {
                // Optional poster/thumbnail for videos.
                $table->string('thumbnail_url')->nullable()->after('url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            foreach (['media_type', 'thumbnail_url'] as $col) {
                if (Schema::hasColumn('product_images', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
