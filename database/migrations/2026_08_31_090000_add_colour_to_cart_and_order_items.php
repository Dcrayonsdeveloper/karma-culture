<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colour sits alongside size rather than inside the attributes JSON: a cart
     * line is matched on product + variant + size, and colour has to join that
     * key so red and blue of the same size stay separate lines.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('colour', 60)->nullable()->after('size');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('colour', 60)->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('colour');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('colour');
        });
    }
};
