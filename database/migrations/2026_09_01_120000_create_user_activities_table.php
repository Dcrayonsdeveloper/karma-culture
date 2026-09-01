<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * user_activities exists in production but had no migration, so a fresh
 * database never got it — every test that signed a user out died on the
 * missing table, and a new install would have failed the same way the first
 * time anything recorded a view or an add-to-cart.
 *
 * The columns and indexes below are taken from the live table so the two agree.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_activities')) {
            return;
        }

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
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
