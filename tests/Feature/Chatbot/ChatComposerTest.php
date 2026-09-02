<?php

namespace Tests\Feature\Chatbot;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The composer was bound with `@keydown.enter.exact.prevent`, but Alpine has no
 * `.exact` modifier — it read "exact" as a second key name, so the combination
 * could never be pressed and the handler (including its `.prevent`) never ran.
 * Enter dropped a newline into the textarea and the message was never sent.
 */
class ChatComposerTest extends TestCase
{
    use RefreshDatabase;

    private function chatPage(): string
    {
        $user = User::factory()->create(['role' => 'customer']);

        return $this->actingAs($user)->get(route('chat'))->assertOk()->getContent();
    }

    public function test_composer_does_not_use_alpines_unsupported_exact_modifier(): void
    {
        $this->assertStringNotContainsString('.exact', $this->chatPage());
    }

    public function test_enter_is_bound_to_a_handler_that_sends(): void
    {
        $html = $this->chatPage();

        $this->assertStringContainsString('@keydown="composerKeydown($event)"', $html);
        $this->assertStringContainsString('composerKeydown(event) {', $html);
    }

    public function test_handler_sends_on_plain_enter_but_leaves_shift_enter_and_ime_keys_alone(): void
    {
        $html = $this->chatPage();

        // Plain Enter is intercepted; Shift+Enter and mid-composition keydowns
        // (Android soft keyboards raise those with keyCode 229) fall through so
        // they still type a newline / finish the word.
        $this->assertStringContainsString("event.key !== 'Enter' && event.keyCode !== 13", $html);
        $this->assertStringContainsString('event.shiftKey || event.isComposing || event.keyCode === 229', $html);
        $this->assertStringContainsString('event.preventDefault();', $html);
    }

    public function test_mobile_keyboard_is_asked_for_a_send_action_key(): void
    {
        $this->assertStringContainsString('enterkeyhint="send"', $this->chatPage());
    }
}
