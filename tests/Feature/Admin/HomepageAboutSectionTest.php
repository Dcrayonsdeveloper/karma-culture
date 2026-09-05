<?php

namespace Tests\Feature\Admin;

use App\Models\AboutReel;
use App\Models\HomepageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The About Us block on the home page is driven by a homepage_sections row for
 * its wording and by a list of reels for its clips. Both halves were once
 * unreachable: nothing ever created the row, and the admin exposed only the
 * first of the three video slots the clips used to live in.
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

    /**
     * The videos were three settings keys and are rows now, so what this file
     * used to pin - a Site Settings field per slot, and a controller that
     * persisted all three - has moved to Homepage > About Reels. The rule
     * behind it has not: every clip the page renders has to be editable from
     * the admin, which is what left two of the three cards unreachable before.
     *
     * The reels' own add / delete / hide / reorder behaviour is covered in
     * Tests\Feature\Homepage\AboutReelsTest.
     */
    public function test_every_reel_the_page_renders_is_editable_from_the_admin(): void
    {
        AboutReel::query()->delete();

        foreach (['storage/storefront/about/one.mp4', 'https://cdn.example.com/two.mp4'] as $i => $path) {
            AboutReel::create(['video_path' => $path, 'position' => $i + 1, 'is_active' => true]);
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $home = $this->get('/')->assertOk()->getContent();
        $screen = $this->actingAs($admin, 'admin')->get(route('admin.homepage.about-reels'))->assertOk();

        foreach (AboutReel::ordered()->get() as $reel) {
            $this->assertStringContainsString($reel->url, $home, 'The page renders this reel...');
            // ...so the admin screen has to show it, with the controls to
            // replace or delete it.
            $screen->assertSee($reel->url, false)
                ->assertSee(route('admin.homepage.about-reels.destroy', $reel), false);
        }
    }
}
