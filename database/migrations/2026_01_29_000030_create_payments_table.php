<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id', 100)->unique();
            $table->string('gateway', 50)->default('razorpay');
            $table->string('gateway_transaction_id')->nullable();
            $table->enum('method', ['card', 'upi', 'netbanking', 'wallet', 'cod', 'emi', 'bnpl'])->default('card');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['pending', 'authorized', 'captured', 'failed', 'refunded'])->default('pending');
            $table->json('gateway_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('gateway_transaction_id');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('refund_id', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['full', 'partial'])->default('partial');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('reason')->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
