<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_images', function (Blueprint $table) {
            if (! Schema::hasColumn('review_images', 'media_type')) {
                $table->string('media_type', 10)->default('image')->after('review_id'); // image | video
            }
            if (! Schema::hasColumn('review_images', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('review_images', function (Blueprint $table) {
            foreach (['media_type', 'thumbnail_url'] as $col) {
                if (Schema::hasColumn('review_images', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
