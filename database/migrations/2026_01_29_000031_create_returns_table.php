<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['return', 'exchange'])->default('return');
            $table->enum('status', ['requested', 'approved', 'rejected', 'pickup_scheduled', 'picked_up', 'received', 'processed', 'completed'])->default('requested');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->enum('refund_method', ['original', 'wallet', 'bank'])->default('original');
            $table->foreignId('exchange_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->string('reason')->nullable();
            $table->enum('condition', ['unopened', 'opened', 'damaged'])->default('opened');
            $table->enum('status', ['pending', 'approved', 'rejected', 'received'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
    }
};
