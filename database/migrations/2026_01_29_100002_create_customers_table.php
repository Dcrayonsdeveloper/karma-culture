<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('sms_consent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('status');
            $table->index('total_spent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
