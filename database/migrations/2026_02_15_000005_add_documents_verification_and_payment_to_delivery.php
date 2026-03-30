<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->string('id_proof', 255)->nullable()->after('profile_photo');
            $table->string('license_document', 255)->nullable()->after('id_proof');
            $table->string('address_proof', 255)->nullable()->after('license_document');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->after('address_proof');
            $table->text('verification_note')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_note');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('payment_collected')->default(false)->after('paid_amount');
            $table->timestamp('payment_collected_at')->nullable()->after('payment_collected');
            $table->unsignedBigInteger('payment_collected_by')->nullable()->after('payment_collected_at');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->dropColumn(['id_proof', 'license_document', 'address_proof', 'verification_status', 'verification_note', 'verified_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_collected', 'payment_collected_at', 'payment_collected_by']);
        });
    }
};
