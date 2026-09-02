<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin > Homepage > Hero Banners, and its split Desktop/Mobile upload panels.
 *
 * The screen used to take one image and one clip and recommend "1920x700px"
 * for them, which was the size of nothing: the hero plays at 1426x370 on
 * desktop and is given a 4:5 portrait box on phones. Each device now has its
 * own section, headed by the size that device actually draws at, and its own
 * pair of files.
 */
class HeroBannerMobileUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_the_screen_recommends_the_size_each_device_draws_at(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')->get(route('admin.homepage.hero-banners'));

        $response->assertOk();
        $response->assertSee(Banner::HERO_DESKTOP_SIZE[0].' &times; '.Banner::HERO_DESKTOP_SIZE[1].'px', false);
        $response->assertSee(Banner::HERO_MOBILE_SIZE[0].' &times; '.Banner::HERO_MOBILE_SIZE[1].'px', false);
        $response->assertSee('Desktop - website', false);
        $response->assertSee('Mobile - phones', false);
        $response->assertSee('name="mobile_image"', false);
        $response->assertSee('name="mobile_video"', false);
    }

    public function test_a_new_banner_stores_the_files_for_both_devices(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.hero-banners.store'), [
                'name' => 'Campaign',
                'image' => UploadedFile::fake()->image('wide.jpg', 1426, 370),
                'mobile_image' => UploadedFile::fake()->image('tall.jpg', 1080, 1350),
            ])
            ->assertSessionHasNoErrors();

        $banner = Banner::where('position', 'hero')->latest('id')->first();

        $this->assertStringStartsWith('banners/', $banner->image_url);
        $this->assertStringStartsWith('banners/mobile/', $banner->mobile_image_url);
        Storage::disk('public')->assertExists($banner->image_url);
        Storage::disk('public')->assertExists($banner->mobile_image_url);
    }

    public function test_a_mobile_file_alone_does_not_satisfy_the_media_requirement(): void
    {
        Storage::fake('public');

        // The mobile pair is an override, so it must not stand in for the
        // desktop slot - a banner with only phone artwork would render as a
        // placeholder box on every other screen.
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.hero-banners.store'), [
                'name' => 'Campaign',
                'mobile_image' => UploadedFile::fake()->image('tall.jpg', 1080, 1350),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_replacing_a_mobile_image_deletes_the_one_it_replaces(): void
    {
        Storage::fake('public');

        $banner = Banner::create([
            'name' => 'Campaign',
            'position' => 'hero',
            'image_url' => 'banners/desktop.jpg',
            'mobile_image_url' => 'banners/mobile/old.jpg',
            'priority' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put('banners/mobile/old.jpg', 'x');

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.homepage.hero-banners.update', $banner), [
                'mobile_image' => UploadedFile::fake()->image('new.jpg', 1080, 1350),
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('banners/mobile/old.jpg');
        $this->assertNotSame('banners/mobile/old.jpg', $banner->fresh()->mobile_image_url);
    }

    public function test_removing_the_mobile_override_needs_no_desktop_guard(): void
    {
        Storage::fake('public');

        $banner = Banner::create([
            'name' => 'Campaign',
            'position' => 'hero',
            'image_url' => 'banners/desktop.jpg',
            'mobile_video_url' => 'banners/mobile/video/reel.mp4',
            'priority' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put('banners/mobile/video/reel.mp4', 'x');

        // Unlike the desktop video, dropping this one cannot leave a banner
        // with nothing to show: it falls back to the desktop media on phones.
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.homepage.hero-banners.update', $banner), ['remove_mobile_video' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertNull($banner->fresh()->mobile_video_url);
        Storage::disk('public')->assertMissing('banners/mobile/video/reel.mp4');
    }

    public function test_deleting_a_banner_takes_all_four_files_with_it(): void
    {
        Storage::fake('public');

        $paths = [
            'banners/desktop.jpg',
            'banners/video/wide.mp4',
            'banners/mobile/tall.jpg',
            'banners/mobile/video/reel.mp4',
        ];

        $banner = Banner::create([
            'name' => 'Campaign',
            'position' => 'hero',
            'image_url' => $paths[0],
            'video_url' => $paths[1],
            'mobile_image_url' => $paths[2],
            'mobile_video_url' => $paths[3],
            'priority' => 1,
            'is_active' => true,
        ]);

        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'x');
        }

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.homepage.hero-banners.destroy', $banner));

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_a_web_root_path_is_not_handed_to_the_public_disk_to_delete(): void
    {
        Storage::fake('public');

        // The imported hero banner records a path under public/, not a key on
        // the public disk. Deleting the banner used to hand that path over
        // anyway - here it hits nothing, but the two namespaces are not the
        // admin's to keep apart.
        $banner = Banner::create([
            'name' => 'Imported',
            'position' => 'hero',
            'video_url' => '/images/karmaa-kulture-web-banner-v3.mp4',
            'priority' => 1,
            'is_active' => true,
        ]);
        Storage::disk('public')->put('/images/karmaa-kulture-web-banner-v3.mp4', 'x');

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.homepage.hero-banners.destroy', $banner));

        Storage::disk('public')->assertExists('/images/karmaa-kulture-web-banner-v3.mp4');
    }
}
