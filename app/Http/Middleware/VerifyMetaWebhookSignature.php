<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // GET requests (webhook verification) don't carry signatures
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = config('services.meta.app_secret');

        // Skip verification if app secret isn't configured (dev mode)
        if (empty($appSecret)) {
            return $next($request);
        }

        if (empty($signature)) {
            abort(403, 'Missing webhook signature');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        if (!hash_equals($expected, $signature)) {
            abort(403, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
