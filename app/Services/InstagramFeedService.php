<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The Instagram strip on the home page.
 *
 * Two things kept this empty. It called graph.instagram.com - the Basic
 * Display API, for personal accounts - with a Facebook system-user token, and
 * that endpoint cannot even parse such a token: it answers "Cannot parse access
 * token" for credentials that are entirely valid, which reads like an expired
 * token and is not one. And it then discarded every VIDEO post, which on this
 * account is 18 of the most recent 25, because Reels are what the account
 * mostly publishes.
 *
 * Business accounts are read from the Facebook Graph API by their own numeric
 * id. Videos are kept, and carry both a poster still and the clip itself so the
 * strip can play rather than imply.
 */
class InstagramFeedService
{
    /** Post shapes worth showing. Stories and ads are neither. */
    private const RENDERABLE = ['IMAGE', 'CAROUSEL_ALBUM', 'VIDEO'];

    protected string $accessToken;

    protected string $version;

    /** Set only when INSTAGRAM_USER_ID pins it; otherwise discovered. */
    protected ?string $configuredUserId;

    public function __construct()
    {
        $this->accessToken = (string) config('services.instagram.access_token', '');
        $this->configuredUserId = config('services.instagram.user_id') ?: null;
        $this->version = (string) config('services.instagram.graph_version', 'v21.0');
    }

    /**
     * Which Instagram account this token speaks for.
     *
     * Dropping the token into .env is meant to be the whole setup, so the
     * account id is worked out from the token rather than asked for as a second
     * value nobody has to hand: the token names its Pages, and a Page names the
     * Instagram Business account attached to it. Cached for a day, because it
     * only changes when somebody re-links the accounts in Meta's settings.
     *
     * INSTAGRAM_USER_ID still wins when set - a token with several Pages on it
     * needs someone to say which, and discovery would otherwise pick the first.
     */
    protected function userId(): ?string
    {
        if ($this->configuredUserId !== null) {
            return $this->configuredUserId;
        }

        if ($this->accessToken === '') {
            return null;
        }

        // Keyed on the token: a new token may well mean a different account,
        // and it must not read the previous one's id out of the cache.
        $key = 'instagram_account:'.substr(sha1($this->accessToken), 0, 16);

        return Cache::remember($key, 86400, function () {
            try {
                $response = Http::timeout(10)->get(
                    "https://graph.facebook.com/{$this->version}/me/accounts",
                    [
                        'fields' => 'instagram_business_account{id,username}',
                        'access_token' => $this->accessToken,
                    ]
                );

                if (! $response->successful()) {
                    Log::warning('Instagram account discovery failed', [
                        'status' => $response->status(),
                        'error' => $response->json('error.message'),
                    ]);

                    return null;
                }

                foreach ($response->json('data', []) as $page) {
                    if (! empty($page['instagram_business_account']['id'])) {
                        return (string) $page['instagram_business_account']['id'];
                    }
                }

                // A token with Pages but no Instagram attached to any of them.
                // Nothing is wrong with the token; there is simply no feed.
                Log::warning('Instagram token reaches no Page with a linked Business account');

                return null;
            } catch (\Throwable $e) {
                Log::error('Instagram account discovery error: '.$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Recent posts, newest first.
     *
     * @return array<int, array{id:string,type:string,is_video:bool,image:string,video:?string,caption:string,link:string}>
     */
    public function getPosts(int $limit = 6): array
    {
        $userId = $this->userId();

        // No token, or a token with no Instagram behind it. The section hides
        // itself rather than drawing an empty strip.
        if ($this->accessToken === '' || $userId === null) {
            return [];
        }

        return Cache::remember("instagram_feed:{$userId}:{$limit}", 3600, function () use ($limit, $userId) {
            try {
                $response = Http::timeout(10)->get(
                    "https://graph.facebook.com/{$this->version}/{$userId}/media",
                    [
                        // thumbnail_url is only populated for VIDEO; it is the
                        // poster frame. media_url on a VIDEO is the clip, so
                        // using it as an <img> src is how a strip of Reels
                        // turns into a strip of broken images.
                        'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
                        'access_token' => $this->accessToken,
                        // Over-fetch: filtering happens after the response, so
                        // asking for exactly $limit can return fewer.
                        'limit' => max($limit * 3, 25),
                    ]
                );

                if (! $response->successful()) {
                    Log::warning('Instagram feed request failed', [
                        'status' => $response->status(),
                        'error' => $response->json('error.message'),
                    ]);

                    return [];
                }

                return collect($response->json('data', []))
                    ->filter(fn ($post) => in_array($post['media_type'] ?? '', self::RENDERABLE, true))
                    ->map(fn ($post) => $this->shape($post))
                    // A post with no usable still is dropped rather than
                    // rendered as an empty frame.
                    ->filter(fn ($post) => $post['image'] !== '')
                    ->take($limit)
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                Log::error('Instagram feed fetch error: '.$e->getMessage());

                return [];
            }
        });
    }

    /**
     * One API post reduced to what the strip actually renders.
     *
     * @param  array<string, mixed>  $post
     * @return array<string, mixed>
     */
    private function shape(array $post): array
    {
        $isVideo = ($post['media_type'] ?? '') === 'VIDEO';

        return [
            'id' => (string) ($post['id'] ?? ''),
            'type' => (string) ($post['media_type'] ?? ''),
            'is_video' => $isVideo,
            // The still: a video's poster frame, otherwise the picture itself.
            'image' => (string) ($isVideo
                ? ($post['thumbnail_url'] ?? '')
                : ($post['media_url'] ?? '')),
            // The clip, for the ones that have one.
            'video' => $isVideo ? ($post['media_url'] ?? null) : null,
            'caption' => (string) ($post['caption'] ?? ''),
            'link' => (string) ($post['permalink'] ?? ''),
        ];
    }

    /**
     * The handle of whichever account is actually configured.
     *
     * The "Follow @…" button under the strip was a hardcoded handle, so it
     * pointed at one account while the posts above it came from another - and
     * it would keep pointing there after the credentials were changed, silently.
     * Asking the API means the button always names the account the shopper is
     * looking at. Null when unconfigured or unreachable, so the caller can fall
     * back to whatever it wants to show instead.
     */
    public function getUsername(): ?string
    {
        $userId = $this->userId();

        if ($this->accessToken === '' || $userId === null) {
            return null;
        }

        return Cache::remember("instagram_handle:{$userId}", 3600, function () use ($userId) {
            try {
                $response = Http::timeout(10)->get(
                    "https://graph.facebook.com/{$this->version}/{$userId}",
                    ['fields' => 'username', 'access_token' => $this->accessToken]
                );

                return $response->successful()
                    ? ($response->json('username') ?: null)
                    : null;
            } catch (\Throwable $e) {
                Log::error('Instagram handle fetch error: '.$e->getMessage());

                return null;
            }
        });
    }

    public function clearCache(): void
    {
        $userId = $this->userId();

        if ($userId !== null) {
            Cache::forget("instagram_feed:{$userId}:6");
            Cache::forget("instagram_handle:{$userId}");
        }

        // The discovery entry too, so a swapped token is re-resolved rather
        // than serving the old account's id for another day.
        if ($this->accessToken !== '') {
            Cache::forget('instagram_account:'.substr(sha1($this->accessToken), 0, 16));
        }
    }
}
