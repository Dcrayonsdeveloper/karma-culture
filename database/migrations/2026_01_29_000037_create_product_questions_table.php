<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->boolean('is_answered')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->unsignedInteger('vote_count')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
        });

        Schema::create('product_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('product_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            $table->text('answer');
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_seller_response')->default(false);
            $table->unsignedInteger('vote_count')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_answers');
        Schema::dropIfExists('product_questions');
    }
};
