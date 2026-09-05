<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function getValueAttribute($value)
    {
        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = match ($this->type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    protected static function booted(): void
    {
        // Settings are cached twice: once per key and once per group. Only the
        // key was ever cleared, so an admin save could sit behind a stale group
        // cache for an hour and look like it had not been applied.
        $forget = function (self $setting) {
            Cache::forget("setting.{$setting->key}");
            unset(static::$memo["setting.{$setting->key}"]);

            // The whole-table map every read goes through. Every write path in
            // the app - Setting::set(), and the admin screens' updateOrCreate -
            // goes through the model, so clearing it here covers all of them.
            Cache::forget(self::ALL_KEY);
            unset(static::$memo[self::ALL_KEY]);

            if ($setting->group) {
                Cache::forget("settings.group.{$setting->group}");
            }

            foreach (array_unique([$setting->group, $setting->getOriginal('group')]) as $group) {
                if ($group) {
                    Cache::forget("settings.group.{$group}");
                    unset(static::$memo["settings.group.{$group}"]);
                }
            }

            // Values derived from settings rather than stored as one. Nothing
            // cleared `currency_config`, so changing the currency symbol or its
            // position under Settings sat behind the hour-long cache and read as
            // "the setting does nothing".
            foreach (static::DERIVED_KEYS as $derived) {
                Cache::forget($derived);
                unset(static::$memo["derived.{$derived}"]);
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }
    /** Marks "no row in the database" so a missing setting is still cached. */
    private const MISSING = '__kk_setting_missing__';

    /** Cache keys holding values computed from settings, cleared on any save. */
    private const DERIVED_KEYS = ['currency_config'];

    /** Cache key for the whole settings table, read as one row set. */
    private const ALL_KEY = 'settings.all';

    /**
     * Values already read during THIS request.
     *
     * Cache::remember() is not free: on shared hosting the cache store is the
     * database, so every call is a round trip to the `cache` table. A storefront
     * page reads the same handful of settings once per rendered price, per date
     * and per partial - a product listing was issuing over 200 `select * from
     * cache` queries to answer three distinct questions.
     *
     * This memo lives for one request only, so an admin save is still visible on
     * the very next one; the writers above clear it so a save is visible even
     * within the request that made it.
     *
     * @var array<string, mixed>
     */
    private static array $memo = [];

    /** Drop the per-request memo. Tests that write settings directly need this. */
    public static function flushMemo(): void
    {
        static::$memo = [];
    }

    /**
     * Cache::remember() with the same per-request memo in front of it, for
     * settings-derived values that are not a single setting row - currency
     * formatting being the one that gets read on every price.
     */
    public static function remembered(string $key, callable $resolve, int $ttl = 3600): mixed
    {
        return static::$memo["derived.{$key}"] ??= Cache::remember($key, $ttl, $resolve);
    }

    /**
     * The default must not be cached.
     *
     * Caching it meant the first caller for a missing key fixed the value for
     * an hour: a caller passing no default cached null, and every later caller
     * asking for the same key got that null back instead of its own default.
     * A blank stored value counts as unset for the same reason - an admin field
     * left empty should fall back, not silently become zero.
     */
    public static function get(string $key, $default = null)
    {
        $value = static::all_()[$key] ?? self::MISSING;

        if ($value === self::MISSING || $value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * Every setting, keyed by key, read in ONE query.
     *
     * The layout alone asks for 43 distinct settings - site name, logo, socials,
     * the two popups, the analytics ids, the announcement bar. One cache round
     * trip each meant 43 `select * from cache` queries before the page had
     * rendered anything, and on shared hosting the cache store IS the database.
     * The table is a settings table - 52 rows - so fetching all of it once is
     * cheaper than fetching three of them individually.
     *
     * Values come off the model, not off a pluck(), so the integer/boolean/json
     * casts in getValueAttribute() still apply.
     *
     * @return array<string, mixed>
     */
    private static function all_(): array
    {
        return static::$memo[self::ALL_KEY] ??= Cache::remember(self::ALL_KEY, 3600, function () {
            $map = [];

            foreach (static::query()->get() as $setting) {
                $map[$setting->key] = $setting->value ?? self::MISSING;
            }

            return $map;
        });
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting.{$key}");
        unset(static::$memo["setting.{$key}"]);

        return $setting;
    }

    /**
     * Read a setting as a boolean regardless of how the row is typed.
     *
     * Rows seeded with type 'boolean' come back from getValueAttribute() as a
     * real bool, while rows written by the settings screens come back as the
     * strings '1'/'0'. Call sites comparing with === '1' therefore silently
     * evaluated false for any seeded row - which is how Cash on Delivery
     * disappeared from checkout as soon as PayU was configured.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getGroup(string $group): array
    {
        return static::$memo["settings.group.{$group}"] ??= Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }
}
