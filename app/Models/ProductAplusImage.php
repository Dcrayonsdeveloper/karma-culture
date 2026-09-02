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
     */
    public function getDisplayStyleAttribute(): string
    {
        $style = '';

        if ($width = $this->display_width_css) {
            $style .= '--kk-aplus-w:'.$width.';';
        }

        if ($height = $this->display_height_css) {
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
        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset_v('storage/'.$path);
    }
}
