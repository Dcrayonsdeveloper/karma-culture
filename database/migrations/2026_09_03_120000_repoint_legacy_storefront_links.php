<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repoints stored links that still name a legacy storefront path.
 *
 * /products, /returns and /orders/{id} were kept alive as 301s to the pages
 * that replaced them. That redirect is gone: a path the site does not serve now
 * answers 404, so a visitor who types a wrong address is told so rather than
 * being dropped on a page they did not ask for.
 *
 * The cost of removing a redirect is that anything still pointing at the old
 * path becomes a broken link, and some of those links are data, not code - a
 * hero banner's target, a homepage section's button, a footer menu entry, all
 * of them editable in the admin and seeded with '/products' by BeautySeeder.
 * Those are rewritten here to the page they were always meant to open, so the
 * storefront never asks for a path that 404s.
 *
 * Rich text is included, but only its href attributes, and only where the whole
 * attribute value is a path we own. The Terms of Service page shipped by
 * KarmaaLegalPagesSeeder links to /returns in its own body copy, so leaving HTML
 * alone would have meant knowingly publishing a dead link on a live legal page.
 * Matching the complete quoted value rather than searching for the substring is
 * what keeps this safe: prose that mentions the word is untouched, and a longer
 * path that merely starts the same way ('/returns-policy') cannot be mangled.
 */
return new class extends Migration
{
    /**
     * Legacy path => canonical path. Order matters: the prefix rules are tried
     * in order, so '/products/' must be tested before the exact '/products'.
     */
    private const EXACT = [
        '/products' => '/shop',
        '/returns' => '/returns-policy',
    ];

    private const PREFIXES = [
        '/products/' => '/product/',
        '/categories/' => '/category/',
        '/orders/' => '/account/orders/',
    ];

    /**
     * table => columns holding a single URL.
     */
    private const LINK_COLUMNS = [
        'banners' => ['link'],
        'homepage_sections' => ['button_link'],
        'navigation_menus' => ['url'],
    ];

    /**
     * table => JSON columns whose strings may hold a URL. Walked key by key
     * rather than string-replaced, so a path that appears inside prose is not
     * touched and the JSON cannot be corrupted by a partial match.
     */
    private const JSON_COLUMNS = [
        'homepage_sections' => ['content'],
    ];

    /**
     * table => rich-text columns whose href attributes may name a legacy path.
     */
    private const HTML_COLUMNS = [
        'pages' => ['content'],
        'blog_posts' => ['content'],
    ];

    public function up(): void
    {
        foreach (self::LINK_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereNotNull($column)
                    ->orderBy('id')
                    ->chunkById(200, function ($rows) use ($table, $column) {
                        foreach ($rows as $row) {
                            $rewritten = $this->rewrite($row->{$column});

                            if ($rewritten !== $row->{$column}) {
                                DB::table($table)->where('id', $row->id)->update([$column => $rewritten]);
                            }
                        }
                    });
            }
        }

        foreach (self::JSON_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereNotNull($column)
                    ->orderBy('id')
                    ->chunkById(200, function ($rows) use ($table, $column) {
                        foreach ($rows as $row) {
                            $decoded = json_decode((string) $row->{$column}, true);

                            // Not JSON, or JSON we cannot re-encode faithfully:
                            // leave it alone rather than risk writing back
                            // something the app can no longer read.
                            if (! is_array($decoded)) {
                                continue;
                            }

                            $walked = $this->walk($decoded);

                            if ($walked === $decoded) {
                                continue;
                            }

                            DB::table($table)->where('id', $row->id)->update([
                                $column => json_encode($walked),
                            ]);
                        }
                    });
            }
        }

        foreach (self::HTML_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereNotNull($column)
                    ->orderBy('id')
                    ->chunkById(100, function ($rows) use ($table, $column) {
                        foreach ($rows as $row) {
                            $rewritten = $this->rewriteHrefs((string) $row->{$column});

                            if ($rewritten !== $row->{$column}) {
                                DB::table($table)->where('id', $row->id)->update([$column => $rewritten]);
                            }
                        }
                    });
            }
        }
    }

    /**
     * Deliberately empty.
     *
     * The reverse of this is not "put the old paths back" - the old paths do not
     * resolve any more, so restoring them would hand the storefront a set of
     * links that 404. /shop, /category/... and /account/orders/... are correct
     * whether or not the redirects exist, so there is nothing to undo.
     */
    public function down(): void
    {
        //
    }

    /**
     * Rewrites one stored link. Only the path is considered, so a query string
     * ('/products?category=kurtas') and an absolute URL on this site both
     * survive with their tail intact. Anything pointing off-site, or at a path
     * with no legacy meaning, is returned untouched.
     */
    private function rewrite(?string $link): ?string
    {
        if ($link === null || trim($link) === '') {
            return $link;
        }

        $link = trim($link);

        // Split off an absolute prefix so 'https://site/products' is treated the
        // same as '/products'. An off-site host is left alone: its /products is
        // not ours to rewrite.
        $prefix = '';
        $path = $link;

        if (preg_match('#^(https?://[^/]+)(/.*)$#i', $link, $m)) {
            $host = parse_url($m[1], PHP_URL_HOST);
            $ours = parse_url((string) config('app.url'), PHP_URL_HOST);

            if (! $ours || strcasecmp((string) $host, (string) $ours) !== 0) {
                return $link;
            }

            $prefix = $m[1];
            $path = $m[2];
        } elseif (! str_starts_with($path, '/')) {
            return $link;
        }

        // Keep ?query and #fragment out of the matching, and back on afterwards.
        $tail = '';
        if (($cut = strcspn($path, '?#')) < strlen($path)) {
            $tail = substr($path, $cut);
            $path = substr($path, 0, $cut);
        }

        foreach (self::PREFIXES as $from => $to) {
            if (str_starts_with($path, $from) && strlen($path) > strlen($from)) {
                return $prefix.$to.substr($path, strlen($from)).$tail;
            }
        }

        // Trailing slash included: '/products/' means the same page as
        // '/products' to anyone typing it.
        $bare = rtrim($path, '/');

        if ($bare !== '' && isset(self::EXACT[$bare])) {
            return $prefix.self::EXACT[$bare].$tail;
        }

        return $link;
    }

    /**
     * Rewrites the href attributes of stored HTML and nothing else.
     *
     * The pattern captures the complete quoted attribute value and hands the
     * whole thing to rewrite(), so a match is always a match on the entire link:
     * href="/returns-policy" cannot be seen as href="/returns" plus some
     * leftovers, and the word "products" in a sentence is never a candidate.
     * Text, markup, attribute order and quoting style all come back untouched.
     */
    private function rewriteHrefs(string $html): string
    {
        return preg_replace_callback(
            '#(\bhref\s*=\s*)(["\'])(.*?)\2#i',
            function (array $m): string {
                $rewritten = $this->rewrite(html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5));

                // Unchanged links keep their original bytes, entities and all.
                // Only a link we actually repointed is re-encoded.
                if ($rewritten === null || $rewritten === html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5)) {
                    return $m[0];
                }

                return $m[1].$m[2].htmlspecialchars($rewritten, ENT_QUOTES | ENT_HTML5).$m[2];
            },
            $html
        ) ?? $html;
    }

    /**
     * Rewrites every string in a decoded JSON structure that looks like a link
     * we own. Values that are not paths come back unchanged, because rewrite()
     * only acts on a leading '/' or an absolute URL on this host.
     */
    private function walk(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->walk($item);
            } elseif (is_string($item)) {
                $value[$key] = $this->rewrite($item);
            }
        }

        return $value;
    }
};
