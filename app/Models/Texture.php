<?php

namespace App\Models;

use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Eloquent\Model;

/**
 * One entry in the texture PICKER - not a texture a product has.
 *
 * Nothing points at textures.id. A product carries its own copy of every
 * texture it comes in inside products.attributes -> "Textures", and cart_items
 * .texture / order_items.texture carry theirs. The row here is the entry in the
 * list the admin picks from, so "Matte", "matte" and " Matte " stop becoming
 * three chips on the shop's texture rail.
 *
 * Which means renaming or deleting one changes the picker and nothing else:
 * products keep their textures, carts keep their lines, and a past order still
 * says what was actually shipped. Deliberate - an order is a record of a sale,
 * not a live join into a lookup table somebody may tidy up next spring.
 *
 * Unlike a colour there is no hex here, because a texture is a surface rather
 * than a colour: Matte and Glossy in the same grey are the same hex and two
 * completely different fabrics. The tile in image_url is therefore not
 * decoration, it is the value - which is why every stock texture ships with one
 * under /images/textures/ instead of leaving it for the admin to supply.
 *
 * The storefront's texture rail is still derived from the live catalogue by
 * {@see ShopFilterCatalogue}; this table does not feed it and must not start
 * to, or the curated list and the derived rail drift apart.
 */
class Texture extends Model
{
    protected $table = 'textures';

    protected $fillable = [
        'name',
        'key',
        'image_url',
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
        // the uniqueness that matters is "one entry per texture", not "one
        // entry per spelling" - a form that let "matte " through as a second
        // row would put two identical tiles in every product's texture picker.
        // Same normalisation the texture rail groups by.
        static::saving(function (self $model): void {
            $model->key = ShopFilterCatalogue::normaliseKey($model->name);
        });

        // The storefront rails - and the admin screens reading the same cached
        // arrays for usage counts - are cached for six hours against a shared
        // version counter. Skip this bump and swapping a texture's tile is
        // invisible wherever it is drawn until the cache ages out, which reads
        // to an admin as an edit that did not save.
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

    /**
     * Public URL for the texture tile, or null when it has none.
     * image_url holds a storage-relative path (admin upload), but the seeded
     * rows carry an absolute path under /images/textures/ and an admin may
     * paste a full URL - resolve all three like Quality does.
     */
    public function getImageSrcAttribute(): ?string
    {
        if (! $this->image_url) {
            return null;
        }
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }
        if (str_starts_with($this->image_url, '/')) {
            return asset_v(ltrim($this->image_url, '/'));
        }

        return asset_v('storage/'.$this->image_url);
    }
}
