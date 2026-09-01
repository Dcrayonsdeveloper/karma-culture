<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-controlled display size for A+ banners.
     *
     * Deliberately separate from the existing `width`/`height` columns: those
     * hold the image's intrinsic pixel dimensions and are emitted as the HTML
     * width/height attributes so the browser can reserve layout space (avoids
     * CLS). Overloading them with CSS values would break that.
     *
     * Stored as short strings because a value may be a length with any unit or
     * the keyword "auto" - e.g. "800px", "50%", "auto". Format is enforced by
     * validation in the controller and re-checked before rendering.
     */
    public function up(): void
    {
        Schema::table('product_aplus_images', function (Blueprint $table) {
            $table->string('display_width', 20)->nullable()->after('height');
            $table->string('display_height', 20)->nullable()->after('display_width');
        });
    }

    public function down(): void
    {
        Schema::table('product_aplus_images', function (Blueprint $table) {
            $table->dropColumn(['display_width', 'display_height']);
        });
    }
};
