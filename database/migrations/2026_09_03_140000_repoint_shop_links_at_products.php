<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves stored links from /shop to /products, following the page itself.
 *
 * The all-products page is served at /products now. It was at /shop, and one
 * migration ago the stored links that still said /products were rewritten to
 * /shop to follow it there - so this is that same repointing, in the direction
 * the page actually went. The earlier migration is left alone rather than
 * edited: it has already run on production, so editing it would change nothing
 * there and only make the history lie about what happened.
 *
 * Same surfaces and the same care as before. Banner targets, homepage section
 * buttons and footer/nav menu entries are single-URL columns; section content
 * is JSON walked key by key; page and blog copy is rewritten only inside href
 * attributes, matched as a complete quoted value. Nothing that merely mentions
 * the word "shop" in prose is touched, and a longer path that only begins the
 * same way cannot be mangled.
 */
return new class extends Migration
{
    private const EXACT = [
        '/shop' => '/products',
    ];

    private const PREFIXES = [
        '/shop/' => '/products/',
    ];

    private const LINK_COLUMNS = [
        'banners' => ['link'],
        'homepage_sections' => ['button_link'],
        'navigation_menus' => ['url'],
    ];

    private const JSON_COLUMNS = [
        'homepage_sections' => ['content'],
    ];

    private const HTML_COLUMNS = [
        'pages' => ['content'],
        'blog_posts' => ['content'],
    ];

    public function up(): void
    {
        foreach (self::LINK_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $this->eachRow($table, $column, function ($value) {
                    return $this->rewrite($value);
                });
            }
        }

        foreach (self::JSON_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $this->eachRow($table, $column, function ($value) {
                    $decoded = json_decode((string) $value, true);

                    // Not JSON, or JSON we cannot re-encode faithfully: leave it
                    // rather than write back something the app cannot read.
                    if (! is_array($decoded)) {
                        return $value;
                    }

                    $walked = $this->walk($decoded);

                    return $walked === $decoded ? $value : json_encode($walked);
                });
            }
        }

        foreach (self::HTML_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $this->eachRow($table, $column, function ($value) {
                    return $this->rewriteHrefs((string) $value);
                });
            }
        }
    }

    /**
     * Deliberately empty. /products is where the page is; sending these links
     * back to /shop would point them at a path that 404s.
     */
    public function down(): void
    {
        //
    }

    private function eachRow(string $table, string $column, callable $transform): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $column, $transform) {
                foreach ($rows as $row) {
                    $rewritten = $transform($row->{$column});

                    if ($rewritten !== $row->{$column}) {
                        DB::table($table)->where('id', $row->id)->update([$column => $rewritten]);
                    }
                }
            });
    }

    /**
     * Rewrites one stored link, path only - a query string and an absolute URL
     * on this site both keep their tail. Anything off-site is left alone.
     */
    private function rewrite(?string $link): ?string
    {
        if ($link === null || trim($link) === '') {
            return $link;
        }

        $link = trim($link);

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

        $bare = rtrim($path, '/');

        if ($bare !== '' && isset(self::EXACT[$bare])) {
            return $prefix.self::EXACT[$bare].$tail;
        }

        return $link;
    }

    private function rewriteHrefs(string $html): string
    {
        return preg_replace_callback(
            '#(\bhref\s*=\s*)(["\'])(.*?)\2#i',
            function (array $m): string {
                $decoded = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5);
                $rewritten = $this->rewrite($decoded);

                if ($rewritten === null || $rewritten === $decoded) {
                    return $m[0];
                }

                return $m[1].$m[2].htmlspecialchars($rewritten, ENT_QUOTES | ENT_HTML5).$m[2];
            },
            $html
        ) ?? $html;
    }

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
