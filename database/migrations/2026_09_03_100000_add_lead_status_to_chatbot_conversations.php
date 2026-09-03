<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give every chat lead a follow-up status the team can actually set.
 *
 * The Chat Leads screen could only show what the assistant inferred on its own
 * - "Buying intent" from is_lead, "Needs a human" from last_intent - and both
 * are written by the bot, never by a person. There was nowhere to record that
 * someone had picked a lead up, parked it, or won it, so the list read exactly
 * the same after a week of calls as it did on day one.
 *
 * Stored as a string rather than an enum on purpose: adding a value to a MySQL
 * enum needs an ALTER TABLE, which is the trap
 * 2026_09_02_000001_add_on_hold_status_to_orders_table had to dig orders back
 * out of. The allowed values live in ChatbotConversation::LEAD_STATUSES and are
 * enforced on the request instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('chatbot_conversations', 'lead_status')) {
                $table->string('lead_status', 20)->default('new')->after('is_lead');
            }
        });

        Schema::table('chatbot_conversations', function (Blueprint $table) {
            // Every future "show me the open ones" filter scans on this.
            $table->index('lead_status', 'chatbot_conversations_lead_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropIndex('chatbot_conversations_lead_status_index');
        });

        Schema::table('chatbot_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_conversations', 'lead_status')) {
                $table->dropColumn('lead_status');
            }
        });
    }
};
