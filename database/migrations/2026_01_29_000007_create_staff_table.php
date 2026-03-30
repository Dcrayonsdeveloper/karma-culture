<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('employee_id', 20)->unique();
            $table->enum('role', ['manager', 'cashier', 'support', 'warehouse'])->default('cashier');
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['store_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
