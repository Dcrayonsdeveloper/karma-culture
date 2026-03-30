<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    public function handle(Request $request, Closure $next, int $minutes = 5): Response
    {
        // Only cache GET requests for unauthenticated users
        if ($request->method() !== 'GET' || auth()->check()) {
            return $next($request);
        }

        $key = 'response_cache.' . md5($request->fullUrl());

        if (Cache::has($key)) {
            $cached = Cache::get($key);
            return response($cached['content'], $cached['status'])
                ->withHeaders($cached['headers'])
                ->header('X-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            Cache::put($key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => array_map(fn ($h) => $h[0] ?? $h, $response->headers->all()),
            ], now()->addMinutes($minutes));

            $response->header('X-Cache', 'MISS');
        }

        return $response;
    }
}
