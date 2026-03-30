<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->enum('type', ['view', 'search', 'add_to_cart', 'purchase', 'wishlist']);
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->string('query');
            $table->unsignedInteger('results_count')->default(0);
            $table->json('filters')->nullable();
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedSmallInteger('clicked_position')->nullable();
            $table->timestamps();

            $table->index(['query', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->string('referrer')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('fraud_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['multiple_accounts', 'suspicious_payment', 'unusual_activity', 'chargeback']);
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->json('indicators')->nullable();
            $table->enum('action', ['flagged', 'blocked', 'allowed'])->default('flagged');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_logs');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('user_activities');
    }
};
