<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    const OVERLAY_STYLES = [
        'none' => 'No Overlay',
        'left-dark' => 'Dark from Left',
        'right-dark' => 'Dark from Right',
        'full-dark' => 'Full Dark',
        'center-vignette' => 'Center Vignette',
        'purple-gradient' => 'Purple Gradient',
    ];

    /**
     * The shape the desktop hero is drawn at, as [width, height] in pixels.
     *
     * Taken from the hero clip the storefront ships with
     * (public/images/karmaa-kulture-web-banner-v3.mp4, 1426x370). Every slide
     * is drawn in this box now - a video no longer takes its height from its
     * own file, which is what made the carousel lurch between a 370px strip
     * and a 1008px clip as it advanced.
     *
     * The home page reads these into the slide's `aspect-ratio` and the admin
     * screen prints them as the recommended upload size, so the advice and the
     * layout cannot drift apart. Since the box is fixed and the artwork now
     * fills it, these are not merely a suggestion: anything uploaded at other
     * proportions is centre-cropped to fit.
     */
    const HERO_DESKTOP_SIZE = [1426, 370];

    /**
     * The same for phones, as a 3:2 box.
     *
     * It used to be 4:5 portrait, on the reasoning that a banner would carry
     * its own phone artwork. Almost none do, and a 3.85:1 desktop strip
     * cropped into a 4:5 box keeps only a fifth of its width - while the strip
     * ratio itself, at phone widths, is a 101px sliver with no room for the
     * caption drawn over it.
     *
     * 3:2 is the balance point between the two shapes this box actually
     * receives: it keeps 39% of a desktop strip's width, or 53% of a portrait
     * asset's height, and is 260px tall on a 390px phone - enough to read as a
     * header. It is also the native frame of a camera export, so artwork that
     * is photographed rather than composed for the web fits with no crop.
     */
    const HERO_MOBILE_SIZE = [1080, 720];

    protected $fillable = [
        'name',
        'title',
        'subtitle',
        'button_text',
        'position',
        'image_url',
        'mobile_image_url',
        'video_url',
        'mobile_video_url',
        'link',
        'overlay_style',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority');
    }

    // Helper methods
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Turn a stored media path into something a browser can fetch.
     *
     * Three shapes reach these columns and each resolves differently: an
     * absolute URL is already usable, a leading slash means a path under the
     * web root (how the hero clip was imported out of public/images), and
     * anything else is a key on the public disk left by an upload.
     */
    private function mediaUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return asset_v(ltrim($path, '/'));
        }

        return asset_v('storage/'.$path);
    }

    public function getImageAttribute(): string
    {
        return $this->mediaUrl($this->image_url) ?: asset_v('images/placeholder-banner.jpg');
    }

    /**
     * The phone-sized still, falling back to the desktop one.
     *
     * This used to assume the public disk, so a mobile image imported as a
     * web-root path or held on a CDN resolved to /storage/https:/... and 404'd.
     */
    public function getMobileImageAttribute(): string
    {
        return $this->mobile_image_url
            ? $this->mediaUrl($this->mobile_image_url)
            : $this->image;
    }

    /** Browser-usable video URL, or '' when this banner has no video. */
    public function getVideoAttribute(): string
    {
        return $this->mediaUrl($this->video_url);
    }

    /** The phone-sized clip, or '' when this banner has none of its own. */
    public function getMobileVideoAttribute(): string
    {
        return $this->mediaUrl($this->mobile_video_url);
    }

    public function getHasVideoAttribute(): bool
    {
        return (bool) $this->video_url;
    }

    public function getHasMobileVideoAttribute(): bool
    {
        return (bool) $this->mobile_video_url;
    }

    /**
     * Whether this banner carries media of its own for phones.
     *
     * False is the ordinary case and the storefront leans on it: a banner with
     * no override renders one media element with a plain `src`, the way it
     * always has. Only an overriding banner pays for the two-frame markup.
     */
    public function getHasMobileMediaAttribute(): bool
    {
        return (bool) ($this->mobile_image_url || $this->mobile_video_url);
    }

    public function getOverlayCssAttribute(): string
    {
        return match ($this->overlay_style ?? 'left-dark') {
            'none' => '',
            'left-dark' => 'bg-linear-to-r from-black/50 via-black/20 to-transparent',
            'right-dark' => 'bg-linear-to-l from-black/50 via-black/20 to-transparent',
            'full-dark' => 'bg-black/40',
            'center-vignette' => 'bg-radial-[ellipse_at_center] from-transparent via-black/20 to-black/50',
            'purple-gradient' => 'bg-linear-to-r from-purple-900/60 via-purple-800/30 to-transparent',
            default => 'bg-linear-to-r from-black/50 via-black/20 to-transparent',
        };
    }
}
