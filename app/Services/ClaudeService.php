<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private const MAX_HISTORY = 20;

    public function generateReply(Lead $lead, string $message): string
    {
        $apiKey = config('services.anthropic.key');

        if (empty($apiKey)) {
            Log::error('Nia: Anthropic API key not configured');
            return "I'm currently unavailable. Please try again later!";
        }

        $systemPrompt = $this->buildSystemPrompt($lead);
        $messages = $this->buildMessageHistory($lead, $message);
        $model = Setting::get('nia_model', 'claude-sonnet-5');

        try {
            $response = Http::timeout(25)
                ->withHeaders([
                    'x-api-key'        => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'     => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $model,
                    'max_tokens' => 1024,
                    'system'     => $systemPrompt,
                    'messages'   => $messages,
                ]);

            if ($response->failed()) {
                Log::error('Nia: Anthropic API error', [
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                    'lead_id' => $lead->id,
                ]);
                return "I'm having trouble right now. Let me get back to you shortly!";
            }

            $data = $response->json();

            return $data['content'][0]['text']
                ?? "Sorry, I didn't catch that. Could you say that again?";

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Nia: Anthropic connection timeout', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
            return "I'm a little slow right now. Please try again in a moment!";
        }
    }

    private function buildSystemPrompt(Lead $lead): string
    {
        $customPrompt = Setting::get('nia_system_prompt', '');

        if (!empty($customPrompt)) {
            $prompt = $customPrompt;
        } else {
            $prompt = $this->defaultSystemPrompt();
        }

        // Append lead context
        $prompt .= "\n\n## Current Customer\n";
        $prompt .= "- Platform: {$lead->platform}\n";
        $prompt .= '- Name: ' . ($lead->name ?? 'Unknown') . "\n";
        $prompt .= "- Stage: {$lead->stage}\n";

        if ($lead->notes) {
            $prompt .= "- Previous context: {$lead->notes}\n";
        }

        if ($lead->tags) {
            $prompt .= '- Tags: ' . implode(', ', $lead->tags) . "\n";
        }

        // AI command instructions
        $prompt .= "\n## Special Commands\n"
            . "You can embed these commands anywhere in your response. "
            . "They will be stripped before sending to the customer:\n"
            . "- [NIA_QUALIFIED] - Use when the customer shows strong buying intent or is ready to purchase.\n"
            . "- [SCHEDULE_CALL] - Use when the customer explicitly requests a callback or phone consultation.\n"
            . "- [LEAD_CONTEXT:description] - Use to save important context about this lead "
            . "(e.g., [LEAD_CONTEXT:Looking for party dresses for 5-year-old daughter, budget 2000-3000]).\n\n"
            . "Only use these when truly appropriate. Do not overuse them.\n";

        return $prompt;
    }

    /**
     * The prompt Nia answers from when no custom one is configured.
     *
     * The store facts in here used to be typed into the string: the brand name,
     * the site URL, the free-shipping threshold, the return window and the COD
     * cap. They drifted, and Nia is the one part of the site that states them
     * out loud to a customer - it was quoting free shipping over Rs 499 while
     * ShippingCharge was charging everything under Rs 999, which is a promise
     * the checkout then breaks. They come from the same settings the header,
     * the product page and the checkout read now, so there is one answer.
     */
    private function defaultSystemPrompt(): string
    {
        $store        = Setting::get('site_name', config('app.name'));
        $site         = rtrim(config('app.url'), '/');
        $freeShipping = (int) Setting::get('free_shipping_threshold', 999);
        $returnDays   = (int) Setting::get('return_window_days', 7);
        $codMax       = number_format((int) Setting::get('cod_max_amount', 5000));

        $shipping = $freeShipping > 0
            ? '- Free shipping on orders above ₹' . number_format($freeShipping)
            : '- Free shipping on all orders';

        return <<<PROMPT
You are Nia, the friendly AI sales and support assistant for {$store} - a premium fashion e-commerce store in India.

## Your Personality
- Warm, attentive, and enthusiastic about helping customers find pieces they will love.
- Professional but conversational - this is social media messaging, keep it natural.
- Smart, persuasive but never pushy. Guide customers towards making a purchase.
- Concise: keep responses under 100 words for chat platforms. No long paragraphs.
- Use emojis sparingly and naturally (1-2 per message max).
- Never fabricate product details, prices, or policies.

## What You Do
- Answer questions about products, sizes, availability, pricing.
- Recommend products based on style, fit, size, and occasion.
- Qualify leads by understanding their needs, budget, and timeline.
- Handle objections gracefully (price concerns, sizing doubts, shipping questions).
- Close sales by guiding customers to the website or sharing product links.
- Schedule callbacks when customers prefer speaking with a human.
- Track and remember context about each customer across conversations.

## Store Information
- Website: {$site}
{$shipping}
- {$returnDays}-day return policy (unused items with tags)
- Payments: UPI, cards, net banking, wallets, COD (up to ₹{$codMax})
- Sizes: XS to XXL (see the size guide for measurements)
- Size guide: {$site}/size-guide
- Contact: available via Instagram, Facebook, and WhatsApp

## Response Style
- Plain text only. Use bullet points (- ) for lists.
- Bold (**text**) only for prices, coupon codes, or critical info.
- No markdown headers. Keep it conversational.
- End messages with a soft call-to-action when appropriate.
- If unsure about something, be honest and offer to connect with the team.
PROMPT;
    }

    private function buildMessageHistory(Lead $lead, string $currentMessage): array
    {
        $chats = $lead->chats()
            ->orderBy('created_at', 'desc')
            ->limit(self::MAX_HISTORY)
            ->get()
            ->sortBy('created_at')
            ->values();

        $messages = [];
        $lastRole = null;

        foreach ($chats as $chat) {
            $role = $chat->sender === 'customer' ? 'user' : 'assistant';

            // Anthropic requires strictly alternating roles
            if ($role === $lastRole && !empty($messages)) {
                $messages[count($messages) - 1]['content'] .= "\n" . $chat->message;
            } else {
                $messages[] = [
                    'role'    => $role,
                    'content' => $chat->message,
                ];
                $lastRole = $role;
            }
        }

        // Append current message as user turn
        if ($lastRole === 'user' && !empty($messages)) {
            $messages[count($messages) - 1]['content'] .= "\n" . $currentMessage;
        } else {
            $messages[] = ['role' => 'user', 'content' => $currentMessage];
        }

        return $messages;
    }
}
