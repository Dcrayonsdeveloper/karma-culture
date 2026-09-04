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

        // Fail closed, not open.
        //
        // This waved the request through whenever META_APP_SECRET was unset,
        // "for dev". The secret is unset on any install that has not wired Meta
        // up yet, which left POST /api/webhook/meta as a public, unauthenticated
        // way into the assistant: anyone could post a message payload, drive the
        // bot, spend Anthropic credits, and have replies delivered from the
        // brand's own Meta page. An unconfigured webhook has to refuse traffic,
        // not accept all of it.
        if (empty($appSecret) || empty($signature)) {
            abort(403, 'Webhook signature required');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        if (!hash_equals($expected, $signature)) {
            abort(403, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
