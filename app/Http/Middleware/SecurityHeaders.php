<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Voice search needs the microphone on our own origin. A bare
        // `microphone=()` switches it off for everyone including us, so Chrome
        // refuses the request without ever prompting the customer.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(self), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Signed-in pages must not survive the session that produced them.
        //
        // Laravel leaves these responses on Symfony's default `no-cache, private`,
        // which still lets the browser keep the rendered page and hand it back on
        // Back/Forward without asking us. So after "Logout" the visitor presses Back
        // and their account, orders and addresses are still on screen — the session
        // is gone, but the pages it produced are not. On a shared or public machine
        // that hands the next person the previous one's account.
        //
        // `no-store` is the only directive that forbids keeping the copy at all.
        // It costs those users bfcache on back-navigation, which is the accepted
        // trade for not leaking an account to whoever sits down next.
        if ($this->isAuthenticated($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * Is anyone signed in on this request, under either guard?
     *
     * Read after the response is built, so the session middleware has already
     * run. The hasSession() gate keeps this off stateless routes: there,
     * check() would fall through to the remember-me cookie and issue a user
     * lookup on requests that never wanted one.
     */
    private function isAuthenticated(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        foreach (['web', 'admin'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return true;
            }
        }

        return false;
    }
}
