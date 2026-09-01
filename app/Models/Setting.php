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

    /** Marks "no row in the database" so a missing setting is still cached. */
    private const MISSING = '__kk_setting_missing__';

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
        $value = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            $setting = static::where('key', $key)->first();

            return $setting ? ($setting->value ?? self::MISSING) : self::MISSING;
        });

        if ($value === self::MISSING || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting.{$key}");

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }
}
