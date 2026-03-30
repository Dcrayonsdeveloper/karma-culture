<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('platform', ['instagram', 'facebook', 'whatsapp']);
            $table->string('platform_id');
            $table->enum('stage', ['new', 'qualifying', 'qualified', 'proposal', 'closed'])->default('new');
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['platform', 'platform_id']);
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
