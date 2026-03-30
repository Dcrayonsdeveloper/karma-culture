<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesalers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('gst_number', 20)->unique();
            $table->enum('gst_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('pan_number', 15)->nullable();
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('available_credit', 12, 2)->default(0);
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('documents')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesalers');
    }
};
