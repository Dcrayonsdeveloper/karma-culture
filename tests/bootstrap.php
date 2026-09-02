<?php

/**
 * Give every concurrent test run its own MySQL schema.
 *
 * Several Claude sessions work this repo at once, and `phpunit.xml` used to
 * pin every one of them to the single `dcommerce_test` database. Because the
 * suite uses RefreshDatabase, two runs overlapping meant one `migrate:fresh`
 * dropping tables the other was mid-assertion against — results became a coin
 * flip, and on 2026-09-02 it took MariaDB down with a core dump while it was
 * trying to unlink `dcommerce_test/migrations.ibd` under another connection.
 *
 * So the database name is decided here instead of in the config: a run that
 * does not name one explicitly gets `dcommerce_test_p<pid>`, created on the
 * way in and dropped on the way out. Setting DB_DATABASE yourself still wins
 * and is left completely alone — including the schema, which is never dropped.
 */

require __DIR__.'/../vendor/autoload.php';

const SHARED_TEST_DB = 'dcommerce_test';

/** Read a key out of an env file without booting Laravel (it isn't up yet). */
$fromEnvFile = static function (string $key) {
    static $parsed = null;

    if ($parsed === null) {
        $parsed = [];
        foreach (['/../.env.testing', '/../.env'] as $candidate) {
            $path = __DIR__.$candidate;
            if (! is_readable($path)) {
                continue;
            }
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                // First file wins: .env.testing is more specific than .env.
                $parsed[$k] ??= trim(trim(trim($v), '"'), "'");
            }
        }
    }

    return $parsed[$key] ?? null;
};

$setting = static function (string $key, ?string $default) use ($fromEnvFile) {
    $value = getenv($key);

    return ($value === false || $value === '') ? ($fromEnvFile($key) ?? $default) : $value;
};

$requested = getenv('DB_DATABASE');
$requested = ($requested === false || $requested === '') ? null : $requested;

// An explicit, non-shared name means the caller has already isolated itself.
if ($requested !== null && $requested !== SHARED_TEST_DB) {
    return;
}

$database = SHARED_TEST_DB.'_p'.getmypid();
$host     = $setting('DB_HOST', '127.0.0.1');
$port     = $setting('DB_PORT', '3306');
$user     = $setting('DB_USERNAME', 'root');
$password = $setting('DB_PASSWORD', '') ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // No reachable server, or no rights to create. Leave DB_DATABASE as it was
    // and let the suite fail with its own connection error, which says more
    // about the cause than anything thrown from here would.
    fwrite(STDERR, "[bootstrap] could not provision {$database}: {$e->getMessage()}\n");

    return;
}

putenv("DB_DATABASE={$database}");
$_ENV['DB_DATABASE'] = $database;
$_SERVER['DB_DATABASE'] = $database;

// Drop it again so a machine running the suite all day does not accumulate a
// schema per run. A hard crash skips this; the next run for that pid reuses
// the leftover, and `migrate:fresh` makes it current anyway.
register_shutdown_function(static function () use ($host, $port, $user, $password, $database) {
    try {
        (new PDO("mysql:host={$host};port={$port}", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]))->exec("DROP DATABASE IF EXISTS `{$database}`");
    } catch (PDOException) {
        // Best effort: a leftover schema is harmless, a fatal here is not.
    }
});
