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

        // on*="..." / on*='...' / on*=value — inline event handlers.
        $clean = preg_replace('/\son[a-z-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        // href/src/action pointing at javascript:, vbscript: or data: (data:
        // image/* is kept — it is the only common legitimate use).
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
