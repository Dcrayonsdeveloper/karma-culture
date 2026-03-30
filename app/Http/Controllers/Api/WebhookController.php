<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private MessagingService $messaging
    ) {}

    /**
     * GET /api/webhook/meta
     * Meta webhook verification endpoint.
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.meta.verify_token')) {
            Log::info('Nia: Webhook verified successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Nia: Webhook verification failed', [
            'mode'        => $mode,
            'token_match' => $token === config('services.meta.verify_token'),
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST /api/webhook/meta
     * Handle incoming messages from Instagram, Facebook Messenger, and WhatsApp.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('Nia: Webhook payload received', [
            'object' => $payload['object'] ?? 'unknown',
        ]);

        if (empty($payload['entry'])) {
            return response()->json(['status' => 'ignored']);
        }

        foreach ($payload['entry'] as $entry) {
            // Instagram & Facebook Messenger events
            if (!empty($entry['messaging'])) {
                foreach ($entry['messaging'] as $event) {
                    $this->handleMessagingEvent($event, $payload['object'] ?? 'page');
                }
            }

            // WhatsApp Business events
            if (!empty($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    if (($change['field'] ?? '') === 'messages') {
                        $this->handleWhatsAppChange($change['value'] ?? []);
                    }
                }
            }
        }

        // Always return 200 to prevent Meta from retrying
        return response()->json(['status' => 'ok']);
    }

    /**
     * Process an Instagram or Facebook Messenger messaging event.
     */
    private function handleMessagingEvent(array $event, string $object): void
    {
        // Skip echo messages (sent by the page itself)
        if (!empty($event['message']['is_echo'])) {
            return;
        }

        // Skip non-text messages (images, stickers, attachments, etc.)
        if (empty($event['message']['text'])) {
            return;
        }

        $senderId  = $event['sender']['id'] ?? null;
        $text      = $event['message']['text'];
        $messageId = $event['message']['mid'] ?? null;

        if (!$senderId) {
            return;
        }

        // Determine platform from the webhook object field
        $platform = ($object === 'instagram') ? 'instagram' : 'facebook';

        try {
            $this->messaging->processIncoming($platform, $senderId, $text, $messageId);
        } catch (\Throwable $e) {
            Log::error('Nia: Error processing messaging event', [
                'platform' => $platform,
                'sender'   => $senderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process WhatsApp Business API incoming messages.
     */
    private function handleWhatsAppChange(array $value): void
    {
        $messages = $value['messages'] ?? [];

        foreach ($messages as $msg) {
            // Only handle text messages
            if (($msg['type'] ?? '') !== 'text') {
                continue;
            }

            $from      = $msg['from'] ?? null;
            $text      = $msg['text']['body'] ?? '';
            $messageId = $msg['id'] ?? null;

            if (!$from || empty($text)) {
                continue;
            }

            // Try to get the contact name from WhatsApp payload
            $contactName = null;
            $contacts = $value['contacts'] ?? [];
            foreach ($contacts as $contact) {
                if (($contact['wa_id'] ?? '') === $from) {
                    $contactName = $contact['profile']['name'] ?? null;
                    break;
                }
            }

            try {
                $result = $this->messaging->processIncoming('whatsapp', $from, $text, $messageId);

                // Update lead name if we got it from WhatsApp and lead doesn't have one
                if ($contactName && isset($result['lead_id'])) {
                    $lead = \App\Models\Lead::find($result['lead_id']);
                    if ($lead && !$lead->name) {
                        $lead->update(['name' => $contactName]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Nia: Error processing WhatsApp message', [
                    'from'  => $from,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
