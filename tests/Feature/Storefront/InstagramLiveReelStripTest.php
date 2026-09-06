<?php

namespace Tests\Feature\Storefront;

use App\Models\AboutReel;
use App\Models\Setting;
use App\Services\InstagramReelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The About Us strip, drawn live from Instagram.
 *
 * The strip used to be whatever somebody had uploaded and put in order on
 * Homepage > About Reels, which meant it only changed when a person remembered
 * to change it. It is a random handful of the account's own reels now, redrawn
 * on every visit.
 *
 * Three things about that are easy to get wrong and are pinned here.
 *
 * 1. A Facebook-Login token cannot talk to graph.instagram.com AT ALL - it
 *    comes back "Cannot parse access token" - and the account is not /me
 *    either. Getting this wrong gives an empty strip and a log line.
 * 2. Asking /me/accounts to expand instagram_business_account inline returns
 *    the Pages WITHOUT it, silently, for a system-user token. Trusting that
 *    reads as "no Instagram account is linked" for an account that is linked.
 * 3. The strip must not go blank because Instagram is having a bad morning.
 *
 * The sync that copies reels onto this server is unchanged and covered in
 * Tests\Feature\Admin\InstagramReelSyncTest; the two coexist, and this file is
 * about what a visitor is served.
 */
class InstagramLiveReelStripTest extends TestCase
{
    use RefreshDatabase;

    /** A Facebook system-user token: the flavour the store actually has. */
    private const FB_TOKEN = 'EAAtest-facebook-token';

    private const PAGE_ID = '107815532068045';

    private const IG_USER_ID = '17841457324575327';

    protected function setUp(): void
    {
        parent::setUp();

        // An unmatched Http::fake pattern does not fail in Laravel, it goes out
        // to the real internet. Every assertion below about which endpoint was
        // called would otherwise be provable only by waiting for a timeout.
        Http::preventStrayRequests();

        Cache::flush();
        Setting::set(InstagramReelService::TOKEN_KEY, self::FB_TOKEN, 'string', 'instagram');
        Setting::set(InstagramReelService::LIMIT_KEY, '3', 'integer', 'instagram');
        Cache::flush();
    }

    private function service(): InstagramReelService
    {
        return app(InstagramReelService::class);
    }

    /** One media entry shaped as the Graph API returns it. */
    private function item(string $id, string $product = 'REELS', string $type = 'VIDEO'): array
    {
        return [
            'id' => $id,
            'media_type' => $type,
            'media_product_type' => $product,
            'media_url' => "https://cdn.instagram.test/{$id}.mp4",
            'thumbnail_url' => "https://cdn.instagram.test/{$id}.jpg",
            'permalink' => "https://www.instagram.com/reel/{$id}/",
            'timestamp' => '2026-09-01T10:00:00+0000',
        ];
    }

    /**
     * The Facebook path, end to end.
     *
     * Note what /me/accounts returns: id and name and NOTHING ELSE, which is
     * what the real edge does for a system-user token however the fields are
     * asked for. A resolver that reads the linked account off this response
     * finds nothing here, exactly as it finds nothing in production.
     */
    private function fakeFacebookAccount(array $items): void
    {
        Http::fake([
            'graph.facebook.com/*/me/accounts*' => fn () => Http::response([
                'data' => [['id' => self::PAGE_ID, 'name' => 'Dcrayons']],
            ]),
            'graph.facebook.com/*/'.self::IG_USER_ID.'/media*' => fn () => Http::response(['data' => $items]),
            'graph.facebook.com/*/'.self::PAGE_ID.'*' => fn () => Http::response([
                'id' => self::PAGE_ID,
                'instagram_business_account' => ['id' => self::IG_USER_ID, 'username' => 'dcrayons_consultancy'],
            ]),
        ]);
    }

    public function test_a_facebook_login_token_reaches_the_reels_through_its_page(): void
    {
        // graph.instagram.com rejects an EAA token outright, so before the
        // Facebook path existed this store's strip could only ever be empty.
        $this->fakeFacebookAccount([$this->item('REEL1'), $this->item('REEL2')]);

        $reels = $this->service()->randomReels();

        $this->assertCount(2, $reels);
        Http::assertSent(fn ($request) => str_contains($request->url(), self::IG_USER_ID.'/media'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'graph.instagram.com'));
    }

    public function test_the_page_node_is_asked_about_itself_rather_than_trusting_the_edge(): void
    {
        // The whole point of the second call. /me/accounts above answers with
        // no instagram_business_account in it; the account is only found by
        // fetching the Page. Drop that call and this store looks unlinked.
        $this->fakeFacebookAccount([$this->item('REEL1')]);

        $this->assertCount(1, $this->service()->randomReels());

        Http::assertSent(fn ($request) => str_contains($request->url(), '/'.self::PAGE_ID.'?')
            && str_contains(urldecode($request->url()), 'instagram_business_account'));
    }

    public function test_the_strip_links_to_instagram_rather_than_to_a_stored_copy(): void
    {
        // The opposite decision from sync(), and deliberately so: a link that is
        // re-signed every twenty minutes never reaches the point where its
        // signature expires, which is the only reason sync() has to download.
        $this->fakeFacebookAccount([$this->item('REEL1')]);

        $reel = $this->service()->randomReels()->first();

        $this->assertSame('https://cdn.instagram.test/REEL1.mp4', $reel->url);
        $this->assertSame('https://cdn.instagram.test/REEL1.jpg', $reel->poster_url);
        $this->assertFalse($reel->exists, 'A live reel was written to the database; it is signed and expiring.');
    }

    public function test_the_home_page_shows_the_instagram_reels_and_not_the_uploaded_ones(): void
    {
        AboutReel::query()->delete();
        AboutReel::create([
            'video_path' => 'storage/storefront/about/uploaded-by-hand.mp4',
            'position' => 1,
            'is_active' => true,
        ]);

        $this->fakeFacebookAccount([$this->item('REEL1'), $this->item('REEL2'), $this->item('REEL3')]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('cdn.instagram.test', $html);
        $this->assertStringNotContainsString('uploaded-by-hand.mp4', $html,
            'The uploaded clip is the fallback, not the strip - it should not show while Instagram is answering.');
    }

    public function test_the_handful_is_random_rather_than_the_same_three_every_time(): void
    {
        $pool = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $this->fakeFacebookAccount(array_map(fn ($id) => $this->item($id), $pool));

        $service = $this->service();
        $draws = [];

        for ($i = 0; $i < 25; $i++) {
            $draw = $service->randomReels()->pluck('instagram_media_id')->all();

            $this->assertCount(3, $draw, 'The strip should hold the configured number of reels.');
            $this->assertEmpty(array_diff($draw, $pool), 'A reel appeared that the account never posted.');

            $draws[] = implode(',', $draw);
        }

        // Twenty-five identical draws out of eight reels is not luck, it is a
        // shuffle that never happens - which is what showing the newest three
        // for ever looks like from here.
        $this->assertGreaterThan(1, count(array_unique($draws)),
            'Every draw was identical, so the strip is not random.');
    }

    public function test_the_shuffle_happens_per_visit_not_per_cache_fill(): void
    {
        // Shuffling before the cache is written would fix one order for the
        // whole twenty-minute window, so every visitor in it sees one strip.
        // The distinction is invisible until you look for it here.
        $this->fakeFacebookAccount(array_map(fn ($id) => $this->item($id), ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']));

        $service = $this->service();
        $draws = [];

        for ($i = 0; $i < 25; $i++) {
            $draws[] = implode(',', $service->randomReels()->pluck('instagram_media_id')->all());
        }

        Http::assertSentCount(3); // pages, page node, media - once, then cached
        $this->assertGreaterThan(1, count(array_unique($draws)),
            'The order was decided once when the cache was filled, not per visit.');
    }

    public function test_only_reels_reach_the_strip(): void
    {
        $this->fakeFacebookAccount([
            $this->item('PHOTO', 'FEED', 'IMAGE'),
            $this->item('REEL1'),
            $this->item('CAROUSEL', 'FEED', 'CAROUSEL_ALBUM'),
        ]);

        $this->assertSame(['REEL1'], $this->service()->randomReels()->pluck('instagram_media_id')->all());
    }

    public function test_the_strip_falls_back_to_the_uploaded_clips_when_instagram_is_down(): void
    {
        AboutReel::query()->delete();
        AboutReel::create([
            'video_path' => 'storage/storefront/about/uploaded-by-hand.mp4',
            'position' => 1,
            'is_active' => true,
        ]);

        Http::fake(['*' => fn () => Http::response(['error' => ['message' => 'Service unavailable', 'code' => 2]], 500)]);

        $reels = $this->service()->stripReels();

        $this->assertCount(1, $reels, 'The section emptied out because a third party was unreachable.');
        $this->assertSame('storage/storefront/about/uploaded-by-hand.mp4', $reels->first()->video_path);
    }

    public function test_an_outage_is_not_re_tried_on_every_single_page_view(): void
    {
        // A visitor is waiting on this call. Without a remembered failure, an
        // Instagram outage puts a request and its timeout in front of every
        // page view the site serves.
        Http::fake(['*' => fn () => Http::response(['error' => ['message' => 'Down', 'code' => 2]], 500)]);

        $service = $this->service();
        $service->stripReels();
        $service->stripReels();
        $service->stripReels();

        Http::assertSentCount(1);
    }

    public function test_a_new_reel_can_be_pushed_out_without_waiting_for_the_window(): void
    {
        $this->fakeFacebookAccount([$this->item('REEL1')]);
        $this->assertCount(1, $this->service()->randomReels());

        $this->service()->forgetLiveReels();
        $this->service()->randomReels();

        // pages + page node + media, then media again on the second fill: the
        // account resolution is cached separately and does not repeat.
        Http::assertSentCount(4);
    }

    // ------------------------------------------------------------- the token

    public function test_the_environment_token_connects_a_deploy_that_has_no_saved_row(): void
    {
        // The admin form is not the only way in. A box that arrives with
        // INSTAGRAM_ACCESS_TOKEN set should serve reels before anyone has
        // opened the admin at all.
        Setting::where('key', InstagramReelService::TOKEN_KEY)->delete();
        Cache::flush();
        Setting::flushMemo();
        config(['services.instagram.access_token' => self::FB_TOKEN]);

        $this->fakeFacebookAccount([$this->item('REEL1')]);

        $this->assertSame(self::FB_TOKEN, $this->service()->token());
        $this->assertCount(1, $this->service()->randomReels());
    }

    public function test_disconnecting_is_not_undone_by_the_environment_token(): void
    {
        // The trap in falling back to config: Setting::get() folds a blank
        // value into its default, so a token an admin has deliberately cleared
        // reads back exactly like one that was never set - and the strip they
        // just switched off carries on running from .env.
        config(['services.instagram.access_token' => self::FB_TOKEN]);

        $this->service()->disconnect();
        Cache::flush();
        Setting::flushMemo();

        $this->assertNull($this->service()->token());
        $this->assertFalse($this->service()->configured());
    }

    public function test_refreshing_a_facebook_token_says_what_to_do_instead(): void
    {
        // ig_refresh_token is an Instagram-Login endpoint. Letting the call go
        // out returns "Cannot parse access token", which reads like the saved
        // token is broken when it is working perfectly well.
        Http::fake(['*' => fn () => Http::response(['error' => ['message' => 'Cannot parse access token', 'code' => 190]], 400)]);

        $result = $this->service()->refreshToken();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Meta Business settings', $result['error']);
        Http::assertNothingSent();
    }

    public function test_an_unlinked_page_is_reported_as_such_rather_than_as_a_bad_token(): void
    {
        Http::fake([
            'graph.facebook.com/*/me/accounts*' => fn () => Http::response([
                'data' => [['id' => self::PAGE_ID, 'name' => 'Dcrayons']],
            ]),
            'graph.facebook.com/*/'.self::PAGE_ID.'*' => fn () => Http::response(['id' => self::PAGE_ID]),
        ]);

        $result = $this->service()->connect();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('No Instagram account is linked', $result['error']);
    }

    public function test_connecting_records_the_handle_found_through_the_page(): void
    {
        $this->fakeFacebookAccount([$this->item('REEL1')]);

        $result = $this->service()->connect();

        $this->assertTrue($result['ok']);
        $this->assertSame('dcrayons_consultancy', $result['username']);
    }
}
