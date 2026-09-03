<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The About Us section shows three video cards, and there was no way to take
 * one down.
 *
 * The path field on Site Settings is readonly - written by the upload beside
 * it - so a clip could only ever be replaced with another one, never removed.
 * And the home page fell back to a bundled default whenever a slot read blank,
 * so even clearing the row by hand would have put a video straight back.
 *
 * A cleared slot now keeps its settings row holding an empty string, which is
 * how the page tells "the admin removed this" from "nobody ever set it".
 */
class AboutVideoRemovalTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Karmaa Kulture',
            'about_us_video_url' => 'storage/storefront/about/one.mp4',
            'about_us_video_url_2' => 'storage/storefront/about/two.mp4',
            'about_us_video_url_3' => 'storage/storefront/about/three.mp4',
        ], $overrides);
    }

    private function saveSettings(array $overrides = []): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.homepage.site-settings.update'), $this->settingsPayload($overrides))
            ->assertSessionHasNoErrors();
    }

    public function test_the_form_offers_a_remove_control_for_a_slot_that_has_a_video(): void
    {
        $this->saveSettings();

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.homepage.site-settings'))->assertOk()->getContent();

        $this->assertStringContainsString('name="about_us_video_remove"', $html);
        $this->assertStringContainsString('name="about_us_video_remove_2"', $html);
        $this->assertStringContainsString('name="about_us_video_remove_3"', $html);
        $this->assertStringContainsString('Remove this video', $html);
    }

    public function test_removing_a_video_clears_the_setting_without_deleting_the_row(): void
    {
        $this->saveSettings();

        $this->saveSettings(['about_us_video_remove_2' => 1]);

        $row = Setting::where('key', 'about_us_video_url_2')->first();

        $this->assertNotNull($row, 'The row has to survive, or the page treats the slot as never configured.');
        $this->assertSame('', (string) $row->value);
    }

    public function test_a_removed_video_disappears_from_the_home_page(): void
    {
        $this->saveSettings();

        $before = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('two.mp4', $before);

        $this->saveSettings(['about_us_video_remove_2' => 1]);

        $after = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('two.mp4', $after, 'The removed card is still on the page.');
        $this->assertStringContainsString('one.mp4', $after, 'Removing one slot took another with it.');
        $this->assertStringContainsString('three.mp4', $after);
    }

    /**
     * The bundled clips are what makes the section work before anyone has
     * configured it, so they must survive - the fallback is only wrong once
     * the admin has said "empty" out loud.
     */
    public function test_a_slot_nobody_has_configured_still_falls_back_to_the_bundled_clip(): void
    {
        $this->assertNull(Setting::where('key', 'about_us_video_url')->first());

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('karmaa-about', $html);
    }

    public function test_clearing_every_slot_hides_the_row_of_cards_entirely(): void
    {
        $this->saveSettings();
        $this->saveSettings([
            'about_us_video_remove' => 1,
            'about_us_video_remove_2' => 1,
            'about_us_video_remove_3' => 1,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // The element, not the class name: kk-about-reels also appears in the
        // stylesheet the page inlines, so a bare string match always passes.
        $this->assertStringNotContainsString(
            '<div class="kk-about-reels">',
            $html,
            'An empty grid is left where the cards were.'
        );
        $this->assertStringContainsString(
            'kk-about-cta',
            $html,
            'The whole About section vanished, not just the cards.'
        );
    }

    /**
     * Ticking remove and choosing a replacement in the same save is a
     * replacement - the upload is the clearer intent of the two.
     */
    public function test_a_new_upload_wins_over_a_remove_ticked_in_the_same_save(): void
    {
        $this->saveSettings();

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.homepage.site-settings.update'), $this->settingsPayload([
                'about_us_video_remove_2' => 1,
                'about_us_video_file_2' => \Illuminate\Http\UploadedFile::fake()->create('replacement.mp4', 64, 'video/mp4'),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNotSame('', (string) Setting::where('key', 'about_us_video_url_2')->first()->value);
    }
}
