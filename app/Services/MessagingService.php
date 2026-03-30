<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadChat;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessagingService
{
    public function __construct(
        private ClaudeService $claude
    ) {}

    /**
     * Orchestrate the full incoming message flow:
     * Deduplicate → find/create lead → store message → AI reply → send → store reply
     */
    public function processIncoming(
        string $platform,
        string $senderId,
        string $message,
        ?string $messageId = null
    ): array {
        // Deduplicate by platform_message_id
        if ($messageId && LeadChat::where('platform_message_id', $messageId)->exists()) {
            Log::info('Nia: Duplicate message skipped', ['message_id' => $messageId]);
            return ['status' => 'duplicate'];
        }

        $lead = $this->findOrCreateLead($platform, $senderId);

        // Store customer message
        LeadChat::create([
            'lead_id'             => $lead->id,
            'sender'              => 'customer',
            'message'             => $message,
            'platform_message_id' => $messageId,
        ]);

        // Check if Nia is enabled
        if (!Setting::get('nia_enabled', true)) {
            return ['status' => 'bot_disabled', 'lead_id' => $lead->id];
        }

        // Generate AI reply
        $rawReply = $this->claude->generateReply($lead, $message);

        // Process AI commands (strips them from reply, executes side effects)
        $cleanReply = $this->processAiCommands($lead, $rawReply);

        // Store bot reply
        LeadChat::create([
            'lead_id' => $lead->id,
            'sender'  => 'bot',
            'message' => $cleanReply,
        ]);

        // Send reply back to platform
        $sent = $this->sendReply($lead, $cleanReply);

        return [
            'status'  => $sent ? 'sent' : 'send_failed',
            'lead_id' => $lead->id,
            'reply'   => $cleanReply,
        ];
    }

    /**
     * Find existing lead or create new one by platform + platform_id.
     */
    public function findOrCreateLead(string $platform, string $platformId): Lead
    {
        return Lead::firstOrCreate(
            ['platform' => $platform, 'platform_id' => $platformId],
            ['stage' => 'new']
        );
    }

    /**
     * Send reply back to the customer via the appropriate Meta API.
     */
    public function sendReply(Lead $lead, string $reply): bool
    {
        $token = config('services.meta.page_access_token');

        if (empty($token)) {
            Log::error('Nia: META_PAGE_ACCESS_TOKEN not configured');
            return false;
        }

        try {
            if ($lead->platform === 'whatsapp') {
                return $this->sendWhatsAppMessage($lead->platform_id, $reply, $token);
            }

            // Instagram and Facebook Messenger use the same Send API
            return $this->sendMessengerMessage($lead->platform_id, $reply, $token);

        } catch (\Throwable $e) {
            Log::error('Nia: Failed to send reply', [
                'lead_id'  => $lead->id,
                'platform' => $lead->platform,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Parse AI commands from the response and execute side effects.
     * Returns the cleaned reply with commands stripped.
     */
    public function processAiCommands(Lead $lead, string $reply): string
    {
        $clean = $reply;

        // [NIA_QUALIFIED] — advance lead stage
        if (str_contains($clean, '[NIA_QUALIFIED]')) {
            $lead->update(['stage' => 'qualified']);
            $clean = str_replace('[NIA_QUALIFIED]', '', $clean);
            Log::info('Nia: Lead qualified', ['lead_id' => $lead->id]);
        }

        // [SCHEDULE_CALL] — flag for follow-up
        if (str_contains($clean, '[SCHEDULE_CALL]')) {
            $existingTags = $lead->tags ?? [];
            if (!in_array('callback_requested', $existingTags)) {
                $lead->update([
                    'tags' => array_merge($existingTags, ['callback_requested']),
                ]);
            }
            $clean = str_replace('[SCHEDULE_CALL]', '', $clean);
            Log::info('Nia: Callback requested', ['lead_id' => $lead->id]);
        }

        // [LEAD_CONTEXT:...] — save context to notes
        if (preg_match_all('/\[LEAD_CONTEXT:(.+?)\]/', $clean, $matches)) {
            foreach ($matches[1] as $context) {
                $context = trim($context);
                $existing = $lead->notes ?? '';
                $lead->update([
                    'notes' => trim($existing . "\n" . $context),
                ]);
            }
            $clean = preg_replace('/\[LEAD_CONTEXT:.+?\]/', '', $clean);
            Log::info('Nia: Lead context saved', ['lead_id' => $lead->id]);
        }

        return trim($clean);
    }

    /**
     * Send message via Instagram / Facebook Messenger Send API.
     */
    private function sendMessengerMessage(string $recipientId, string $message, string $token): bool
    {
        $response = Http::post(
            "https://graph.facebook.com/v21.0/me/messages?access_token={$token}",
            [
                'recipient' => ['id' => $recipientId],
                'message'   => ['text' => $message],
            ]
        );

        if ($response->failed()) {
            Log::error('Nia: Messenger/Instagram send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send message via WhatsApp Cloud API.
     */
    private function sendWhatsAppMessage(string $recipientPhone, string $message, string $token): bool
    {
        $phoneNumberId = config('services.meta.whatsapp_phone_number_id');

        if (empty($phoneNumberId)) {
            Log::error('Nia: META_WHATSAPP_PHONE_NUMBER_ID not configured');
            return false;
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $recipientPhone,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if ($response->failed()) {
            Log::error('Nia: WhatsApp send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        return true;
    }
}
