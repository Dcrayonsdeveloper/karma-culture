<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // .glb file — used by Android Scene Viewer and <model-viewer> on desktop/web
            $table->string('model_glb_path', 500)->nullable()->after('height');
            // .usdz file — used by iOS AR QuickLook
            $table->string('model_usdz_path', 500)->nullable()->after('model_glb_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['model_glb_path', 'model_usdz_path']);
        });
    }
};
