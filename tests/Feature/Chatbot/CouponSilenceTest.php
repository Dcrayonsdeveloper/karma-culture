<?php

namespace Tests\Feature\Chatbot;

use App\Http\Controllers\ChatbotController;
use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * The assistant used to read every active coupon out of the database and list
 * the codes in its system prompt, under a heading telling it to share them
 * whenever anyone asked about deals. So "is there any coupon?" was answered
 * with live, working codes - including to customers who would have paid full
 * price, and on a public widget where one answer reaches everyone who asks.
 *
 * Codes are not the assistant's to give out. It now points at the offers the
 * site already publishes, and the prompt carries nothing to leak.
 */
class CouponSilenceTest extends TestCase
{
    use RefreshDatabase;

    private function buildSystemPrompt(): string
    {
        $class = new ReflectionClass(ChatbotController::class);
        $method = $class->getMethod('buildSystemPrompt');
        $method->setAccessible(true);

        return $method->invoke($class->newInstanceWithoutConstructor(), [], [], []);
    }

    private function isOffTopic(string $message): bool
    {
        $class = new ReflectionClass(ChatbotController::class);
        $method = $class->getMethod('isOffTopic');
        $method->setAccessible(true);

        return $method->invoke($class->newInstanceWithoutConstructor(), $message);
    }

    private function makeCoupon(string $code): Coupon
    {
        return Coupon::create([
            'code' => $code,
            'name' => 'Silent Test Coupon',
            'type' => 'percentage',
            'value' => 25,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function test_an_active_coupon_code_never_reaches_the_prompt(): void
    {
        $this->makeCoupon('SECRET25');

        $prompt = $this->buildSystemPrompt();

        $this->assertStringNotContainsString('SECRET25', $prompt, 'A live coupon code is being handed to the model.');
        $this->assertStringNotContainsStringIgnoringCase('Active Offers', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('coupon code', $prompt);
    }

    public function test_the_prompt_tells_the_model_to_refuse(): void
    {
        $prompt = $this->buildSystemPrompt();

        $this->assertStringContainsString(
            'Never give out a discount code',
            $prompt,
            'Without the instruction the model is free to invent a code, which is worse than quoting a real one.'
        );
        $this->assertStringContainsString(
            'applied at checkout',
            $prompt,
            'A refusal with nowhere to go reads as stonewalling; the reply has to point somewhere.'
        );
    }

    /**
     * The instruction has to survive a customer claiming prior authorisation -
     * "your colleague already gave me one" is the shape this gets talked around
     * with, and it costs one clause to close.
     */
    public function test_the_refusal_covers_a_customer_who_claims_they_were_promised_a_code(): void
    {
        $this->assertStringContainsString('promised a code earlier', $this->buildSystemPrompt());
    }

    /**
     * Coupon words stay in the on-topic vocabulary on purpose. If they were
     * dropped, "is there any coupon?" would hit the generic "I can only help
     * with things about this store" line - which is both wrong and unhelpful,
     * since the question IS about the store. It reaches the model, which
     * declines and points at the site.
     */
    public function test_asking_about_coupons_is_still_treated_as_a_shop_question(): void
    {
        $this->assertFalse($this->isOffTopic('is there any coupon?'));
        $this->assertFalse($this->isOffTopic('any discount available?'));
        $this->assertFalse($this->isOffTopic('what offers do you have'));
    }

    /**
     * The widget shipped a one-tap chip that asked the exact question the
     * assistant now declines. A button that invites a refusal is a worse
     * experience than no button.
     */
    public function test_no_quick_chip_invites_a_coupon_question(): void
    {
        $chips = (new ChatbotController)->quickChips();

        foreach ($chips as $chip) {
            $this->assertStringNotContainsStringIgnoringCase('coupon', $chip['message']);
            $this->assertStringNotContainsStringIgnoringCase('coupon', $chip['label']);
        }

        $this->assertNotEmpty($chips, 'The openers were emptied rather than replaced.');
    }

    /**
     * ...and the widget really renders those, rather than a second copy.
     *
     * quickChips() has always claimed in its docblock to be "shared by the
     * widget and the full page so the two surfaces never drift apart". They
     * had: the widget carried its own hardcoded list in Alpine, so removing
     * the coupon opener from the controller left the widget still offering
     * "Current Offers" to every shopper on the storefront.
     */
    public function test_the_widget_renders_the_controllers_openers_and_not_its_own(): void
    {
        config(['services.anthropic.key' => 'test-key']);

        $user = \App\Models\User::factory()->create(['role' => 'customer']);
        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase(
            'Current Offers',
            $html,
            'The widget is still offering the coupon opener from its own copy of the list.'
        );

        foreach ((new ChatbotController)->quickChips() as $chip) {
            $this->assertStringContainsString(
                $chip['message'],
                $html,
                'The widget is not rendering the controller\'s openers.'
            );
        }
    }

    /**
     * On a phone the panel was a 520px card floating above the launcher, so a
     * strip of the page showed underneath and the footer could be read through
     * the gap while chatting. Below the sm breakpoint an open panel fills the
     * viewport - verified in Chrome at 500x665: root and panel both measured
     * exactly the viewport, with the launcher hidden.
     */
    public function test_the_widget_goes_full_screen_on_a_phone(): void
    {
        config(['services.anthropic.key' => 'test-key']);

        $user = \App\Models\User::factory()->create(['role' => 'customer']);
        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('@media (max-width: 639px)', $html);
        $this->assertMatchesRegularExpression(
            '/\.chatbot-widget-root\.is-fullscreen\s*\{[^}]*bottom:\s*0/s',
            $html,
            'The open panel no longer clears the corner offsets, so the page shows under it.'
        );
        $this->assertMatchesRegularExpression(
            '/\.is-fullscreen \.chatbot-panel\s*\{[^}]*height:\s*100%/s',
            $html,
            'The panel is not filling the viewport height.'
        );
        $this->assertMatchesRegularExpression(
            '/\.is-fullscreen \.chatbot-launcher\s*\{[^}]*display:\s*none/s',
            $html,
            'The launcher still floats over the full-screen panel.'
        );
        $this->assertStringContainsString(
            "isOpen && 'is-fullscreen'",
            $html,
            'Nothing puts the class on the root, so the rules never apply.'
        );
    }
}
