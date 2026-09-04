<?php

namespace Tests\Feature\Banner;

use App\Models\Banner;
use App\Models\User;
use App\Rules\ValidationRules;
use App\Support\BannerMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The size cap, from the mobile field's point of view.
 *
 * Reported as "the mobile image will not upload", and it looked device-specific
 * because it effectively was. Both fields were capped at 5 MB, but a desktop
 * banner is a 1426x370 strip that compresses to well under a megabyte, while
 * the phone artwork beside it is 1080x720 or taller and comes out of a design
 * tool several times heavier. The same rule therefore passed one field and
 * refused the other, and the refusal arrived only after the whole file had
 * uploaded, phrased in kilobytes, with the rest of the form reset around it.
 */
class BannerUploadLimitsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** A file of a given size in kilobytes, as an image the rules will accept. */
    private function image(int $kb, int $width = 1080, int $height = 720): UploadedFile
    {
        return UploadedFile::fake()->image('artwork.jpg', $width, $height)->size($kb);
    }

    public function test_a_mobile_banner_of_a_realistic_size_is_accepted(): void
    {
        // 8 MB - bigger than the old 5 MB cap and entirely ordinary for phone
        // artwork exported at full quality. This is the file that was being
        // refused.
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.hero-banners.store'), [
                'name' => 'Campaign',
                'image' => $this->image(400, 1426, 370),
                'mobile_image' => $this->image(8192),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $banner = Banner::where('name', 'Campaign')->firstOrFail();

        $this->assertNotNull($banner->mobile_image_url, 'The mobile image was dropped.');
        $this->assertStringStartsWith('banners/mobile/', $banner->mobile_image_url);
        Storage::disk('public')->assertExists($banner->mobile_image_url);
    }

    public function test_the_same_size_is_accepted_on_the_other_banner_screen(): void
    {
        // Two screens write this table, and a limit that differed between them
        // would be the same bug wearing a different hat.
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.banners.store'), [
                'name' => 'Marketing campaign',
                'position' => 'hero',
                'image' => $this->image(400, 1426, 370),
                'mobile_image' => $this->image(8192),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $banner = Banner::where('name', 'Marketing campaign')->firstOrFail();

        $this->assertStringStartsWith('banners/mobile/', (string) $banner->mobile_image_url);
    }

    public function test_a_mobile_image_can_be_added_to_a_banner_that_already_exists(): void
    {
        // The path an admin actually takes: the banner is already up, and the
        // phone artwork is added to it afterwards.
        Storage::fake('public');

        $banner = Banner::create([
            'name' => 'Existing',
            'position' => 'hero',
            'image_url' => 'banners/desktop.jpg',
            'priority' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.homepage.hero-banners.update', $banner), [
                'name' => 'Existing',
                'mobile_image' => $this->image(8192),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertStringStartsWith('banners/mobile/', (string) $banner->fresh()->mobile_image_url);
    }

    public function test_a_file_past_the_cap_is_refused_in_megabytes_not_kilobytes(): void
    {
        // Still refused - the cap moved, it did not go away. What changed is
        // that the sentence names a number the admin can compare against what
        // their file manager shows them.
        Storage::fake('public');

        $tooBig = (int) (BannerMedia::MAX_IMAGE_KB + 1024);

        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.hero-banners.store'), [
                'name' => 'Campaign',
                'image' => $this->image(400, 1426, 370),
                'mobile_image' => $this->image($tooBig),
            ]);

        $response->assertSessionHasErrors('mobile_image');

        $message = session('errors')->first('mobile_image');

        $this->assertStringContainsString('MB', $message);
        $this->assertStringNotContainsString('kilobytes', $message);
        $this->assertStringContainsString(
            ValidationRules::megabytes(BannerMedia::MAX_IMAGE_KB),
            $message
        );
    }

    public function test_the_screens_promise_the_limit_the_server_enforces(): void
    {
        // The number under the field used to be typed in by hand, so raising
        // the cap in one place would have left the screen advertising the old
        // one. Both now read the same constant.
        $mb = ValidationRules::megabytes(BannerMedia::MAX_IMAGE_KB);

        foreach ([
            route('admin.homepage.hero-banners'),
            route('admin.banners.create'),
        ] as $url) {
            $this->actingAs($this->admin(), 'admin')
                ->get($url)
                ->assertOk()
                ->assertSee($mb.' MB', false)
                ->assertDontSee('max 5MB', false);
        }
    }

    public function test_a_tall_phone_export_is_not_refused_as_a_decompression_bomb(): void
    {
        // The pixel guard is there to stop a crafted image exhausting memory,
        // not to tell a designer their artwork is too detailed. A full-bleed
        // phone export past 5,000px on its long edge was being caught by it.
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.homepage.hero-banners.store'), [
                'name' => 'Tall',
                'image' => $this->image(400, 1426, 370),
                'mobile_image' => $this->image(2048, 3000, 6000),
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_avif_is_not_offered_because_the_framework_would_refuse_it(): void
    {
        // Worth pinning as a decision rather than leaving as an omission.
        // Production reads AVIF perfectly - GD, Imagick and finfo all do - but
        // Laravel's own `image` rule hardcodes jpg/jpeg/png/gif/bmp/webp, so a
        // file offered as AVIF would be accepted by the picker and then refused
        // by the validator. Not offering it is the kinder of the two.
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.banners.create'))
            ->assertOk()
            ->assertDontSee('image/avif', false);
    }
}
