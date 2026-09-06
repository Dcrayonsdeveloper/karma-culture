<?php

namespace App\Services;

use App\Models\AboutReel;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Pulls the store's own Instagram reels into the About Us strip.
 *
 * Two facts about Instagram shape everything here.
 *
 * 1. THERE IS NO PUBLIC LISTING ANY MORE. Fetching instagram.com/<handle>
 *    returns a page with no media in it at all - the posts are rendered by
 *    script after load - and the old ?__a=1 JSON endpoint is closed. So the
 *    reels of an account cannot be discovered without an access token, and
 *    scraping is neither possible nor permitted. This class talks to the
 *    documented API and nothing else.
 *
 * 2. media_url IS A SIGNED CDN LINK THAT EXPIRES, within days. Storing it would
 *    give a strip that works for a week and then shows nothing, with no error
 *    anywhere to explain it. Every clip is therefore downloaded once, at sync
 *    time, and served from this site afterwards.
 *
 * BOTH Instagram APIs are spoken here, because a token only works against the
 * one that issued it - see account() for how they are told apart and why the
 * Facebook-Login one needs two extra calls to find the account at all.
 *
 * See doc/instagram-reels.md.
 */
class InstagramReelService
{
    /** "Instagram API with Instagram Login": the account itself is /me. */
    private const IG_LOGIN_BASE = 'https://graph.instagram.com';

    /** "Instagram API with Facebook Login": /me is a Facebook user, not the account. */
    private const FB_LOGIN_BASE = 'https://graph.facebook.com/v21.0';

    /** Matches the 64MB ceiling the manual reel upload already enforces. */
    private const MAX_VIDEO_BYTES = 65536 * 1024;

    private const MAX_POSTER_BYTES = 5 * 1024 * 1024;

    /** Where synced files live, kept apart from hand-uploaded clips. */
    private const DISK_DIR = 'storefront/about/instagram';

    public const TOKEN_KEY = 'instagram_access_token';
    public const LIMIT_KEY = 'instagram_reel_limit';
    public const USERNAME_KEY = 'instagram_username';
    public const SYNCED_AT_KEY = 'instagram_reels_synced_at';
    public const TOKEN_EXPIRES_KEY = 'instagram_token_expires_at';

    /**
     * How long a batch of freshly signed CDN links is served to visitors.
     *
     * Twenty minutes against a signature that runs for a day and a half, so a
     * link is never handed out anywhere near the point where it stops working -
     * and Instagram is asked three times an hour however busy the site is.
     */
    private const LIVE_TTL = 1200;

    /** A failure is remembered briefly, so an outage is not re-tried per visitor. */
    private const LIVE_FAILURE_TTL = 300;

    /** A visitor is waiting on this call, so it gets far less rope than a sync. */
    private const LIVE_TIMEOUT = 8;

    /** Reels to draw the random handful from. Mixed feed, so ask for plenty. */
    private const LIVE_POOL = 50;

    private const LIVE_CACHE_PREFIX = 'instagram.live_reels.';

    private const ACCOUNT_CACHE_PREFIX = 'instagram.account.';

    /** Which Page a token can reach does not change hour to hour. */
    private const ACCOUNT_TTL = 43200;

    /**
     * The token to talk to Instagram with.
     *
     * The admin screen is the source of truth, and INSTAGRAM_ACCESS_TOKEN in
     * the environment is the fallback - which is what lets a deploy arrive
     * already connected instead of waiting for somebody to open a form and
     * paste a credential into it.
     *
     * The row is asked about with isSet() rather than read with get(), because
     * get() folds a blank value into its default and the two mean opposite
     * things here: no row at all is "nobody has configured this, use the
     * environment", while a row that exists and is empty is Disconnect having
     * been pressed - a decision the environment must not quietly reverse.
     */
    public function token(): ?string
    {
        if (Setting::isSet(self::TOKEN_KEY)) {
            $saved = trim((string) Setting::get(self::TOKEN_KEY, ''));

            return $saved === '' ? null : $saved;
        }

        $fromEnv = trim((string) config('services.instagram.access_token'));

        return $fromEnv === '' ? null : $fromEnv;
    }

    public function configured(): bool
    {
        return $this->token() !== null;
    }

    /** How many of the most recent reels the strip should hold. */
    public function limit(): int
    {
        $value = Setting::get(self::LIMIT_KEY, 6);

        return (int) max(1, min(20, is_numeric($value) ? (int) $value : 6));
    }

    public function username(): ?string
    {
        $name = trim((string) Setting::get(self::USERNAME_KEY, ''));

        return $name === '' ? null : $name;
    }

    public function lastSyncedAt(): ?\Illuminate\Support\Carbon
    {
        $at = Setting::get(self::SYNCED_AT_KEY);

        return $at ? \Illuminate\Support\Carbon::parse($at) : null;
    }

    public function tokenExpiresAt(): ?\Illuminate\Support\Carbon
    {
        $at = Setting::get(self::TOKEN_EXPIRES_KEY);

        return $at ? \Illuminate\Support\Carbon::parse($at) : null;
    }

    /**
     * Confirm a token works and record whose account it is.
     *
     * Called when the token is saved so the admin finds out immediately, rather
     * than at the next sync, that they pasted the wrong thing.
     *
     * @return array{ok:bool,username?:string,error?:string}
     */
    public function connect(): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'No Instagram access token is saved yet.'];
        }

        try {
            $account = $this->account();

            // The Facebook path already learned the handle on its way to the
            // account; the Instagram-Login one has to ask for it.
            $username = $account['username'] ?? (string) $this->call(
                $account['base'].'/'.$account['node'],
                ['fields' => 'id,username', 'access_token' => $this->token()],
                20,
            )->json('username', '');
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        Setting::set(self::USERNAME_KEY, $username, 'string', 'instagram');
        Cache::forget('settings.group.instagram');

        return ['ok' => true, 'username' => $username];
    }

    /**
     * What the About Us strip on the home page shows.
     *
     * Instagram first, so the strip stays current on its own: nobody uploads a
     * clip, nobody remembers to press Sync, and the account's newest work is on
     * the home page within twenty minutes of being posted.
     *
     * Stored rows are the fallback rather than the default. They are what the
     * strip shows before a token is configured, and what it drops back to while
     * Instagram is unreachable - so the section never empties out because a
     * third party is having a bad morning. A store that would rather curate the
     * strip by hand still can: disconnect Instagram and the uploaded clips are
     * all that is left to show.
     *
     * @return Collection<int,AboutReel>
     */
    public function stripReels(): Collection
    {
        $live = $this->randomReels();

        return $live->isNotEmpty() ? $live : AboutReel::active()->ordered()->get();
    }

    /**
     * Drop the cached reel list, so the next visitor's strip is fetched afresh.
     *
     * The cache key is derived from the token, so a token CHANGE invalidates
     * itself and needs none of this. What needs it is everything else: a reel
     * posted a minute ago, a reel deleted on Instagram, an admin who wants to
     * see the effect of what they just did rather than wait out the window.
     */
    public function forgetLiveReels(): void
    {
        $token = $this->token();

        if ($token !== null) {
            Cache::forget(self::LIVE_CACHE_PREFIX.substr(sha1($token), 0, 16));
        }
    }

    /**
     * A different handful of the account's reels each time the page is opened.
     *
     * @return Collection<int,AboutReel>
     */
    public function randomReels(?int $count = null): Collection
    {
        $reels = $this->liveReels();

        if ($reels === []) {
            return collect();
        }

        // Shuffled HERE, not before the cache is written. What is cached is the
        // account's reel list, so each visitor draws their own handful out of
        // it; shuffling on the way in would instead fix one order for twenty
        // minutes and show every visitor in that window the same strip.
        shuffle($reels);

        return collect(array_slice($reels, 0, $count ?? $this->limit()))
            ->map(fn (array $item) => $this->asReel($item));
    }

    /**
     * The account's reels, linked straight to Instagram's CDN.
     *
     * This hot-links where sync() downloads, and the two are not in conflict.
     * What makes a STORED media_url useless is that its signature expires
     * within days with nothing to say why; this list is re-signed every time
     * the cache is refilled, so no link is ever served anywhere near the point
     * where it stops working. Nothing is kept that outlives its own validity.
     *
     * Only the fields the strip renders are kept, because the rest of an item
     * is a long caption and a timestamp that would sit in the cache unread.
     *
     * @return array<int,array{id:string,media_url:string,thumbnail_url:string,permalink:?string}>
     */
    private function liveReels(): array
    {
        if (! $this->configured()) {
            return [];
        }

        $key = self::LIVE_CACHE_PREFIX.substr(sha1((string) $this->token()), 0, 16);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $reels = array_map(fn (array $item) => [
                'id' => (string) $item['id'],
                'media_url' => (string) $item['media_url'],
                'thumbnail_url' => (string) ($item['thumbnail_url'] ?? ''),
                'permalink' => $item['permalink'] ?? null,
            ], $this->onlyReels($this->fetchMedia(self::LIVE_POOL, self::LIVE_TIMEOUT)));
        } catch (\RuntimeException $e) {
            // Somebody is waiting on this render. Remembering the failure for a
            // few minutes keeps an Instagram outage from putting an API call,
            // and its timeout, in front of every single page view.
            Log::warning('Instagram reels unavailable for the About Us strip', ['error' => $e->getMessage()]);
            Cache::put($key, [], self::LIVE_FAILURE_TTL);

            return [];
        }

        Cache::put($key, $reels, self::LIVE_TTL);

        return $reels;
    }

    /**
     * One API item as an AboutReel the strip can render. Never saved.
     *
     * The strip's markup already speaks AboutReel - ->url and ->poster_url -
     * and both accessors pass an absolute URL straight through, so a live reel
     * drops into the same <x-media> as a stored one with nothing in the view to
     * tell them apart.
     *
     * @param  array{id:string,media_url:string,thumbnail_url:string,permalink:?string}  $item
     */
    private function asReel(array $item): AboutReel
    {
        return AboutReel::make([
            'video_path' => $item['media_url'],
            'poster_path' => $item['thumbnail_url'],
            'permalink' => $item['permalink'],
            'instagram_media_id' => $item['id'],
            'position' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Bring the strip in line with the account's most recent reels.
     *
     * Only ever touches rows this sync created. A clip somebody uploaded by
     * hand keeps its place and is never removed, because deleting a file the
     * store owner put there because Instagram happens not to list it would be
     * well past what "sync my reels" asks for.
     *
     * @return array{ok:bool,added:int,updated:int,removed:int,skipped:int,error?:string}
     */
    public function sync(): array
    {
        $empty = ['added' => 0, 'updated' => 0, 'removed' => 0, 'skipped' => 0];

        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Add an Instagram access token first.'] + $empty;
        }

        try {
            $media = $this->fetchMedia(min(50, $this->limit() * 4));
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()] + $empty;
        }

        $reels = array_slice($this->onlyReels($media), 0, $this->limit());

        if ($reels === []) {
            return ['ok' => false, 'error' => 'Instagram returned no reels for this account. Only reels are used; photos and stories are skipped.'] + $empty;
        }

        $added = 0;
        $updated = 0;
        $skipped = 0;
        $seen = [];

        foreach ($reels as $index => $item) {
            $mediaId = (string) $item['id'];
            $existing = AboutReel::where('instagram_media_id', $mediaId)->first();

            // Already here and the file is still on disk: only the ordering can
            // have changed. Re-downloading tens of megabytes to learn nothing
            // is the whole reason this check exists.
            if ($existing && $this->fileStillPresent($existing)) {
                $existing->update([
                    'position' => $index + 1,
                    'permalink' => $item['permalink'] ?? $existing->permalink,
                    'synced_at' => now(),
                ]);
                $seen[] = $existing->id;
                $updated++;

                continue;
            }

            $stored = $this->download($mediaId, $item);

            if ($stored === null) {
                $skipped++;

                continue;
            }

            $reel = AboutReel::updateOrCreate(
                ['instagram_media_id' => $mediaId],
                [
                    'video_path' => $stored['video'],
                    'poster_path' => $stored['poster'],
                    'permalink' => $item['permalink'] ?? null,
                    'position' => $index + 1,
                    'is_active' => $existing?->is_active ?? true,
                    'synced_at' => now(),
                ],
            );

            $seen[] = $reel->id;
            $existing ? $updated++ : $added++;
        }

        $removed = $this->pruneMissing($seen);

        Setting::set(self::SYNCED_AT_KEY, now()->toDateTimeString(), 'string', 'instagram');
        Cache::forget('settings.group.instagram');
        // The home page caches; a strip that still shows last week's reels
        // would make a successful sync look like it did nothing.
        Cache::flush();

        return ['ok' => true, 'added' => $added, 'updated' => $updated, 'removed' => $removed, 'skipped' => $skipped];
    }

    /**
     * Long-lived tokens last 60 days. This buys another 60.
     *
     * Worth knowing: Instagram refuses to refresh a token younger than 24
     * hours, and one that has already expired cannot be refreshed at all - it
     * has to be reissued by hand.
     *
     * @return array{ok:bool,expires_at?:string,error?:string}
     */
    public function refreshToken(): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'No Instagram access token is saved yet.'];
        }

        // ig_refresh_token is an Instagram-Login endpoint and there is no
        // Facebook equivalent to fall back to: a Business or system-user token
        // is reissued in Meta's own settings, not from here. Saying so beats
        // relaying "Cannot parse access token", which reads like the saved
        // token is broken when it is working perfectly well.
        if ($this->usesFacebookLogin((string) $this->token())) {
            return ['ok' => false, 'error' => 'This is a Facebook-Login token, which is not refreshed from here. System-user tokens are managed in Meta Business settings - reissue it there and paste the new one in above.'];
        }

        try {
            $response = Http::timeout(20)->get(self::IG_LOGIN_BASE.'/refresh_access_token', [
                'grant_type' => 'ig_refresh_token',
                'access_token' => $this->token(),
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach Instagram: '.$e->getMessage()];
        }

        if ($response->failed()) {
            return ['ok' => false, 'error' => $this->readableApiError($response)];
        }

        $new = (string) $response->json('access_token', '');

        if ($new === '') {
            return ['ok' => false, 'error' => 'Instagram did not return a new token.'];
        }

        $expiresAt = now()->addSeconds((int) $response->json('expires_in', 60 * 24 * 3600));

        Setting::set(self::TOKEN_KEY, $new, 'string', 'instagram');
        Setting::set(self::TOKEN_EXPIRES_KEY, $expiresAt->toDateTimeString(), 'string', 'instagram');
        Cache::forget('settings.group.instagram');

        return ['ok' => true, 'expires_at' => $expiresAt->toDateTimeString()];
    }

    /** Forget the token and drop every reel that came from Instagram. */
    public function disconnect(): int
    {
        $removed = 0;

        foreach (AboutReel::whereNotNull('instagram_media_id')->get() as $reel) {
            $this->deleteFiles($reel);
            $reel->delete();
            $removed++;
        }

        foreach ([self::TOKEN_KEY, self::USERNAME_KEY, self::SYNCED_AT_KEY, self::TOKEN_EXPIRES_KEY] as $key) {
            Setting::set($key, '', 'string', 'instagram');
        }

        Cache::forget('settings.group.instagram');
        Cache::flush();

        return $removed;
    }

    /**
     * The account's recent media, newest first.
     *
     * Callers ask for more than they need because the feed is mixed: photos and
     * carousels come back in the same list and are filtered out afterwards, so
     * asking for exactly N would often yield fewer than N reels.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchMedia(int $limit, int $timeout = 30): array
    {
        $account = $this->account();

        $response = $this->call($account['base'].'/'.$account['node'].'/media', [
            'fields' => 'id,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp',
            'limit' => $limit,
            'access_token' => $this->token(),
        ], $timeout);

        return $response->json('data', []) ?: [];
    }

    /**
     * Which API this token speaks, and the node its media hangs off.
     *
     * There are two Instagram APIs and a token works against exactly one.
     *
     * - "Instagram API with Instagram Login" issues IGAA... tokens that
     *   graph.instagram.com answers for directly, and there /me IS the creator
     *   account.
     * - "Instagram API with Facebook Login" - what Business Manager and system
     *   users hand out - issues EAA... tokens. graph.instagram.com rejects
     *   those flatly ("Cannot parse access token"), and /me is no use either:
     *   it is the Facebook user, while the reels hang off the Instagram account
     *   linked to one of that user's Pages.
     *
     * The prefix is what separates them, and it is dependable - every Facebook
     * Graph token begins EAA - so the flavour costs no round trip. Only the
     * Facebook one needs discovering, and that answer is cached: it is two more
     * calls, and which Page a token can reach does not change hour to hour.
     *
     * @return array{base:string,node:string,username:?string}
     *
     * @throws \RuntimeException
     */
    private function account(): array
    {
        $token = $this->token();

        if ($token === null) {
            throw new \RuntimeException('Add an Instagram access token first.');
        }

        if (! $this->usesFacebookLogin($token)) {
            return ['base' => self::IG_LOGIN_BASE, 'node' => 'me', 'username' => null];
        }

        // Keyed by the token, so pasting a new one resolves against the new
        // account at once rather than serving the old account's reels for
        // another twelve hours.
        $key = self::ACCOUNT_CACHE_PREFIX.substr(sha1($token), 0, 16);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $account = $this->discoverLinkedAccount($token);
        Cache::put($key, $account, self::ACCOUNT_TTL);

        return $account;
    }

    private function usesFacebookLogin(string $token): bool
    {
        return str_starts_with($token, 'EAA');
    }

    /**
     * The Instagram account behind a Facebook token.
     *
     * Two steps, and the second is not the redundant one it looks like: asking
     * /me/accounts to expand instagram_business_account inline comes back with
     * the Pages and WITHOUT that field - silently, no error - for a system-user
     * token, which reads exactly like "no Instagram account is linked here".
     * Fetching the Page node on its own returns it. So the edge is used only to
     * list the Pages, and each Page is then asked about itself.
     *
     * @return array{base:string,node:string,username:?string}
     *
     * @throws \RuntimeException
     */
    private function discoverLinkedAccount(string $token): array
    {
        $pages = $this->call(self::FB_LOGIN_BASE.'/me/accounts', [
            'fields' => 'id,name',
            'limit' => 50,
            'access_token' => $token,
        ], 20)->json('data', []) ?: [];

        foreach ($pages as $page) {
            if (empty($page['id'])) {
                continue;
            }

            $linked = $this->call(self::FB_LOGIN_BASE.'/'.$page['id'], [
                'fields' => 'instagram_business_account{id,username}',
                'access_token' => $token,
            ], 20)->json('instagram_business_account');

            if (! empty($linked['id'])) {
                return [
                    'base' => self::FB_LOGIN_BASE,
                    'node' => (string) $linked['id'],
                    'username' => $linked['username'] ?? null,
                ];
            }
        }

        throw new \RuntimeException($pages === []
            ? 'This Facebook token cannot see any Pages. It needs the pages_show_list permission, and the Instagram account has to be linked to a Page the token can reach.'
            : 'No Instagram account is linked to any Page this token can reach. Link the professional Instagram account to the Page in Meta Business settings, then connect again.');
    }

    /**
     * One Graph call, with its failures turned into something an admin can act on.
     *
     * @param  array<string,mixed>  $query
     *
     * @throws \RuntimeException
     */
    private function call(string $url, array $query, int $timeout): \Illuminate\Http\Client\Response
    {
        try {
            $response = Http::timeout($timeout)->get($url, $query);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not reach Instagram: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new \RuntimeException($this->readableApiError($response));
        }

        return $response;
    }

    /**
     * Reels only.
     *
     * media_product_type is the field that distinguishes a reel from an
     * ordinary video post, but it is not returned for every account type, so a
     * missing one falls back to "is it a video" rather than dropping the item.
     * Anything with no playable media_url is dropped either way.
     *
     * @param  array<int,array<string,mixed>>  $media
     * @return array<int,array<string,mixed>>
     */
    private function onlyReels(array $media): array
    {
        return array_values(array_filter($media, function ($item) {
            if (empty($item['id']) || empty($item['media_url'])) {
                return false;
            }

            $product = $item['media_product_type'] ?? null;

            return $product !== null
                ? $product === 'REELS'
                : ($item['media_type'] ?? null) === 'VIDEO';
        }));
    }

    /**
     * Fetch one reel's video and still onto the public disk.
     *
     * @param  array<string,mixed>  $item
     * @return array{video:string,poster:?string}|null
     */
    private function download(string $mediaId, array $item): ?array
    {
        $video = $this->fetchToDisk((string) $item['media_url'], $mediaId.'.mp4', self::MAX_VIDEO_BYTES);

        if ($video === null) {
            return null;
        }

        $poster = empty($item['thumbnail_url'])
            ? null
            : $this->fetchToDisk((string) $item['thumbnail_url'], $mediaId.'.jpg', self::MAX_POSTER_BYTES);

        return ['video' => $video, 'poster' => $poster];
    }

    /**
     * Stream a CDN file to the public disk, or null if it could not be had.
     *
     * Streamed to a temporary file rather than held in memory: a 60MB reel read
     * into a string is a 60MB string, and PHP's memory_limit on shared hosting
     * is not generous. One reel failing must not abort the run - the strip is
     * better off with four of five clips than with none.
     */
    private function fetchToDisk(string $url, string $filename, int $maxBytes): ?string
    {
        $temp = tempnam(sys_get_temp_dir(), 'igreel');

        if ($temp === false) {
            return null;
        }

        try {
            $response = Http::timeout(120)->sink($temp)->get($url);

            if ($response->failed()) {
                Log::warning('Instagram reel download failed', ['file' => $filename, 'status' => $response->status()]);

                return null;
            }

            $size = @filesize($temp) ?: 0;

            if ($size === 0) {
                return null;
            }

            if ($size > $maxBytes) {
                Log::warning('Instagram reel skipped: larger than the allowed size', [
                    'file' => $filename,
                    'bytes' => $size,
                    'max' => $maxBytes,
                ]);

                return null;
            }

            $path = self::DISK_DIR.'/'.$filename;
            $handle = fopen($temp, 'rb');

            if ($handle === false) {
                return null;
            }

            Storage::disk('public')->put($path, $handle);

            if (is_resource($handle)) {
                fclose($handle);
            }

            return 'storage/'.$path;
        } catch (\Throwable $e) {
            Log::warning('Instagram reel download errored', ['file' => $filename, 'error' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($temp);
        }
    }

    /**
     * Has the clip survived since the last sync?
     *
     * A row whose file has gone - a wiped storage directory, a botched deploy -
     * looks synced but renders an empty frame, so it is re-downloaded rather
     * than skipped.
     */
    private function fileStillPresent(AboutReel $reel): bool
    {
        return $reel->ownsFile() && Storage::disk('public')->exists($reel->storagePath());
    }

    /**
     * Drop synced reels the account no longer shows.
     *
     * Scoped to rows carrying an instagram_media_id, so a hand-uploaded clip in
     * the same strip is never caught by it.
     *
     * @param  array<int,int>  $keepIds
     */
    private function pruneMissing(array $keepIds): int
    {
        $stale = AboutReel::whereNotNull('instagram_media_id')
            ->whereNotIn('id', $keepIds ?: [0])
            ->get();

        foreach ($stale as $reel) {
            $this->deleteFiles($reel);
            $reel->delete();
        }

        return $stale->count();
    }

    private function deleteFiles(AboutReel $reel): void
    {
        if ($reel->ownsFile()) {
            Storage::disk('public')->delete($reel->storagePath());
        }

        if ($poster = $reel->posterStoragePath()) {
            Storage::disk('public')->delete($poster);
        }
    }

    /**
     * Instagram's error, in words an admin can act on.
     *
     * The raw body is a nested JSON object, and the message inside it is the
     * only part worth showing - but the two failures that actually happen (a
     * token that is not an Instagram-Login token, and one that has expired)
     * both come back saying only "Invalid OAuth access token", which does not
     * tell anybody what to do next.
     */
    private function readableApiError(\Illuminate\Http\Client\Response $response): string
    {
        $message = (string) ($response->json('error.message') ?? '');
        $code = (int) ($response->json('error.code') ?? 0);

        if ($code === 190 || str_contains(strtolower($message), 'access token')) {
            return 'Instagram rejected the access token. It may have expired, or it may be a Facebook-Login token - this needs one from Instagram API with Instagram Login. '.$message;
        }

        return $message !== ''
            ? 'Instagram said: '.$message
            : 'Instagram returned HTTP '.$response->status().'.';
    }
}
