<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Marketing > Banners, the CRUD behind every placement.
 *
 * The screen shipped with no tests at all, and it showed: it filed phone
 * artwork into the desktop directory, kept every file it replaced, wrote the
 * priority column across placements it does not manage, and had a video field
 * on neither form even though the table has carried two video columns since
 * August. Each of those has a test here so it cannot come back.
 */
class BannerCrudTest extends TestCase
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

    private function admin(): self
    {
        return $this->actingAs($this->adminUser, 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Autumn campaign',
            'position' => 'sidebar',
            'image' => UploadedFile::fake()->image('wide.jpg', 1426, 370),
        ], $overrides);
    }

    private function banner(array $overrides = []): Banner
    {
        return Banner::create(array_merge([
            'name' => 'Existing',
            'position' => 'sidebar',
            'image_url' => 'banners/desktop.jpg',
            'priority' => 0,
            'is_active' => true,
        ], $overrides));
    }

    // Media

    public function test_a_video_only_banner_is_accepted(): void
    {
        Storage::fake('public');

        $this->admin()
            ->post(route('admin.banners.store'), $this->payload([
                'image' => null,
                'video' => UploadedFile::fake()->create('clip.mp4', 200, 'video/mp4'),
            ]))
            ->assertSessionHasNoErrors();

        $banner = Banner::latest('id')->first();

        $this->assertNull($banner->image_url);
        $this->assertStringStartsWith('banners/video/', $banner->video_url);
        Storage::disk('public')->assertExists($banner->video_url);
    }

    public function test_a_banner_with_no_artwork_at_all_is_refused_by_name(): void
    {
        $response = $this->admin()->post(route('admin.banners.store'), $this->payload(['image' => null]));

        $response->assertSessionHasErrors('image');
        $this->assertStringContainsString(
            'desktop video',
            session('errors')->first('image'),
            'The error has to name the video as the alternative; "the image field is required" is untrue when a video would have done.'
        );
        $this->assertDatabaseCount('banners', 0);
    }

    public function test_each_upload_is_filed_in_its_own_directory(): void
    {
        Storage::fake('public');

        $this->admin()
            ->post(route('admin.banners.store'), $this->payload([
                'video' => UploadedFile::fake()->create('clip.mp4', 200, 'video/mp4'),
                'mobile_image' => UploadedFile::fake()->image('tall.jpg', 1080, 720),
                'mobile_video' => UploadedFile::fake()->create('phone.mp4', 200, 'video/mp4'),
            ]))
            ->assertSessionHasNoErrors();

        $banner = Banner::latest('id')->first();

        // The bug this replaces: phone artwork went to `banners/`, where it was
        // indistinguishable from the desktop file beside it.
        $this->assertStringStartsWith('banners/', $banner->image_url);
        $this->assertStringStartsWith('banners/video/', $banner->video_url);
        $this->assertStringStartsWith('banners/mobile/', $banner->mobile_image_url);
        $this->assertStringStartsWith('banners/mobile/video/', $banner->mobile_video_url);
    }

    public function test_replacing_an_image_deletes_the_file_it_replaces(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/desktop.jpg', 'x');

        $banner = $this->banner();

        $this->admin()
            ->put(route('admin.banners.update', $banner), [
                'name' => 'Existing',
                'position' => 'sidebar',
                'image' => UploadedFile::fake()->image('new.jpg', 1426, 370),
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('banners/desktop.jpg');
        $this->assertNotSame('banners/desktop.jpg', $banner->fresh()->image_url);
    }

    public function test_the_remove_mobile_image_checkbox_drops_the_override(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/mobile/phone.jpg', 'x');

        $banner = $this->banner(['mobile_image_url' => 'banners/mobile/phone.jpg']);

        $this->admin()
            ->put(route('admin.banners.update', $banner), [
                'name' => 'Existing',
                'position' => 'sidebar',
                'remove_mobile_image' => '1',
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('banners/mobile/phone.jpg');
        $this->assertNull($banner->fresh()->mobile_image_url);
    }

    public function test_removing_the_video_from_a_video_only_banner_is_refused(): void
    {
        Storage::fake('public');

        $banner = $this->banner(['image_url' => null, 'video_url' => 'banners/video/clip.mp4']);
        Storage::disk('public')->put('banners/video/clip.mp4', 'x');

        $this->admin()
            ->put(route('admin.banners.update', $banner), [
                'name' => 'Existing',
                'position' => 'sidebar',
                'remove_video' => '1',
            ])
            ->assertSessionHasErrors('remove_video');

        Storage::disk('public')->assertExists('banners/video/clip.mp4');
        $this->assertSame('banners/video/clip.mp4', $banner->fresh()->video_url);
    }

    // Links and schedule

    public function test_a_site_relative_path_is_a_valid_link(): void
    {
        Storage::fake('public');

        // V::url() demanded a full https:// address, so /products - the shape
        // most banners actually use, and the one the hero screen accepts - was
        // refused here for the same table.
        $this->admin()
            ->post(route('admin.banners.store'), $this->payload(['link' => '/products']))
            ->assertSessionHasNoErrors();

        $this->assertSame('/products', Banner::latest('id')->first()->link);
    }

    public function test_a_protocol_relative_link_is_refused(): void
    {
        // `//evil.com` reads as a local path and resolves off-site.
        $this->admin()
            ->post(route('admin.banners.store'), $this->payload(['link' => '//evil.com']))
            ->assertSessionHasErrors('link');
    }

    public function test_a_javascript_link_is_refused(): void
    {
        $this->admin()
            ->post(route('admin.banners.store'), $this->payload(['link' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('link');
    }

    public function test_an_end_before_the_start_is_refused(): void
    {
        $this->admin()
            ->post(route('admin.banners.store'), $this->payload([
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_a_future_window_is_stored_and_reads_as_scheduled(): void
    {
        Storage::fake('public');

        $this->admin()
            ->post(route('admin.banners.store'), $this->payload([
                'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('scheduled', Banner::latest('id')->first()->state);
    }

    public function test_a_blank_alt_text_falls_back_to_the_heading(): void
    {
        Storage::fake('public');

        // Stored as '' the model reads it as a deliberate empty alt and the
        // storefront hides the artwork from screen readers.
        $this->admin()
            ->post(route('admin.banners.store'), $this->payload(['title' => 'Autumn is here', 'alt_text' => '']))
            ->assertSessionHasNoErrors();

        $banner = Banner::latest('id')->first();

        $this->assertNull($banner->alt_text);
        $this->assertSame('Autumn is here', $banner->alt);
    }

    // Delete and restore

    public function test_deleting_removes_the_files_but_keeps_the_row(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/desktop.jpg', 'x');
        Storage::disk('public')->put('banners/mobile/phone.jpg', 'x');

        $banner = $this->banner(['mobile_image_url' => 'banners/mobile/phone.jpg']);

        $this->admin()->delete(route('admin.banners.destroy', $banner));

        Storage::disk('public')->assertMissing('banners/desktop.jpg');
        Storage::disk('public')->assertMissing('banners/mobile/phone.jpg');
        $this->assertSoftDeleted('banners', ['id' => $banner->id]);
    }

    public function test_a_deleted_banner_is_listed_and_restored(): void
    {
        $banner = $this->banner();
        $banner->delete();

        $this->admin()->get(route('admin.banners.index'))->assertDontSee('Existing');
        $this->admin()->get(route('admin.banners.index', ['trashed' => 1]))->assertSee('Existing');

        $this->admin()
            ->put(route('admin.banners.restore', $banner))
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted('banners', ['id' => $banner->id]);
    }

    // Ordering

    public function test_reorder_leaves_the_placements_it_was_not_given_alone(): void
    {
        $hero = Banner::create(['name' => 'Hero', 'position' => 'hero', 'image_url' => 'banners/h.jpg', 'priority' => 7, 'is_active' => true]);
        $first = $this->banner(['name' => 'First', 'priority' => 0]);
        $second = $this->banner(['name' => 'Second', 'priority' => 1]);

        $this->admin()
            ->postJson(route('admin.banners.reorder'), [
                'position' => 'sidebar',
                'order' => [$second->id, $first->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, $second->fresh()->priority);
        $this->assertSame(1, $first->fresh()->priority);
        // The hero rows belong to Homepage > Hero Banners, which sorts by the
        // same column. This screen must not touch them.
        $this->assertSame(7, $hero->fresh()->priority);
    }

    public function test_reorder_refuses_a_partial_placement(): void
    {
        $first = $this->banner(['name' => 'First', 'priority' => 0]);
        $this->banner(['name' => 'Second', 'priority' => 1]);

        $this->admin()
            ->postJson(route('admin.banners.reorder'), [
                'position' => 'sidebar',
                'order' => [$first->id],
            ])
            ->assertStatus(422);

        $this->assertSame(0, $first->fresh()->priority);
    }

    public function test_reorder_refuses_an_id_from_another_placement(): void
    {
        $hero = Banner::create(['name' => 'Hero', 'position' => 'hero', 'image_url' => 'banners/h.jpg', 'priority' => 7, 'is_active' => true]);

        $this->admin()
            ->postJson(route('admin.banners.reorder'), ['position' => 'sidebar', 'order' => [$hero->id]])
            ->assertStatus(422);
    }

    public function test_a_new_banner_goes_to_the_end_of_its_own_placement(): void
    {
        Storage::fake('public');

        $this->banner(['priority' => 4]);

        $this->admin()->post(route('admin.banners.store'), $this->payload());

        $this->assertSame(5, Banner::latest('id')->first()->priority);
    }

    // Screens

    public function test_the_list_shows_the_effective_state_not_the_raw_switch(): void
    {
        $this->banner(['name' => 'Finished run', 'starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()]);

        // Switched on and invisible: "Active" was true here and told an admin
        // nothing about why the storefront was empty.
        $this->admin()
            ->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Expired')
            ->assertSee('Ended '.now()->subDay()->format('j M Y'), false);
    }

    public function test_the_forms_drive_their_size_advice_off_the_constants(): void
    {
        $banner = $this->banner();

        foreach ([route('admin.banners.create'), route('admin.banners.edit', $banner)] as $url) {
            $this->admin()->get($url)
                ->assertOk()
                ->assertSee(Banner::HERO_DESKTOP_SIZE[0].' &times; '.Banner::HERO_DESKTOP_SIZE[1].'px', false)
                ->assertSee(Banner::HERO_MOBILE_SIZE[0].' &times; '.Banner::HERO_MOBILE_SIZE[1].'px', false)
                ->assertSee('name="video"', false)
                ->assertSee('name="mobile_video"', false)
                ->assertSee('name="alt_text"', false)
                ->assertSee('name="starts_at"', false);
        }
    }

    public function test_the_toggle_switches_a_banner_without_touching_its_schedule(): void
    {
        $banner = $this->banner(['starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);

        $this->admin()->put(route('admin.banners.toggle', $banner))->assertSessionHasNoErrors();

        $banner->refresh();

        $this->assertFalse($banner->is_active);
        $this->assertNotNull($banner->starts_at);
        $this->assertNotNull($banner->ends_at);
    }

    // JSON contract - these admin routes are the only authenticated admin API

    public function test_the_list_answers_json(): void
    {
        $this->banner(['name' => 'Listed']);

        $this->admin()
            ->getJson(route('admin.banners.index'))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Listed')
            ->assertJsonPath('data.0.state', 'live')
            ->assertJsonStructure(['data' => [['id', 'position', 'state', 'media', 'frames']], 'meta' => ['total']]);
    }

    public function test_a_json_create_answers_with_the_created_banner(): void
    {
        Storage::fake('public');

        $this->admin()
            ->postJson(route('admin.banners.store'), $this->payload(['name' => 'Made over JSON']))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Made over JSON');
    }

    public function test_a_json_create_reports_validation_the_same_way(): void
    {
        $this->admin()
            ->postJson(route('admin.banners.store'), $this->payload(['image' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_a_json_update_leaves_out_what_it_does_not_send(): void
    {
        $banner = $this->banner(['title' => 'Kept', 'starts_at' => now()->addDay()]);

        // A client that sends only the fields it is changing must not have the
        // rest reset: is_active would switch the banner off and starts_at would
        // erase the campaign's start date.
        $this->admin()
            ->putJson(route('admin.banners.update', $banner), ['name' => 'Renamed', 'position' => 'sidebar'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        $banner->refresh();

        $this->assertSame('Kept', $banner->title);
        $this->assertTrue($banner->is_active);
        $this->assertNotNull($banner->starts_at);
    }

    public function test_a_json_delete_answers_json(): void
    {
        Storage::fake('public');

        $banner = $this->banner();

        $this->admin()
            ->deleteJson(route('admin.banners.destroy', $banner))
            ->assertOk()
            ->assertJsonPath('data.id', $banner->id);

        $this->assertSoftDeleted('banners', ['id' => $banner->id]);
    }
}
