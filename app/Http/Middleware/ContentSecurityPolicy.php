<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // NOTE ON script-src.
        //
        // A nonce-based policy is the goal, but the app cannot satisfy one yet:
        // around forty Blade views carry inline <script> blocks that render no
        // nonce, and Alpine 3's standard build evaluates its x-* expressions
        // with new Function(), which needs 'unsafe-eval'. Shipping a nonce
        // policy would silently disable every dropdown, modal, carousel and
        // cart interaction on the site.
        //
        // A nonce also *overrides* 'unsafe-inline' wherever browsers see both,
        // so the nonce is deliberately not emitted here — adding it back
        // without first doing the work below would break the storefront.
        //
        // To harden this properly: emit the nonce on every inline script (or
        // move them into bundled modules), switch to Alpine's CSP build and
        // convert inline expressions to component methods, then restore
        // "script-src 'self' 'nonce-{...}'".
        //
        // Every other directive below is enforced today and is worth having on
        // its own: it still blocks framing, base-tag injection and off-site
        // form posts.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net https://connect.facebook.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "img-src 'self' data: blob: https:",
            // The About Us strip plays Instagram's own reels, linked straight
            // to their CDN rather than copied here, because the link is signed
            // and re-signed rather than stored - see InstagramReelService.
            //
            // There is no media-src here at all today, so <video> falls through
            // to default-src 'self' and every one of those clips is blocked.
            // That costs nothing yet only because this middleware is not
            // registered; whoever turns it on would find the strip blank with
            // nothing but a console entry to say why.
            //
            // The wildcards are not laziness: the host is a per-POP name picked
            // at request time (instagram.fdel77-1.fna.fbcdn.net today, some
            // other POP tomorrow) and the video URL redirects to a second host
            // again, so no fixed list of origins would hold.
            "media-src 'self' blob: data: https://*.fbcdn.net https://*.cdninstagram.com",
            "font-src 'self' https://fonts.bunny.net",
            "connect-src 'self' https://www.facebook.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
