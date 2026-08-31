<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storefront chats were never recorded: history lived only in the visitor's
     * browser and vanished with the tab. Nothing could be reported on, and no
     * lead could be followed up. These two tables are the foundation for both.
     */
    public function up(): void
    {
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('message_count')->default(0);
            // Set when the assistant judges the visitor is close to buying.
            $table->boolean('is_lead')->default(false);
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('last_intent', 60)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            // Which products the reply put in front of the customer, so the
            // dashboard can rank what people actually ask about.
            $table->json('product_ids')->nullable();
            $table->unsignedSmallInteger('response_ms')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('chatbot_product_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained('chatbot_conversations')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_product_clicks');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
    }
};
