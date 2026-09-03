<?php

namespace Tests\Feature\Homepage;

use App\Models\AboutReel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The About Us strip is a list, not three fixed slots.
 *
 * It used to be three settings keys - about_us_video_url, _2 and _3 - so the
 * section could hold exactly three clips: a store with one left two slots'
 * worth of nothing, and a fourth clip had nowhere to go. The only edits
 * possible were "replace this slot's file" and "clear this slot".
 *
 * Reels are rows now, so one can be added, deleted, hidden or moved, and the
 * home page renders however many are active.
 */
class AboutReelsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function reel(string $path, int $position, bool $active = true): AboutReel
    {
        return AboutReel::create([
            'video_path' => $path,
            'position' => $position,
            'is_active' => $active,
        ]);
    }

    /**
     * The migration seeds the strip from whatever the three keys held, so a
     * fresh install opens on the bundled clips exactly as it did before.
     */
    public function test_the_strip_starts_from_the_slots_it_replaced(): void
    {
        $this->assertSame(
            ['videos/karmaa-about.mp4', 'videos/karmaa-about-2.mp4', 'videos/karmaa-about-3.mp4'],
            AboutReel::ordered()->pluck('video_path')->all(),
            'A store that never touched the old slots was seeing these three; it still should be.'
        );
    }

    public function test_the_home_page_renders_the_active_reels_in_order(): void
    {
        AboutReel::query()->delete();
        $this->reel('storage/storefront/about/one.mp4', 1);
        $this->reel('storage/storefront/about/two.mp4', 2);
        $this->reel('storage/storefront/about/hidden.mp4', 3, false);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('storage/storefront/about/one.mp4', $html);
        $this->assertStringContainsString('storage/storefront/about/two.mp4', $html);
        $this->assertStringNotContainsString('hidden.mp4', $html, 'A hidden reel is off the home page, not merely last.');
    }

    /** The count is no longer three. Four reels means four cards. */
    public function test_the_strip_is_not_capped_at_three(): void
    {
        AboutReel::query()->delete();
        foreach (range(1, 5) as $i) {
            $this->reel("storage/storefront/about/reel{$i}.mp4", $i);
        }

        $html = $this->get('/')->assertOk()->getContent();

        foreach (range(1, 5) as $i) {
            $this->assertStringContainsString("reel{$i}.mp4", $html);
        }
    }

    /**
     * And the strip has to survive the count changing. Three fixed columns left
     * a single reel stretched across a third of the section, hugging the left
     * edge with two empty columns beside it - and a wrapping row dropped the
     * fourth clip onto a second line, which read as a block of tiles rather
     * than a strip of reels.
     */
    public function test_the_strip_lays_itself_out_around_the_count(): void
    {
        $home = file_get_contents(resource_path('views/home.blade.php'));

        $this->assertDoesNotMatchRegularExpression(
            '/\.kk-about-reels \{[^}]*grid-template-columns: repeat\(3,/',
            $home,
            'A hardcoded three columns cannot hold one reel or four.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.kk-about-reels[^{]*\{[^}]*flex-wrap: wrap/',
            $home,
            'The strip is one line; a fourth reel must not drop onto a second row.'
        );
        // The track is max-content wide, so its auto margins are what centre a
        // short strip - and they give way by themselves once it overflows.
        $this->assertMatchesRegularExpression(
            '/\.kk-about-reels__track \{[^}]*margin: 0 auto/',
            $home,
            'One or two reels have to sit in the middle of the section, not against its left edge.'
        );
    }

    /** With every reel gone the section keeps its heading and drops the strip. */
    public function test_an_empty_strip_leaves_no_empty_grid_behind(): void
    {
        AboutReel::query()->delete();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('kk-about-cta', $html, 'The section itself stays.');
        // The markup, not the class name: the stylesheet in the page head
        // mentions .kk-about-reels whether or not the strip renders.
        $this->assertStringNotContainsString('<div class="kk-about-reels">', $html);
    }

    public function test_an_admin_can_add_a_reel(): void
    {
        Storage::fake('public');
        AboutReel::query()->delete();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.about-reels.store'), [
                'video' => UploadedFile::fake()->create('reel.mp4', 200, 'video/mp4'),
            ])
            ->assertRedirect();

        $reel = AboutReel::sole();
        $this->assertStringStartsWith('storage/storefront/about/', $reel->video_path);
        $this->assertTrue($reel->is_active);
        Storage::disk('public')->assertExists($reel->storagePath());
    }

    public function test_a_reel_without_a_file_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.about-reels.store'), [])
            ->assertSessionHasErrors('video');
    }

    public function test_an_admin_can_delete_a_reel_and_its_file(): void
    {
        Storage::fake('public');
        AboutReel::query()->delete();

        $this->actingAs($this->admin(), 'admin')->post(route('admin.homepage.about-reels.store'), [
            'video' => UploadedFile::fake()->create('reel.mp4', 200, 'video/mp4'),
        ]);

        $reel = AboutReel::sole();
        $stored = $reel->storagePath();

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.homepage.about-reels.destroy', $reel))
            ->assertRedirect();

        $this->assertDatabaseCount('about_reels', 0);
        Storage::disk('public')->assertMissing($stored);
    }

    /**
     * The point of deleting one: the card goes. Under the three-slot design the
     * home page fell back to a bundled clip whenever a slot read blank, so a
     * removed video came straight back and the card could never be taken down.
     */
    public function test_a_deleted_reel_is_gone_from_the_home_page(): void
    {
        AboutReel::query()->delete();
        $keep = $this->reel('storage/storefront/about/keep.mp4', 1);
        $drop = $this->reel('storage/storefront/about/drop.mp4', 2);

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.homepage.about-reels.destroy', $drop))
            ->assertRedirect();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('keep.mp4', $html);
        $this->assertStringNotContainsString('drop.mp4', $html);
        $this->assertStringNotContainsString('karmaa-about', $html, 'No bundled clip may take the deleted one\'s place.');
        $this->assertTrue($keep->exists);
    }

    /**
     * A bundled clip is shipped with the repo and pointed at by the migration,
     * so deleting the reel must not delete the file out of the build.
     */
    public function test_deleting_a_reel_does_not_touch_a_file_it_does_not_own(): void
    {
        Storage::fake('public');
        AboutReel::query()->delete();
        $reel = $this->reel('videos/karmaa-about.mp4', 1);

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.homepage.about-reels.destroy', $reel))
            ->assertRedirect();

        $this->assertDatabaseCount('about_reels', 0);
        $this->assertFalse($reel->ownsFile());
    }

    public function test_an_admin_can_hide_a_reel_without_losing_it(): void
    {
        AboutReel::query()->delete();
        $reel = $this->reel('storage/storefront/about/one.mp4', 1);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.homepage.about-reels.toggle', $reel))
            ->assertRedirect();

        $this->assertFalse($reel->fresh()->is_active);
        $this->assertDatabaseCount('about_reels', 1);
    }

    public function test_an_admin_can_reorder_the_strip(): void
    {
        AboutReel::query()->delete();
        $first = $this->reel('storage/storefront/about/one.mp4', 1);
        $second = $this->reel('storage/storefront/about/two.mp4', 2);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.homepage.about-reels.move', $second), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(
            [$second->id, $first->id],
            AboutReel::ordered()->pluck('id')->all()
        );
    }

    /** Replacing a clip keeps the reel's place and takes the old file with it. */
    public function test_replacing_a_clip_keeps_the_position_and_deletes_the_old_file(): void
    {
        Storage::fake('public');
        AboutReel::query()->delete();

        $this->actingAs($this->admin(), 'admin')->post(route('admin.homepage.about-reels.store'), [
            'video' => UploadedFile::fake()->create('first.mp4', 200, 'video/mp4'),
        ]);
        $this->actingAs($this->admin(), 'admin')->post(route('admin.homepage.about-reels.store'), [
            'video' => UploadedFile::fake()->create('second.mp4', 200, 'video/mp4'),
        ]);

        $reel = AboutReel::ordered()->first();
        $old = $reel->storagePath();

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.homepage.about-reels.update', $reel), [
                'video' => UploadedFile::fake()->create('replacement.mp4', 200, 'video/mp4'),
            ])
            ->assertRedirect();

        $reel->refresh();
        $this->assertSame(1, $reel->position, 'A replacement is not a new reel at the end of the strip.');
        $this->assertNotSame($old, $reel->storagePath());
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($reel->storagePath());
    }

    /** The admin screen renders, and links from the homepage manager. */
    public function test_the_admin_screen_and_its_link_are_reachable(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.homepage.about-reels'))
            ->assertOk()
            ->assertSee('Add Reel', false);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.homepage.index'))
            ->assertOk()
            ->assertSee(route('admin.homepage.about-reels'), false);
    }

    /**
     * Site Settings held the three slots. Leaving them there as well would mean
     * two screens editing one strip, with only one of them being read.
     */
    public function test_site_settings_no_longer_carries_the_old_slots(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.homepage.site-settings'))
            ->assertOk()
            ->assertDontSee('name="about_us_video_url"', false)
            ->assertDontSee('name="about_us_video_file"', false)
            ->assertSee(route('admin.homepage.about-reels'), false);
    }
}
