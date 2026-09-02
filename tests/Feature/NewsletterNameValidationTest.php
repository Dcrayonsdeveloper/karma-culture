<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The offer popup labelled its name input "(optional)" and sent no name rules
 * at all, so a lead could land with no name, or with "John123" / "evil.com" /
 * "<script>" in the column an admin reads. The endpoint is shared: the
 * exit-intent popup posts email + phone with no name field, so the requirement
 * is bound to the source that renders the input, while the charset guard
 * applies to every source whenever a name is present.
 */
class NewsletterNameValidationTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(array $payload)
    {
        return $this->postJson('/newsletter/subscribe', $payload);
    }

    private function offerPopup(array $overrides = [])
    {
        return $this->subscribe(array_merge([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'source' => 'offer_popup',
        ], $overrides));
    }

    // ---- the reported bug ------------------------------------------------

    public function test_offer_popup_rejects_a_missing_name(): void
    {
        $this->offerPopup(['name' => null])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Please enter your name.');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_offer_popup_rejects_a_whitespace_only_name(): void
    {
        $this->offerPopup(['name' => '   '])->assertStatus(422);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_offer_popup_accepts_a_real_name(): void
    {
        $this->offerPopup()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'john@example.com',
            'name' => 'John Smith',
            'source' => 'offer_popup',
        ]);
    }

    // ---- the charset guard, one case per rejection reason -----------------

    public static function rejectedNames(): array
    {
        return [
            'digits' => ['John123'],
            'symbols' => ['John@#$'],
            'html' => ['John <script>alert(1)</script>'],
            'sql payload' => ["Robert'); DROP TABLE users;--"],
            'emoji' => ['John 😀'],
            'leading hyphen' => ['-John'],
            'leading apostrophe' => ["'John"],
            'leading period' => ['.John'],
            'single character' => ['J'],
            'over 100 characters' => [str_repeat('ab', 51)],
            'bare domain' => ['evil.com'],
            'www prefix' => ['www.evil'],
            'url scheme' => ['http://evil.co/x'],
            'javascript scheme' => ['javascript:alert(1)'],
            'keyboard mashing' => ['Aaaaaa'],
        ];
    }

    /**
     * @dataProvider rejectedNames
     */
    public function test_offer_popup_rejects_junk_names(string $name): void
    {
        $this->offerPopup(['name' => $name])->assertStatus(422);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public static function acceptedNames(): array
    {
        return [
            'plain' => ['John Smith'],
            'two characters' => ['Jo'],
            'hyphenated' => ['Mary-Anne'],
            'straight apostrophe' => ["O'Connor"],
            'curly apostrophe' => ['O’Connor'],
            'initials with periods' => ['J. R. Smith'],
            'accented latin' => ['José'],
            'devanagari with matras' => ['रवि कुमार'],
            'cjk' => ['山田太郎'],
            'vietnamese tone marks' => ['Nguyễn'],
            'non-breaking space' => ["John\u{00A0}Smith"],
            'exactly 100 characters' => [str_repeat('ab', 50)],
        ];
    }

    /**
     * @dataProvider acceptedNames
     */
    public function test_offer_popup_accepts_real_world_names(string $name): void
    {
        $this->offerPopup(['name' => $name])->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('newsletter_subscribers', ['name' => $name]);
    }

    // ---- the other forms on the same endpoint ----------------------------

    public function test_exit_intent_still_works_without_a_name(): void
    {
        // This popup has no name input at all; requiring one here would have
        // rejected every claim it makes.
        $this->subscribe([
            'email' => 'exit@example.com',
            'phone' => '9876543210',
            'source' => 'exit_intent',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'exit@example.com',
            'source' => 'exit_intent',
        ]);
    }

    public function test_a_source_without_a_name_field_still_rejects_a_junk_name(): void
    {
        // Optional does not mean unvalidated: a direct POST cannot slip markup
        // into the subscriber list by claiming a different source.
        $this->subscribe([
            'name' => '<script>alert(1)</script>',
            'email' => 'exit@example.com',
            'source' => 'exit_intent',
        ])->assertStatus(422);

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_an_existing_subscriber_cannot_be_renamed_to_junk(): void
    {
        NewsletterSubscriber::create([
            'email' => 'john@example.com',
            'name' => 'John Smith',
            'is_active' => true,
            'subscribed_at' => now(),
            'source' => 'footer',
        ]);

        $this->offerPopup(['name' => 'evil.com'])->assertStatus(422);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'john@example.com',
            'name' => 'John Smith',
        ]);
    }

    // ---- the markup the popup renders ------------------------------------

    public function test_the_popup_no_longer_advertises_the_name_as_optional(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        if (! str_contains($html, 'offer-name')) {
            $this->markTestSkipped('The offer popup is disabled in settings.');
        }

        $this->assertStringNotContainsString('Your name (optional)', $html);
        $this->assertStringContainsString('Your name *', $html);
    }
}
