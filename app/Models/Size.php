<?php

namespace App\Models;

use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Eloquent\Model;

/**
 * One entry in the size PICKER - not a size a product has.
 *
 * Read this before wiring a relationship to it, because the obvious one is
 * wrong. There is no sizes.id anywhere else in the schema: a variant stores the
 * label ("M") in product_variants.name, and a cart line and an order line store
 * their own copies in cart_items.size / order_items.size. This row exists only
 * so the admin picks "M" from a list instead of typing it, which is what stops
 * "M", "m" and " M " turning into three chips on the shop's size rail.
 *
 * The consequence, and it is the point rather than a limitation: deleting or
 * renaming a row here changes nothing that has already been saved. Products
 * keep their sizes, carts keep their lines, and last year's invoice still says
 * what was actually shipped. Retiring a size takes it out of the picker; it
 * does not rewrite history, and nothing on this screen ever should.
 *
 * The shop's rails are still derived from the catalogue by
 * {@see ShopFilterCatalogue} - this table does not feed them and must not start
 * to, or the two sources of truth drift the moment an admin forgets to add a
 * row for a size a product already carries.
 */
class Size extends Model
{
    protected $table = 'sizes';

    protected $fillable = [
        'name',
        'key',
        'description',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Derived on every save rather than validated in the request, because
        // the uniqueness that matters is "one entry per size", not "one entry
        // per spelling" - and a form that let " m " through as a second row
        // would put a second, identical option in every product's size picker.
        // Same normalisation the shop rails group by, so what the admin curates
        // here and what the storefront collapses out there are the same idea.
        static::saving(function (self $model): void {
            $model->key = ShopFilterCatalogue::normaliseKey($model->name);
        });

        // The storefront's filter rails are cached for six hours against a
        // shared version counter. A size row does not feed those rails today,
        // but the admin screens that DO read them alongside this table
        // (usage counts, the hidden-value list) are served from the same cached
        // arrays - so an edit here that does not bump the counter is an edit
        // nobody sees until the cache ages out. Bumping is cheap; a stale
        // screen costs a support ticket.
        static::saved(fn () => ProductVariant::bumpFilterCache());
        static::deleted(fn () => ProductVariant::bumpFilterCache());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('name');
    }
}
