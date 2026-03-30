<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('gst_number', 20)->unique()->nullable();
            $table->string('pan_number', 15)->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->json('bank_details')->nullable();
            $table->json('documents')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
