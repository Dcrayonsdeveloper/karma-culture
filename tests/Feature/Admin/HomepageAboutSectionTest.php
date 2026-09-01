<?php

namespace Tests\Feature\Admin;

use App\Models\HomepageSection;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The About Us block on the home page is driven by a homepage_sections row for
 * its wording and by three settings for its videos. Both halves were
 * unreachable: nothing ever created the row, and the admin exposed only the
 * first of the three video fields.
 */
class HomepageAboutSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_about_us_section_exists_so_the_admin_has_something_to_edit(): void
    {
        $section = HomepageSection::where('key', 'about_us')->first();

        $this->assertNotNull($section, 'The Sections screen can only edit rows that exist.');
        $this->assertTrue((bool) $section->is_active);
        $this->assertNotEmpty($section->title);
        $this->assertNotEmpty($section->subtitle);
    }

    public function test_the_wording_the_admin_edits_is_the_wording_the_page_reads(): void
    {
        $section = HomepageSection::where('key', 'about_us')->firstOrFail();
        $section->update([
            'title' => 'Made by Hand',
            'subtitle' => 'Every seam checked twice.',
        ]);

        // How home.blade.php resolves the two values.
        $sections = HomepageSection::where('is_active', true)->get()->keyBy('key');

        $this->assertSame('Made by Hand', $sections['about_us']->title);
        $this->assertSame(
            'Every seam checked twice.',
            $sections['about_us']->subtitle,
            'The page reads subtitle; content is an array of repeater items and never held this sentence.'
        );
    }

    public function test_content_stays_an_array_so_reading_it_as_a_sentence_would_be_wrong(): void
    {
        $section = HomepageSection::where('key', 'about_us')->firstOrFail();
        $section->update(['content' => [['title' => 'Fabric', 'description' => 'Long staple cotton']]]);

        $this->assertIsArray($section->fresh()->content);
    }

    public function test_all_three_about_video_settings_round_trip(): void
    {
        foreach ([
            'about_us_video_url' => 'storage/storefront/about/one.mp4',
            'about_us_video_url_2' => 'storage/storefront/about/two.mp4',
            'about_us_video_url_3' => 'https://cdn.example.com/three.mp4',
        ] as $key => $value) {
            Setting::set($key, $value, 'string', 'homepage');
        }

        $this->assertSame('storage/storefront/about/one.mp4', Setting::get('about_us_video_url'));
        $this->assertSame('storage/storefront/about/two.mp4', Setting::get('about_us_video_url_2'));
        $this->assertSame('https://cdn.example.com/three.mp4', Setting::get('about_us_video_url_3'));
    }

    public function test_the_admin_form_offers_a_field_for_every_video_the_page_renders(): void
    {
        $markup = file_get_contents(resource_path('views/admin/homepage/site-settings.blade.php'));

        foreach (['about_us_video_url', 'about_us_video_url_2', 'about_us_video_url_3'] as $field) {
            $this->assertStringContainsString(
                "'{$field}'",
                $markup,
                "The home page renders {$field}, so the admin must be able to set it."
            );
        }
    }

    public function test_the_controller_persists_every_video_field_it_offers(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/HomepageController.php'));

        foreach (['about_us_video_url_2', 'about_us_video_url_3'] as $field) {
            $this->assertStringContainsString($field, $source);
        }
        foreach (['about_us_video_file_2', 'about_us_video_file_3'] as $upload) {
            $this->assertStringContainsString($upload, $source);
        }
    }
}
