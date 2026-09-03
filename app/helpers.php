<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return currency_config('symbol');
    }
}

if (!function_exists('currency_position')) {
    function currency_position(): string
    {
        return currency_config('position');
    }
}

if (!function_exists('format_price')) {
    function format_price(float|int|string|null $amount, int $decimals = 2): string
    {
        if ($amount === null) {
            $amount = 0;
        }

        $symbol = currency_symbol();
        $position = currency_position();
        $formatted = number_format((float) $amount, $decimals);

        return $position === 'after'
            ? $formatted . $symbol
            : $symbol . $formatted;
    }
}

if (!function_exists('format_date')) {
    /**
     * A date in the format the admin picked under Settings -> General.
     *
     * date_format was a required field on that screen that nothing read, so
     * choosing "d/m/Y" changed nothing anywhere. Dates are rendered through
     * this now, and the setting means what it says.
     *
     * Null in, empty string out: an order that has not shipped has no shipped
     * date, and the caller should not have to guard every one of them.
     */
    function format_date($date, ?string $fallback = null): string
    {
        if (empty($date)) {
            return '';
        }

        if (! $date instanceof DateTimeInterface) {
            $date = new DateTimeImmutable((string) $date);
        }

        $format = $fallback ?: (string) Setting::get('date_format', 'M d, Y');

        return $date->format($format ?: 'M d, Y');
    }
}

if (!function_exists('currency_config')) {
    function currency_config(?string $key = null): mixed
    {
        $config = Cache::remember('currency_config', 3600, function () {
            return [
                'symbol' => Setting::get('currency_symbol', '₹'),
                'position' => Setting::get('currency_position', 'before'),
                'code' => Setting::get('currency', 'INR'),
            ];
        });

        return $key ? ($config[$key] ?? null) : $config;
    }
}

if (!function_exists('safe_html')) {
    /**
     * Sanitise admin-authored rich text for display.
     *
     * strip_tags() alone is not enough: it keeps every attribute on the tags it
     * allows, so `<a href="javascript:...">` and `<p onclick="...">` survive a
     * tag allowlist untouched. This strips the tags, then removes event
     * handlers and script-bearing URLs from what is left.
     */
    function safe_html(?string $html, ?string $allowed = null): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $allowed ??= '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6>'
            . '<a><span><div><table><thead><tbody><tr><td><th><blockquote><hr>'
            . '<figure><figcaption><img><pre><code><small><sub><sup>';

        $clean = strip_tags($html, $allowed);

        // on*="..." / on*='...' / on*=value - inline event handlers.
        $clean = preg_replace('/\son[a-z-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        // href/src/action pointing at javascript:, vbscript: or data: (data:
        // image/* is kept - it is the only common legitimate use).
        $clean = preg_replace_callback(
            '/\s(href|src|action|formaction)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            function (array $m): string {
                $value = $m[2] ?: ($m[3] ?: ($m[4] ?? ''));
                $scheme = strtolower(preg_replace('/[\s\x00-\x1F]+/', '', $value));

                $blocked = str_starts_with($scheme, 'javascript:')
                    || str_starts_with($scheme, 'vbscript:')
                    || (str_starts_with($scheme, 'data:') && ! str_starts_with($scheme, 'data:image/'));

                return $blocked ? ' ' . strtolower($m[1]) . '="#"' : $m[0];
            },
            $clean
        ) ?? '';

        // style="" can carry url(javascript:...) and expression() on old engines.
        $clean = preg_replace('/\sstyle\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $clean);

        return $clean;
    }
}

if (!function_exists('asset_v')) {
    /**
     * asset() with a cache-busting fingerprint.
     *
     * Vite fingerprints the files it builds, so /build assets can be cached
     * forever safely. Everything else we serve out of /public cannot: the logo
     * in /images, and every product photo, banner and brand mark uploaded
     * through the admin, keep the same filename when they are replaced. A
     * browser holding yesterday's copy has no reason to ask for it again, so
     * the admin swaps an image, sees the new one on their own machine after a
     * hard refresh, and every customer keeps seeing the old one.
     *
     * Appending the file's modification time makes the URL itself change when
     * the bytes change, which invalidates the browser cache, any proxy in
     * between, and Hostinger's edge cache in one move — without depending on
     * mod_headers or mod_expires being present on the host.
     *
     * The stat is memoised per request; a missing file (or one on a remote
     * disk) falls through to a plain asset() rather than erroring.
     */
    function asset_v(?string $path, ?bool $secure = null): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        // Already absolute (S3, CDN, external) - nothing local to stat.
        if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        static $stamps = [];

        $relative = ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');

        if (!array_key_exists($relative, $stamps)) {
            $file = public_path($relative);
            // is_file() follows the public/storage symlink, which is how every
            // uploaded image resolves.
            $stamps[$relative] = is_file($file) ? @filemtime($file) : null;
        }

        $url = asset($path, $secure);
        $stamp = $stamps[$relative];

        if (!$stamp) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $stamp;
    }
}

if (!function_exists('is_offsite_url')) {
    /**
     * Does this admin-entered URL leave the storefront?
     *
     * Banner links, section buttons and the like are typed into the admin as
     * free text and land in two very different places: a campaign page, a
     * lookbook or a clip elsewhere on the web, or another page of this same
     * store. The two want opposite handling. Following an off-site link in
     * place closes the storefront on whoever clicked it and loses wherever
     * they had scrolled to, so those open in a tab of their own; a link back
     * into the store must not, because a banner for New In should move the
     * shopper along rather than leave the storefront open twice.
     *
     * Only an http(s) URL naming a host other than ours counts as off-site:
     *
     *  - "/new-in", "new-in", "?sort=new" and "#section" carry no host, so
     *    they are ours by definition.
     *  - mailto:, tel:, whatsapp: and every other scheme hands off to another
     *    app instead of navigating; target="_blank" on one of those leaves an
     *    empty tab behind, so none of them count.
     *  - "//example.com/x" is protocol-relative and does name a host.
     *
     * The host is matched against both the domain serving the request and the
     * configured APP_URL, either of which may be the canonical one - they
     * differ on the live site - and a leading "www." is ignored on both sides
     * so www.example.com is not read as a different site from example.com.
     */
    function is_offsite_url(?string $url): bool
    {
        $url = trim((string) $url);

        // Protocol-relative aside, anything that is not http(s) is either a
        // path on this site or a hand-off to another app. Neither is off-site.
        if (!str_starts_with($url, '//') && !preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        $bare = static fn (?string $h): string => preg_replace('/^www\./i', '', strtolower((string) $h));

        $ours = [$bare(parse_url((string) config('app.url'), PHP_URL_HOST))];

        // There is no request host under the console (queued mail, artisan),
        // so the configured URL is the only reference point there.
        if (app()->bound('request') && request()->server->has('HTTP_HOST')) {
            $ours[] = $bare(request()->getHost());
        }

        return !in_array($bare($host), array_filter($ours), true);
    }
}
