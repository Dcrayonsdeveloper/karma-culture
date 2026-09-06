<?php

namespace App\Models;

use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Eloquent\Model;

/**
 * One entry in the colour PICKER - not a colour a product has.
 *
 * Nothing in the schema points at colours.id. A product keeps its own copy of
 * every colour it comes in inside products.attributes -> "Colours", and a cart
 * line and an order line keep theirs in cart_items.colour / order_items.colour.
 * This row exists so the admin picks Ivory from a list with a swatch on it
 * instead of typing "ivory" into one product and "Ivory " into the next, which
 * is how the shade rail ended up needing to normalise spellings at read time in
 * the first place.
 *
 * So: renaming Maroon to Burgundy here re-labels the picker and touches nothing
 * else. Deleting it takes it out of the picker and leaves every product still
 * offering it, every cart line still holding it, and every past invoice still
 * saying what was actually sold. That is correct - an order is a record, not a
 * live join - and it is the reason this table can be edited freely.
 *
 * Two ways to show one, in priority order: hex_code paints the swatch, and
 * image_url is the escape hatch for a colour a flat block lies about - a print,
 * a two-tone weave, anything with a pattern. {@see getSwatchAttribute()} always
 * returns something paintable so a half-filled row never renders as a hole.
 *
 * The storefront's shade rail is still derived from the live catalogue by
 * {@see ShopFilterCatalogue}; this table does not feed it and must not start
 * to, or the picker and the rail become two sources of truth that drift apart
 * the first time somebody adds a colour to a product without adding it here.
 */
class Colour extends Model
{
    protected $table = 'colours';

    protected $fillable = [
        'name',
        'key',
        'hex_code',
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
        // the uniqueness that matters is "one entry per colour", not "one entry
        // per spelling" - a form that let "black " through as a second row
        // would put two identical swatches in every product's colour picker.
        // Same normalisation the shade rail groups by, so the curated list and
        // the derived rail agree on what counts as one colour.
        static::saving(function (self $model): void {
            $model->key = ShopFilterCatalogue::normaliseKey($model->name);
        });

        // The storefront rails - and the admin screens that read the same
        // cached arrays for usage counts - are cached for six hours against a
        // shared version counter. Without this bump, correcting a hex here is
        // invisible everywhere the swatch is drawn until the cache ages out,
        // which reads as "the edit did not save".
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
     * Public URL for the colour's fabric photo, or null when it has none -
     * which is the normal case, because the hex is usually the whole story.
     * image_url holds a storage-relative path (admin upload), but may also be
     * a full URL or an absolute path - resolve all three like Quality does.
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

    /**
     * Something paintable, always.
     *
     * hex_code is nullable - an admin can add "Multicolour" and mean to upload
     * a photo later - and a swatch bound straight to a null hex renders as a
     * transparent hole that looks like a broken image rather than a missing
     * one. The fallback is a neutral grey: obviously a placeholder, and it
     * never pretends to be a real colour a shopper might order.
     */
    public function getSwatchAttribute(): string
    {
        return $this->hex_code ?: '#d4d4d4';
    }
}
