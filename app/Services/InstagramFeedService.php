<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramFeedService
{
    protected string $accessToken;
    protected string $apiUrl = 'https://graph.instagram.com';

    public function __construct()
    {
        // Coalesce rather than lean on config()'s default: that default only
        // applies when the KEY IS ABSENT, and config/services.php defines this
        // one - so an unset INSTAGRAM_ACCESS_TOKEN resolves to null, not to the
        // '' passed here, and assigning null to a string property is a
        // TypeError that took the whole home page down anywhere the token was
        // not configured. getPosts() already treats an empty token as "no feed",
        // so '' is the right resting state.
        //
        // env() is dropped with it: config/services.php already reads that
        // variable, and calling env() outside config breaks under config:cache,
        // where it returns null on a production box that has the value set.
        $this->accessToken = (string) (config('services.instagram.access_token') ?? '');
    }

    /**
     * Get recent Instagram posts.
     * 
     * @param int $limit Number of posts to fetch
     * @return array
     */
    public function getPosts(int $limit = 6): array
    {
        if (empty($this->accessToken)) {
            return [];
        }

        // Cache for 1 hour to avoid hitting API limits
        return Cache::remember('instagram_feed', 3600, function () use ($limit) {
            try {
                $response = Http::timeout(10)->get("{$this->apiUrl}/me/media", [
                    'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
                    'access_token' => $this->accessToken,
                    'limit' => $limit,
                ]);

                if ($response->successful()) {
                    $data = $response->json('data', []);
                    
                    // Filter only images and carousels (skip videos for now)
                    return collect($data)
                        ->filter(fn ($post) => in_array($post['media_type'], ['IMAGE', 'CAROUSEL_ALBUM']))
                        ->take($limit)
                        ->map(fn ($post) => [
                            'id' => $post['id'],
                            'image' => $post['media_url'],
                            'caption' => $post['caption'] ?? '',
                            'link' => $post['permalink'],
                            'type' => $post['media_type'],
                        ])
                        ->values()
                        ->toArray();
                }

                Log::warning('Instagram API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Instagram feed fetch error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Clear the Instagram feed cache.
     */
    public function clearCache(): void
    {
        Cache::forget('instagram_feed');
    }
}
