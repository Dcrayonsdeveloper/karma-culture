<?php

namespace App\Services;

use App\Models\AboutReel;
use App\Models\Setting;
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
 * The API used is "Instagram API with Instagram Login" (graph.instagram.com),
 * not the Facebook-Login one, because it needs only a Professional Instagram
 * account - no Facebook Page, no Business Manager. See doc/instagram-reels.md.
 */
class InstagramReelService
{
    private const BASE_URL = 'https://graph.instagram.com';

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

    public function token(): ?string
    {
        $token = trim((string) Setting::get(self::TOKEN_KEY, ''));

        return $token === '' ? null : $token;
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
            $response = Http::timeout(20)->get(self::BASE_URL.'/me', [
                'fields' => 'id,username',
                'access_token' => $this->token(),
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach Instagram: '.$e->getMessage()];
        }

        if ($response->failed()) {
            return ['ok' => false, 'error' => $this->readableApiError($response)];
        }

        $username = (string) $response->json('username', '');
        Setting::set(self::USERNAME_KEY, $username, 'string', 'instagram');
        Cache::forget('settings.group.instagram');

        return ['ok' => true, 'username' => $username];
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
            $media = $this->fetchMedia();
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

        try {
            $response = Http::timeout(20)->get(self::BASE_URL.'/refresh_access_token', [
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
     * Asks for more than the limit because the feed is mixed: photos and
     * carousels come back in the same list and are filtered out afterwards, so
     * asking for exactly N would often yield fewer than N reels.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchMedia(): array
    {
        try {
            $response = Http::timeout(30)->get(self::BASE_URL.'/me/media', [
                'fields' => 'id,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp',
                'limit' => min(50, $this->limit() * 4),
                'access_token' => $this->token(),
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not reach Instagram: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new \RuntimeException($this->readableApiError($response));
        }

        return $response->json('data', []) ?: [];
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
