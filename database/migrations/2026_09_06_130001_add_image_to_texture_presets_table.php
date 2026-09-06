<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('texture_presets', function (Blueprint $table) {
            // A texture reads as a fabric, not a name - the swatch an admin
            // uploads here is what the home rail hangs on the shirt instead of
            // painting it a flat colour. Storage-relative path on the public
            // disk, like every other admin upload.
            $table->string('image_path')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('texture_presets', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
