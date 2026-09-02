<?php

namespace Tests\Feature\Chatbot;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The floating widget kept the single-line `<input>` and `@keydown.enter.prevent`
 * it shipped with, so Shift+Enter could not open a second line — the browser has
 * nowhere to put a newline in a text input, and the handler swallowed the key
 * either way. It now matches the full-page composer: a textarea, Enter sends,
 * Shift+Enter breaks the line. See ChatComposerTest for the page version.
 */
class ChatWidgetComposerTest extends TestCase
{
    use RefreshDatabase;

    /** The widget rides on the storefront layout, and only signed-in shoppers get the composer. */
    private function storefrontHtml(): string
    {
        $user = User::factory()->create(['role' => 'customer']);

        return $this->actingAs($user)->get('/')->assertOk()->getContent();
    }

    public function test_composer_is_a_textarea_so_a_newline_has_somewhere_to_go(): void
    {
        $html = $this->storefrontHtml();

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('@keydown="composerKeydown($event)"', $html);
    }

    public function test_handler_sends_on_plain_enter_but_leaves_shift_enter_and_ime_keys_alone(): void
    {
        $html = $this->storefrontHtml();

        // Shift+Enter and mid-composition keydowns (Android soft keyboards raise
        // those with keyCode 229) fall through, so they still type a newline or
        // finish the word instead of firing the request.
        $this->assertStringContainsString('composerKeydown(event) {', $html);
        $this->assertStringContainsString("event.key !== 'Enter' && event.keyCode !== 13", $html);
        $this->assertStringContainsString('event.shiftKey || event.isComposing || event.keyCode === 229', $html);
    }

    public function test_alpines_unsupported_exact_modifier_is_not_used(): void
    {
        $this->assertStringNotContainsString('.exact', $this->storefrontHtml());
    }

    public function test_mobile_keyboard_is_asked_for_a_send_action_key(): void
    {
        $this->assertStringContainsString('enterkeyhint="send"', $this->storefrontHtml());
    }

    public function test_a_multiline_message_keeps_its_line_breaks_in_the_bubble(): void
    {
        // The bubble renders with x-text, which collapses whitespace unless the
        // element opts out — without this a Shift+Enter message came back as one
        // run-on line.
        $this->assertStringContainsString('whitespace-pre-wrap', $this->storefrontHtml());
    }
}
