<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One filter value an admin has taken off the storefront.
 *
 * The Shop Filters screen no longer holds a list of filter values - the
 * catalogue is the source of truth for WHICH values exist ({@see
 * \App\Support\ShopFilterCatalogue}) and this table is the source of truth for
 * WHETHER one is shown. Keeping the two apart is the whole point: hiding Pink
 * must not take Pink off the products that are pink, and it has to stay hidden
 * when the next pink product arrives.
 */
class ShopFilterExclusion extends Model
{
    /** The filter rails an exclusion can belong to. `shade` is the colour rail. */
    public const TYPES = ['size', 'shade', 'texture', 'price'];

    protected $fillable = ['uuid', 'type', 'value_key', 'label'];

    protected static function booted(): void
    {
        static::creating(function (self $exclusion): void {
            if (empty($exclusion->uuid)) {
                // v7 rather than v4: the id sorts by creation time, so the
                // admin list reads in the order values were hidden without a
                // second column to sort on.
                $exclusion->uuid = (string) Str::uuid7();
            }
        });

        // The derived filter lists are cached against this counter, so hiding
        // or restoring a value has to retire them or the storefront keeps
        // offering what the admin has just taken away.
        static::saved(fn () => ProductVariant::bumpFilterCache());
        static::deleted(fn () => ProductVariant::bumpFilterCache());
    }

    /** Bound on the uuid: an exclusion is never addressed by its row id. */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
