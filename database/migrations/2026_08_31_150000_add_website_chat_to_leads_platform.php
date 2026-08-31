<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The platform enum predates the storefront assistant and only covered the
     * social channels. A lead captured in the website chat has nowhere valid to
     * go without this, and the insert fails on a strict connection.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `leads` MODIFY `platform` ENUM('instagram','facebook','whatsapp','website_chat') NOT NULL");
    }

    public function down(): void
    {
        DB::table('leads')->where('platform', 'website_chat')->delete();
        DB::statement("ALTER TABLE `leads` MODIFY `platform` ENUM('instagram','facebook','whatsapp') NOT NULL");
    }
};
