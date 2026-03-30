<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('partner_id', 20)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('company_name', 100)->nullable();
            $table->enum('vehicle_type', ['bike', 'scooter', 'van', 'truck', 'other'])->default('bike');
            $table->string('vehicle_number', 30)->nullable();
            $table->string('license_number', 50)->nullable();
            $table->string('profile_photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_partners');
    }
};
