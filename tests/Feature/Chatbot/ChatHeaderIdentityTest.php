<?php

namespace Tests\Feature\Chatbot;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Both chat surfaces used to head themselves "Shopping Assistant", which said
 * what the page is but not who the customer is talking to. They carry the
 * shop's own name now, which the owner sets in Settings.
 *
 * The full page also drew its avatar as the site logo on a #2D1810 fill. The
 * logo is a dark mark on transparent, so the two cancelled out and left a dark
 * blob with nothing legible in it - the floating widget had always used a white
 * disc, and now both do.
 */
class ChatHeaderIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        config(['services.anthropic.key' => 'test-key']);

        return $this->actingAs(User::factory()->create(['role' => 'customer']))->user ?? User::firstOrFail();
    }

    private function chatPageHtml(): string
    {
        $this->signIn();

        return $this->get('/chat')->assertOk()->getContent();
    }

    private function storefrontHtml(): string
    {
        $this->signIn();

        return $this->get('/')->assertOk()->getContent();
    }

    public function test_the_full_page_is_headed_with_the_shop_name(): void
    {
        Setting::create(['key' => 'site_name', 'value' => 'Karmaa Kulture', 'group' => 'general', 'type' => 'string']);

        $html = $this->chatPageHtml();

        $this->assertMatchesRegularExpression(
            '/<h1[^>]*>\s*Karmaa Kulture\s*<\/h1>/',
            $html,
            'The conversation is still headed by a generic label rather than the shop.'
        );
        $this->assertStringContainsString('Shopping assistant &middot; Online', $html);
    }

    public function test_the_widget_is_headed_with_the_same_name(): void
    {
        Setting::create(['key' => 'site_name', 'value' => 'Karmaa Kulture', 'group' => 'general', 'type' => 'string']);

        $html = $this->storefrontHtml();

        $this->assertMatchesRegularExpression(
            '/kk-chat-header-title[^>]*>Karmaa Kulture</',
            $html,
            'The widget and /chat introduce themselves as two different things.'
        );
    }

    /**
     * With no site_name saved the header must still name a shop. config
     * ('app.name') is "Laravel" out of the box, and a customer reading that in
     * the chat header is worse than a slightly stale brand default.
     */
    public function test_an_unset_site_name_never_falls_back_to_the_framework_default(): void
    {
        $this->assertNull(Setting::where('key', 'site_name')->first());

        $html = $this->chatPageHtml();

        $this->assertStringNotContainsString('>Laravel<', $html);
        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*Karmaa Kulture\s*<\/h1>/', $html);
    }

    /**
     * The avatar has to be a light disc, or the dark logo is invisible on it.
     */
    public function test_the_chat_page_avatar_is_not_a_dark_disc(): void
    {
        $html = $this->chatPageHtml();

        $this->assertStringNotContainsString(
            'rounded-full bg-[#2D1810]',
            $html,
            'The avatar is back on the espresso fill, which hides the logo inside it.'
        );
        $this->assertMatchesRegularExpression(
            '/rounded-full bg-white[^"]*ring-1/',
            $html,
            'The avatar disc lost the white fill and ring that make the logo readable.'
        );
    }
}
