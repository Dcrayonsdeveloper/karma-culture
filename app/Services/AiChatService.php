<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * One entry point for the storefront chatbot, so the rest of the app never
 * cares which vendor is answering.
 *
 * The key and model are read from Settings first and fall back to the .env
 * values, so the admin Integrations page can turn the bot on without a deploy.
 */
class AiChatService
{
    public const PROVIDER_ANTHROPIC = 'anthropic';
    public const PROVIDER_GEMINI    = 'gemini';

    public static function provider(): string
    {
        $provider = Setting::get('ai_provider', self::PROVIDER_ANTHROPIC);

        return in_array($provider, [self::PROVIDER_ANTHROPIC, self::PROVIDER_GEMINI], true)
            ? $provider
            : self::PROVIDER_ANTHROPIC;
    }

    public static function apiKey(): string
    {
        return self::provider() === self::PROVIDER_GEMINI
            ? (string) (Setting::get('gemini_api_key') ?: config('services.gemini.key'))
            : (string) (Setting::get('anthropic_api_key') ?: config('services.anthropic.key'));
    }

    public static function model(): string
    {
        return self::provider() === self::PROVIDER_GEMINI
            ? (string) (Setting::get('gemini_model') ?: config('services.gemini.model'))
            : (string) (Setting::get('anthropic_model') ?: config('services.anthropic.model'));
    }

    /** Whether the widget should render at all. */
    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    /**
     * Send a conversation and return the assistant's reply.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, reply: string}
     */
    public static function reply(string $systemPrompt, array $messages): array
    {
        $apiKey = self::apiKey();

        if ($apiKey === '') {
            return ['ok' => false, 'reply' => ''];
        }

        return self::provider() === self::PROVIDER_GEMINI
            ? self::callGemini($apiKey, self::model(), $systemPrompt, $messages)
            : self::callAnthropic($apiKey, self::model(), $systemPrompt, $messages);
    }

    private static function callAnthropic(string $apiKey, string $model, string $system, array $messages): array
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $model ?: 'claude-haiku-4-5-20251001',
                'max_tokens' => 1024,
                'system'     => $system,
                'messages'   => $messages,
            ]);

        if ($response->failed()) {
            Log::error('Chatbot: Anthropic API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['ok' => false, 'reply' => ''];
        }

        return ['ok' => true, 'reply' => (string) ($response->json('content.0.text') ?? '')];
    }

    /**
     * Gemini names the assistant turn "model" rather than "assistant", carries
     * the system prompt in its own field, and wraps every turn in parts[].
     */
    private static function callGemini(string $apiKey, string $model, string $system, array $messages): array
    {
        $contents = array_map(fn (array $m) => [
            'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $messages);

        $model = $model ?: 'gemini-3.6-flash';

        $response = Http::timeout(60)
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
                'content-type'   => 'application/json',
            ])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents'           => $contents,
                'generationConfig'   => ['maxOutputTokens' => 1024],
            ]);

        if ($response->failed()) {
            Log::error('Chatbot: Gemini API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['ok' => false, 'reply' => ''];
        }

        return ['ok' => true, 'reply' => (string) ($response->json('candidates.0.content.parts.0.text') ?? '')];
    }
}
