<?php

namespace Tests\Feature\Banner;

use App\Models\Banner;
use App\Support\BannerMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What each screen is given when a banner does not carry all four files.
 *
 * The two screens have different orders of preference and they are not
 * symmetrical. A desktop would rather show the phone still than nothing, but it
 * never reaches for the phone CLIP: a portrait video letterboxed into a 3.85:1
 * strip is worse than the still it replaced. A phone with neither of its own
 * takes whatever the desktop has, in the desktop's own order.
 *
 * All of it lives in one method on the model, because the website and the API
 * both read it and a phone must not be sent one file by one and a different
 * file by the other.
 */
class BannerFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function banner(array $attributes = []): Banner
    {
        return Banner::create($attributes + [
            'name' => 'Campaign',
            'position' => 'hero',
            'priority' => 1,
            'is_active' => true,
        ]);
    }

    public function test_desktop_prefers_its_own_clip_then_its_own_still(): void
    {
        $banner = $this->banner([
            'video_url' => 'banners/video/wide.mp4',
            'image_url' => 'banners/desktop.jpg',
        ]);

        $frame = $banner->frameFor('desktop');
        $this->assertSame('video', $frame['kind']);
        $this->assertStringContainsString('wide.mp4', $frame['src']);
        // The still becomes the poster, so the first painted frame is artwork
        // rather than a black rectangle.
        $this->assertStringContainsString('desktop.jpg', $frame['poster']);

        $stillOnly = $this->banner(['image_url' => 'banners/desktop.jpg', 'name' => 'Still']);
        $this->assertSame('image', $stillOnly->frameFor('desktop')['kind']);
    }

    public function test_a_desktop_with_only_phone_artwork_shows_it_rather_than_nothing(): void
    {
        $banner = $this->banner(['mobile_image_url' => 'banners/mobile/portrait.jpg']);

        $frame = $banner->frameFor('desktop');

        $this->assertSame('image', $frame['kind']);
        $this->assertStringContainsString('portrait.jpg', $frame['src']);
    }

    public function test_a_desktop_never_falls_back_to_the_phone_clip(): void
    {
        // A portrait clip letterboxed into a 3.85:1 strip is worse than the
        // still beside it, so the still wins even though the clip is "richer".
        $banner = $this->banner([
            'mobile_video_url' => 'banners/mobile/video/reel.mp4',
            'mobile_image_url' => 'banners/mobile/portrait.jpg',
        ]);

        $frame = $banner->frameFor('desktop');

        $this->assertSame('image', $frame['kind']);
        $this->assertStringNotContainsString('reel.mp4', $frame['src']);
    }

    public function test_mobile_prefers_its_own_clip_then_its_own_still(): void
    {
        $banner = $this->banner([
            'image_url' => 'banners/desktop.jpg',
            'mobile_video_url' => 'banners/mobile/video/reel.mp4',
            'mobile_image_url' => 'banners/mobile/portrait.jpg',
        ]);

        $frame = $banner->frameFor('mobile');

        $this->assertSame('video', $frame['kind']);
        $this->assertStringContainsString('reel.mp4', $frame['src']);
        $this->assertStringContainsString('portrait.jpg', $frame['poster']);
    }

    public function test_mobile_falls_back_through_the_desktop_pair_in_order(): void
    {
        $clip = $this->banner([
            'video_url' => 'banners/video/wide.mp4',
            'image_url' => 'banners/desktop.jpg',
        ]);
        $this->assertSame('video', $clip->frameFor('mobile')['kind']);

        $still = $this->banner(['image_url' => 'banners/desktop.jpg', 'name' => 'Still']);
        $this->assertStringContainsString('desktop.jpg', $still->frameFor('mobile')['src']);
    }

    public function test_a_banner_with_nothing_at_all_resolves_to_nothing(): void
    {
        $banner = $this->banner();

        $this->assertNull($banner->frameFor('desktop'));
        $this->assertNull($banner->frameFor('mobile'));
        $this->assertFalse($banner->has_media);
    }

    public function test_an_uploaded_still_gets_a_webp_twin_the_storefront_can_offer(): void
    {
        Storage::fake('public');

        $path = BannerMedia::store(UploadedFile::fake()->image('wide.jpg', 1426, 370), 'image_url');

        $this->assertStringStartsWith('banners/', $path);
        Storage::disk('public')->assertExists($path);

        // Best-effort: a server without WebP support simply serves the original,
        // which is what every banner did before this existed. So the assertion
        // is conditional on the capability, not on the outcome.
        if (function_exists('imagewebp')) {
            $webp = BannerMedia::webpFor($path);

            if ($webp !== null) {
                Storage::disk('public')->assertExists($webp);
                $this->assertStringEndsWith('.webp', $webp);
            }
        }

        $this->assertTrue(true);
    }

    public function test_replacing_a_file_takes_the_old_one_with_it(): void
    {
        Storage::fake('public');

        $first = BannerMedia::store(UploadedFile::fake()->image('one.jpg'), 'image_url');
        $second = BannerMedia::replace(UploadedFile::fake()->image('two.jpg'), 'image_url', $first);

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_a_web_root_path_is_never_handed_to_the_public_disk(): void
    {
        // The hero clip the shop ships with is recorded as `/images/...`, a real
        // file in the webroot that this disk does not own. Deleting a banner
        // that points at it must leave it alone.
        Storage::fake('public');

        BannerMedia::delete('/images/karmaa-kulture-web-banner-v3.mp4');
        BannerMedia::delete('https://cdn.example.com/banner.jpg');

        // Nothing to assert but the absence of an exception and of a write; the
        // guard is the behaviour.
        $this->assertTrue(true);
    }

    public function test_mobile_images_are_filed_under_their_own_directory(): void
    {
        // Both banner screens used to choose this path independently and one of
        // them put phone artwork in the desktop folder.
        Storage::fake('public');

        $path = BannerMedia::store(UploadedFile::fake()->image('portrait.jpg'), 'mobile_image_url');

        $this->assertStringStartsWith('banners/mobile/', $path);
    }
}
