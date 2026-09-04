<?php

namespace Tests\Feature\Admin;

use App\Models\AboutReel;
use App\Models\Admin;
use App\Models\Setting;
use App\Models\User;
use App\Services\InstagramReelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pulling the About Us reel strip from Instagram.
 *
 * The defects pinned here are the ones the API's shape invites: media_url is a
 * signed link that expires, so a sync that stored it would work for a week and
 * then show nothing; the feed is mixed, so a naive import puts photos in a reel
 * strip; and the sync owns only its own rows, so it must never touch a clip
 * somebody uploaded by hand.
 */
class InstagramReelSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private InstagramReelService $service;

    /** What the next feed call returns. Swapped, not re-faked - see fakeFeed(). */
    private array $feedItems = [];

    private bool $feedFaked = false;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Cache::flush();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $this->adminUser->id, 'role' => 'super_admin', 'is_active' => true]);

        Setting::set(InstagramReelService::TOKEN_KEY, 'IGtoken-test', 'string', 'instagram');
        Cache::flush();

        $this->service = app(InstagramReelService::class);
    }

    /** One media entry as the Graph API returns it. */
    private function item(string $id, string $type = 'REELS', string $mediaType = 'VIDEO'): array
    {
        return [
            'id' => $id,
            'media_type' => $mediaType,
            'media_product_type' => $type,
            'media_url' => "https://cdn.instagram.test/{$id}.mp4?sig=expires-soon",
            'thumbnail_url' => "https://cdn.instagram.test/{$id}.jpg",
            'permalink' => "https://www.instagram.com/reel/{$id}/",
            'timestamp' => '2026-09-01T10:00:00+0000',
        ];
    }

    /**
     * Every fake is a CLOSURE, never a bare Http::response().
     *
     * Http::response() builds one Response and hands that same object to every
     * matching request. Its body is a stream, and ->sink() consumes it, so the
     * second download from the same fake writes zero bytes and reads as a
     * failed fetch - which is a property of the test double, not of the code.
     * A closure is invoked per request, so each one gets its own stream.
     */
    private function fakeFeed(array $items): void
    {
        $this->feedItems = $items;

        // Registered ONCE. Http::fake() merges its stubs with the ones already
        // registered rather than replacing them, and the earliest matching stub
        // wins - so calling it a second time to change the feed silently leaves
        // the first answer in place, and a test that expects the account to have
        // changed keeps seeing the old reels. Reading the items off the instance
        // is what actually swaps them.
        if ($this->feedFaked) {
            return;
        }

        $this->feedFaked = true;

        Http::fake([
            'graph.instagram.com/me/media*' => fn () => Http::response(['data' => $this->feedItems]),
            'graph.instagram.com/me*' => fn () => Http::response(['id' => '17841400000000000', 'username' => 'ashubieber']),
            'cdn.instagram.test/*.mp4*' => fn () => Http::response('fake-mp4-bytes', 200),
            'cdn.instagram.test/*.jpg*' => fn () => Http::response('fake-jpg-bytes', 200),
        ]);
    }

    /**
     * Reels that came from Instagram.
     *
     * Never a bare AboutReel::count(): the create-table migration backfills
     * three rows from the settings slots it replaced, so every test starts with
     * a strip that already has clips in it. Counting the whole table would be
     * counting those too.
     */
    private function syncedReels(): \Illuminate\Database\Eloquent\Collection
    {
        return AboutReel::whereNotNull('instagram_media_id')->orderBy('position')->get();
    }

    public function test_a_sync_downloads_each_reel_rather_than_linking_to_instagram(): void
    {
        // media_url is signed and expires within days. Storing it would give a
        // strip that works this week and is blank the next, with nothing to say
        // why - so the bytes have to be brought over.
        $this->fakeFeed([$this->item('AAA')]);

        $result = $this->service->sync();

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['added']);

        $reel = AboutReel::firstWhere('instagram_media_id', 'AAA');
        $this->assertNotNull($reel);
        $this->assertStringStartsNotWith('http', $reel->video_path,
            'The reel points at Instagram\'s CDN, so the strip goes blank when the signature expires.');
        Storage::disk('public')->assertExists('storefront/about/instagram/AAA.mp4');
    }

    public function test_the_thumbnail_is_stored_as_the_poster(): void
    {
        $this->fakeFeed([$this->item('AAA')]);

        $this->service->sync();

        $reel = AboutReel::firstWhere('instagram_media_id', 'AAA');
        Storage::disk('public')->assertExists('storefront/about/instagram/AAA.jpg');
        $this->assertNotNull($reel->poster_url);
    }

    public function test_only_reels_are_imported(): void
    {
        $this->fakeFeed([
            $this->item('REEL1'),
            $this->item('PHOTO1', 'FEED', 'IMAGE'),
            $this->item('CAROUSEL1', 'FEED', 'CAROUSEL_ALBUM'),
            $this->item('REEL2'),
        ]);

        $result = $this->service->sync();

        $this->assertSame(2, $result['added']);
        $this->assertSame(['REEL1', 'REEL2'], $this->syncedReels()->pluck('instagram_media_id')->all());
    }

    public function test_a_video_post_counts_when_instagram_omits_the_product_type(): void
    {
        // media_product_type is not returned for every account type. Dropping
        // items that lack it would import nothing at all for those accounts.
        $item = $this->item('VID1');
        unset($item['media_product_type']);

        $this->fakeFeed([$item]);

        $this->assertSame(1, $this->service->sync()['added']);
    }

    public function test_an_item_with_no_playable_url_is_skipped_not_stored_broken(): void
    {
        $item = $this->item('BROKEN');
        unset($item['media_url']);

        $this->fakeFeed([$item, $this->item('GOOD')]);

        $result = $this->service->sync();

        $this->assertSame(1, $result['added']);
        $this->assertNull(AboutReel::firstWhere('instagram_media_id', 'BROKEN'));
    }

    public function test_syncing_twice_does_not_duplicate_or_re_download(): void
    {
        $this->fakeFeed([$this->item('AAA'), $this->item('BBB')]);

        $this->service->sync();
        $second = app(InstagramReelService::class)->sync();

        $this->assertCount(2, $this->syncedReels());
        $this->assertSame(0, $second['added'], 'A second sync re-added reels that were already here.');
        $this->assertSame(2, $second['updated']);

        // First sync: one feed call plus a video and a poster each. Second sync:
        // the feed call and nothing else. Re-fetching tens of megabytes to learn
        // nothing has changed is the cost this guard exists to avoid.
        Http::assertSentCount(6);
    }

    public function test_the_limit_is_respected(): void
    {
        Setting::set(InstagramReelService::LIMIT_KEY, '2', 'integer', 'instagram');
        Cache::flush();

        $this->fakeFeed([$this->item('A'), $this->item('B'), $this->item('C'), $this->item('D')]);

        $this->assertSame(2, app(InstagramReelService::class)->sync()['added']);
    }

    public function test_a_reel_deleted_on_instagram_is_removed_from_the_strip(): void
    {
        $this->fakeFeed([$this->item('AAA'), $this->item('BBB')]);
        $this->service->sync();

        $this->fakeFeed([$this->item('AAA')]);
        $result = app(InstagramReelService::class)->sync();

        $this->assertSame(1, $result['removed']);
        $this->assertNull(AboutReel::firstWhere('instagram_media_id', 'BBB'));
        Storage::disk('public')->assertMissing('storefront/about/instagram/BBB.mp4');
    }

    public function test_a_hand_uploaded_clip_is_never_touched_by_a_sync(): void
    {
        // The one thing that would be unforgivable: deleting a file the store
        // owner put there because Instagram happens not to list it.
        $manual = AboutReel::create([
            'video_path' => 'storage/storefront/about/hand-uploaded.mp4',
            'position' => 1,
            'is_active' => true,
        ]);

        $this->fakeFeed([$this->item('AAA')]);
        $this->service->sync();

        $this->assertNotNull($manual->fresh(), 'The sync deleted a clip that was uploaded by hand.');

        $this->fakeFeed([]);
        app(InstagramReelService::class)->sync();

        $this->assertNotNull($manual->fresh());
    }

    public function test_a_missing_file_is_re_downloaded_rather_than_left_blank(): void
    {
        $this->fakeFeed([$this->item('AAA')]);
        $this->service->sync();

        // A wiped storage directory or a botched deploy: the row still looks
        // synced but the frame renders empty.
        Storage::disk('public')->delete('storefront/about/instagram/AAA.mp4');

        app(InstagramReelService::class)->sync();

        Storage::disk('public')->assertExists('storefront/about/instagram/AAA.mp4');
    }

    public function test_a_hidden_reel_stays_hidden_across_a_sync(): void
    {
        $this->fakeFeed([$this->item('AAA')]);
        $this->service->sync();

        AboutReel::firstWhere('instagram_media_id', 'AAA')->update(['is_active' => false]);
        Storage::disk('public')->delete('storefront/about/instagram/AAA.mp4');

        app(InstagramReelService::class)->sync();

        $this->assertFalse(AboutReel::firstWhere('instagram_media_id', 'AAA')->is_active,
            'A reel the admin hid came back onto the home page after a sync.');
    }

    public function test_an_expired_token_produces_an_actionable_message(): void
    {
        Http::fake([
            'graph.instagram.com/*' => Http::response([
                'error' => ['message' => 'Error validating access token: Session has expired.', 'code' => 190],
            ], 400),
        ]);

        $result = $this->service->sync();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('token', strtolower($result['error']));
        $this->assertCount(0, $this->syncedReels());
    }

    public function test_a_failed_download_skips_that_reel_without_losing_the_rest(): void
    {
        Http::fake([
            'graph.instagram.com/me/media*' => fn () => Http::response(['data' => [$this->item('GOOD'), $this->item('BAD')]]),
            'cdn.instagram.test/BAD.mp4*' => fn () => Http::response('', 404),
            'cdn.instagram.test/*' => fn () => Http::response('bytes', 200),
        ]);

        $result = $this->service->sync();

        $this->assertSame(1, $result['added']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotNull(AboutReel::firstWhere('instagram_media_id', 'GOOD'));
    }

    // ------------------------------------------------------------------ admin

    public function test_the_screen_requires_an_admin(): void
    {
        $this->get('/admin/homepage/about-reels')->assertRedirect(route('admin.login'));
    }

    public function test_the_saved_token_is_never_rendered_back_into_the_page(): void
    {
        // It is a credential. Printing it puts it in every browser cache, proxy
        // log and screen share that ever opens this screen.
        $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/homepage/about-reels')
            ->assertOk()
            ->assertDontSee('IGtoken-test');
    }

    public function test_saving_settings_without_a_token_keeps_the_existing_one(): void
    {
        // The field renders masked, so an admin changing only the count must not
        // wipe the token by submitting an empty placeholder back.
        $this->actingAs($this->adminUser, 'admin');
        $this->fakeFeed([]);

        $this->put('/admin/homepage/about-reels/instagram', ['reel_limit' => 8])->assertRedirect();

        Cache::flush();
        $this->assertSame('IGtoken-test', app(InstagramReelService::class)->token());
        $this->assertSame(8, app(InstagramReelService::class)->limit());
    }

    public function test_the_instagram_route_is_not_swallowed_by_the_reel_wildcard(): void
    {
        // PUT /about-reels/instagram matches {aboutReel} first if the wildcard
        // is declared above it, and 404s trying to bind a reel called
        // "instagram" - the ordering bug that once ate /cart/remove-coupon.
        $this->actingAs($this->adminUser, 'admin');
        $this->fakeFeed([]);

        $this->put('/admin/homepage/about-reels/instagram', ['reel_limit' => 5])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_disconnecting_removes_synced_reels_but_keeps_uploads(): void
    {
        $manual = AboutReel::create([
            'video_path' => 'storage/storefront/about/mine.mp4',
            'position' => 1,
            'is_active' => true,
        ]);

        $this->fakeFeed([$this->item('AAA')]);
        $this->service->sync();

        $this->actingAs($this->adminUser, 'admin')
            ->delete('/admin/homepage/about-reels/instagram')
            ->assertRedirect();

        $this->assertNull(AboutReel::firstWhere('instagram_media_id', 'AAA'));
        $this->assertNotNull($manual->fresh());
        Cache::flush();
        $this->assertFalse(app(InstagramReelService::class)->configured());
    }

    public function test_the_sync_button_reports_what_it_did(): void
    {
        $this->fakeFeed([$this->item('AAA')]);

        $this->actingAs($this->adminUser, 'admin')
            ->post('/admin/homepage/about-reels/instagram/sync')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertCount(1, $this->syncedReels());
    }

    public function test_the_token_refresh_stores_the_new_token_and_its_expiry(): void
    {
        Http::fake([
            'graph.instagram.com/refresh_access_token*' => Http::response([
                'access_token' => 'IGtoken-refreshed',
                'token_type' => 'bearer',
                'expires_in' => 5184000,
            ]),
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->post('/admin/homepage/about-reels/instagram/refresh-token')
            ->assertRedirect()
            ->assertSessionHas('success');

        Cache::flush();
        $service = app(InstagramReelService::class);
        $this->assertSame('IGtoken-refreshed', $service->token());
        $this->assertTrue($service->tokenExpiresAt()->isFuture());
    }

    public function test_the_access_token_is_sent_to_instagram_and_not_leaked_in_the_path(): void
    {
        $this->fakeFeed([$this->item('AAA')]);

        $this->service->sync();

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), 'graph.instagram.com/me/media')) {
                return false;
            }

            return str_contains($request->url(), 'access_token=IGtoken-test');
        });
    }
}
