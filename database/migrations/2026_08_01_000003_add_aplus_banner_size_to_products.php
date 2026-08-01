<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // How the A+ banners are sized on the product page:
            //   'fit'    -> each banner capped to one screen height (default)
            //   'full'   -> edge-to-edge full width (may exceed one screen)
            //   'custom' -> capped to aplus_banner_max_height pixels
            $table->string('aplus_banner_size', 20)->default('fit')->after('feature_highlights');
            $table->unsignedInteger('aplus_banner_max_height')->nullable()->after('aplus_banner_size');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['aplus_banner_size', 'aplus_banner_max_height']);
        });
    }
};
