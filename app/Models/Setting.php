<?php

namespace App\Models;

use App\Exceptions\EnvOnlySettingException;
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

        // A credential must never come back into this table. Every writer used
        // to be a controller decision - drop a field from one form and the next
        // form, a seeder or a console command could still put a live payment
        // salt in the database - so the refusal lives on the model instead,
        // where every write path in the application has to pass through it.
        static::saving(function (self $setting): void {
            if (self::isEnvOnly((string) $setting->key) && (string) $setting->value !== '') {
                throw new EnvOnlySettingException(
                    'The setting ['.$setting->key.'] is a credential and is read from the environment only. '
                    .'Set it in .env on the server; it cannot be stored in the database.'
                );
            }
        });
    }

    /**
     * Settings that are read from the environment and may never be stored.
     *
     * These were all typed into the admin panel once, which meant live payment,
     * courier and AI credentials sat in the `settings` table - readable by
     * anyone with an admin login and present in every database dump. The
     * tracking identifiers are not secret, but they steer third-party scripts
     * on every page, so they are pinned to the deployment too.
     *
     * @var array<int, string>
     */
    public const ENV_ONLY_KEYS = [
        // Payments
        'payu_merchant_key',
        'payu_merchant_salt',
        'payu_mode',
        // Courier
        'shiprocket_api_token',
        'shiprocket_email',
        'shiprocket_password',
        // AI
        'anthropic_api_key',
        'gemini_api_key',
        // Social
        'instagram_access_token',
        // Seeded but never read by any code - listed so they cannot come back.
        'razorpay_webhook_secret',
        'sms_api_key',
        'whatsapp_app_secret',
        'whatsapp_page_access_token',
        'whatsapp_verify_token',
        // Tracking identifiers
        'google_analytics_id',
        'google_tag_manager_id',
        'facebook_pixel_id',
        'google_search_console_verification',
    ];

    /**
     * Key shapes that are refused even when nobody thought to list them.
     *
     * The list above only covers what exists today; this covers the next
     * integration somebody adds. Anchored to the end of the key so ordinary
     * content settings are unaffected - `meta_keywords` is not a key, and
     * `pos_receipt_footer` is not a token.
     */
    private const ENV_ONLY_SUFFIXES = ['_api_key', '_secret', '_password', '_token', '_salt', '_access_key'];

    /** Whether a key is a credential this table refuses to hold. */
    public static function isEnvOnly(string $key): bool
    {
        if (in_array($key, self::ENV_ONLY_KEYS, true)) {
            return true;
        }

        foreach (self::ENV_ONLY_SUFFIXES as $suffix) {
            if (str_ends_with($key, $suffix)) {
                return true;
            }
        }

        return false;
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

    /**
     * Is there a row for this key at all, blank value included?
     *
     * get() cannot answer this: it deliberately folds a blank value into the
     * default it was handed, so a setting an admin has emptied on purpose reads
     * back identically to one that was never written. Callers that fall back to
     * a value from config need the difference - a cleared row is a decision, and
     * an environment default must not quietly undo it.
     *
     * Reads the same whole-table cache every other setting read goes through,
     * so it costs no extra query.
     */
    public static function isSet(string $key): bool
    {
        return array_key_exists($key, static::all_());
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
