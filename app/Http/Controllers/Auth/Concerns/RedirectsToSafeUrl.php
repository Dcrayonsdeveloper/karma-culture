<?php

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Http\Request;

/**
 * A same-origin replacement for redirect()->intended().
 *
 * `intended()` hands whatever sits in session('url.intended') straight to the
 * Redirector, which does not check the host. Laravel itself only ever writes
 * the current request URL there, so the stock flow is safe — but every
 * authentication controller in the app funnels through that one session key,
 * and anything that later writes to it (a "?redirect=" parameter wired into
 * setIntendedUrl, a package, a future controller) turns login into an open
 * redirect: the victim signs in on the real site and lands on the attacker's
 * page still believing they are on it, which is exactly the shape a credential
 * phishing chain needs.
 *
 * Checking the destination at the point of use costs nothing and closes it
 * permanently, whatever put the value there.
 */
trait RedirectsToSafeUrl
{
    /**
     * Pull the intended URL, and fall back to $default unless it points at
     * this host.
     */
    protected function safeIntendedUrl(Request $request, string $default): string
    {
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended)) {
            return $default;
        }

        $intended = trim($intended);

        if ($intended === '') {
            return $default;
        }

        // "//evil.com" and "/\evil.com" are protocol-relative: a browser reads
        // both as another origin even though they open with a slash, so a
        // leading-slash test on its own is not enough. Backslashes are
        // normalised to forward slashes by browsers before the host is parsed.
        $normalized = str_replace('\\', '/', $intended);

        if (str_starts_with($normalized, '//')) {
            return $default;
        }

        // A root-relative path can only ever stay on this origin.
        if (str_starts_with($normalized, '/')) {
            return $intended;
        }

        $host = parse_url($intended, PHP_URL_HOST);

        if (! is_string($host) || strcasecmp($host, $request->getHost()) !== 0) {
            return $default;
        }

        $scheme = parse_url($intended, PHP_URL_SCHEME);

        if ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return $default;
        }

        return $intended;
    }
}
