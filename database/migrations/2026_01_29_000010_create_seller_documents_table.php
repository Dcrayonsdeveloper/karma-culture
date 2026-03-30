<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['gst_certificate', 'pan_card', 'bank_statement', 'address_proof', 'other']);
            $table->string('file_url');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_documents');
    }
};
