<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2026_01_29_000046 already creates audit_logs, and its shape — the one
        // AuditLog and LogAdminActions actually write to, with description, url
        // and method nested inside the properties JSON — is what every existing
        // database has. This migration is a duplicate that never applied
        // anywhere; on a fresh database it would abort the whole migrate run,
        // so it stands down when the table is already there.
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action'); // created, updated, deleted, status_changed, login, etc.
            $table->string('model_type')->nullable(); // e.g. App\Models\Product
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        // Dropping here would destroy the table 2026_01_29_000046 owns.
    }
};
