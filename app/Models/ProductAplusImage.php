<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAplusImage extends Model
{
    /**
     * Accepted display size: a CSS length with an optional unit, or "auto".
     * A bare number is treated as px. Anything else is rejected - these values
     * are written into a style attribute, so nothing unvalidated may pass.
     */
    public const DISPLAY_SIZE_REGEX = '/^(auto|\d{1,5}(\.\d{1,2})?(px|%|rem|em|vw|vh)?)$/i';

    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'width',
        'height',
        'display_width',
        'display_height',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Normalise a stored display size into a CSS length.
     *
     * Returns null when empty or malformed so callers fall back to the
     * responsive default. A bare number gains a px unit ("600" -> "600px").
     * "auto" survives normalisation but is dropped by getDisplayStyleAttribute.
     */
    public static function normaliseDisplaySize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match(self::DISPLAY_SIZE_REGEX, $value)) {
            return null;
        }

        $value = strtolower($value);

        return is_numeric($value) ? $value.'px' : $value;
    }

    public function getDisplayWidthCssAttribute(): ?string
    {
        return self::normaliseDisplaySize($this->display_width);
    }

    public function getDisplayHeightCssAttribute(): ?string
    {
        return self::normaliseDisplaySize($this->display_height);
    }

    /**
     * Per-image CSS custom properties, consumed by .kk-aplus__img.
     *
     * Custom properties rather than direct width/height declarations so a
     * media query can still override the value on small screens - an inline
     * `width:900px` would win against any stylesheet rule and break mobile.
     * Empty string when unset, leaving the stylesheet defaults in force.
     *
     * "auto" emits nothing. The stylesheet caps both axes with
     * min(var(--kk-aplus-h), 78vh) to keep a banner on screen, and min() takes
     * lengths only: min(auto, 78vh) is invalid, so the browser would throw the
     * whole declaration away and the fit-on-screen guarantee with it. Dropping
     * the property is exactly equivalent anyway - "auto" means "use the
     * stylesheet default", which is what an absent property falls back to.
     */
    public function getDisplayStyleAttribute(): string
    {
        $style = '';

        if (($width = $this->display_width_css) && $width !== 'auto') {
            $style .= '--kk-aplus-w:'.$width.';';
        }

        // A percentage height is dropped along with "auto". The slide takes its
        // height from the banner inside it, so a percentage resolves against a
        // container the banner itself defines - the circular case that made a
        // "70%" banner lay out at its natural height and stretch the frame in
        // the first place. It is also not safe inside min(): a percentage
        // against an indefinite height is under-specified, and an engine that
        // resolves it to zero would collapse the banner instead of capping it.
        if (($height = $this->display_height_css) && $height !== 'auto' && ! str_ends_with($height, '%')) {
            $style .= '--kk-aplus-h:'.$height.';';
        }

        return $style;
    }

    /** Browser-usable URL (handles /storage, absolute URL, or a bare storage path). */
    public function getImageUrlAttribute(): string
    {
        $path = $this->image_path;
        if (! $path) {
            return '';
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // The admin controller records uploads as "/storage/products/<hash>.jpg"
        // while older rows hold a bare relative path. Both are files we serve
        // ourselves, so both are fingerprinted; returning the rooted form raw
        // was skipping the cache-bust on every product image on the site.
        if (str_starts_with($path, '/')) {
            return asset_v(ltrim($path, '/'));
        }

        return asset_v('storage/'.$path);
    }
}
