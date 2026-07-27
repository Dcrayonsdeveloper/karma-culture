<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'feature_highlights')) {
                // Array of { image, heading, caption } rows rendered as Amazon/Flipkart-style
                // image-wise feature blocks on the PDP (Task 12).
                $table->json('feature_highlights')->nullable()->after('specifications');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'feature_highlights')) {
                $table->dropColumn('feature_highlights');
            }
        });
    }
};
