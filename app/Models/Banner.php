<?php

namespace App\Models;

use App\Support\BannerMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use SoftDeletes;

    const OVERLAY_STYLES = [
        'none' => 'No Overlay',
        'left-dark' => 'Dark from Left',
        'right-dark' => 'Dark from Right',
        'full-dark' => 'Full Dark',
        'center-vignette' => 'Center Vignette',
        'purple-gradient' => 'Purple Gradient',
    ];

    /**
     * A link an admin may point a banner at.
     *
     * Every one of these values ends up in an `href` on the storefront. Blade
     * escapes the text, but escaping does not disarm a scheme: `javascript:...`
     * in a stored link is a click away from running as the visitor. So the
     * scheme is allow-listed - http, https, mailto and tel - alongside the two
     * shapes that carry no scheme at all: a site-relative path and a bare
     * fragment.
     *
     * The path branch refuses a second leading slash. `//evil.com` looks like a
     * path and passed as one, but a browser reads it as a protocol-relative URL
     * and resolves it off-site, so a banner labelled with a local path could
     * quietly send visitors to someone else's domain.
     *
     * It lives on the model because both banner screens need it and they had
     * disagreed: Marketing > Banners used V::url(), which demands a full
     * http(s) address, so `/products` was accepted by Homepage > Hero Banners
     * and refused here for the same table.
     *
     * Admin\HomepageController still carries its own private copy of this
     * literal; the two are identical and that one should be deleted in favour
     * of this const the next time that file is opened.
     */
    const LINK_REGEX = '/^(?:(?:https?|mailto|tel):\S+|\/(?!\/)\S*|#\S*)$/i';

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
        'alt_text',
        'overlay_style',
        'priority',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            // Without these the window comparisons compare strings.
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * The one definition of "a shopper should be seeing this right now".
     *
     * Switched on, started, not yet finished. Every surface that shows banners
     * reads it - the home page, the API, and the admin's own Live badge - so a
     * banner cannot be live in one place and not another, which is the failure
     * that had scheduling removed from this table once already.
     *
     * An open-ended window is the ordinary case: both columns null means "from
     * now until further notice", which is what every banner created before
     * scheduling returned already says.
     */
    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
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

    /** Whether the switch is on. Says nothing about the window - see {@see state}. */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * What a shopper sees right now, in one word.
     *
     * `live` is the only state that reaches the storefront. The other three are
     * the reasons a banner does not, and the admin screens print them beside
     * the switch: without that, "Active" and "not on the site" were both true
     * at once and nothing on the page reconciled them.
     */
    public function getStateAttribute(): string
    {
        if (! $this->is_active) {
            return 'hidden';
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return 'scheduled';
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return 'expired';
        }

        return 'live';
    }

    /** The state as a sentence, for the admin list and the edit form. */
    public function getStateLabelAttribute(): string
    {
        return match ($this->state) {
            'scheduled' => 'Scheduled for '.$this->starts_at->format('j M Y, H:i'),
            'expired' => 'Ended '.$this->ends_at->format('j M Y, H:i'),
            'hidden' => 'Hidden',
            default => 'Live',
        };
    }

    public function getIsVisibleAttribute(): bool
    {
        return $this->state === 'live';
    }

    /**
     * The text a screen reader is given for the artwork.
     *
     * Falls back to the title, which is exactly what the storefront printed
     * before there was a column for it, so nothing an admin never filled in
     * suddenly loses its description. An empty string is respected and means
     * "decorative": the storefront then also marks the image aria-hidden.
     */
    public function getAltAttribute(): string
    {
        return $this->alt_text !== null
            ? trim($this->alt_text)
            : (string) ($this->title ?: $this->name);
    }

    /** A banner with no artwork at all - the storefront must not draw a frame for it. */
    public function getHasMediaAttribute(): bool
    {
        return (bool) ($this->image_url || $this->video_url || $this->mobile_image_url || $this->mobile_video_url);
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

    /**
     * Whether the hero should draw its phone breakpoint at HERO_MOBILE_SIZE.
     *
     * frameFor('mobile') falls back to the desktop file for a banner that has
     * no phone artwork of its own, and almost none do - so a 3:2 phone box was
     * usually framing a 3.85:1 strip and cropping 61% of its width away. The
     * box has to follow the file that will actually be drawn.
     *
     * It is one answer for the whole hero rather than one per slide, because a
     * carousel whose slides each sized themselves is what made it lurch as it
     * advanced. So the narrow box is taken only when EVERY visible banner has
     * phone artwork to fill it; one slide without any keeps them all on the
     * wide box, where each is drawn at its own file's proportions.
     *
     * The same answer must also decide whether phone artwork is SERVED at all:
     * a banner that offered its own 3:2 still through <picture> while the hero
     * had settled on the wide box would have that still cropped instead. The
     * home page reads this for both, which is what keeps them agreeing.
     */
    public static function heroUsesPhoneBox(iterable $banners): bool
    {
        $banners = collect($banners);

        return $banners->isNotEmpty()
            && $banners->every(fn (self $banner) => $banner->has_mobile_media);
    }

    /**
     * The shape the phone box is drawn at for a given hero.
     *
     * The admin screens print both constants as the sizes to upload; this is
     * the one that phones actually get, which is not always the same thing.
     */
    public static function heroPhoneBox(iterable $banners): array
    {
        return static::heroUsesPhoneBox($banners) ? self::HERO_MOBILE_SIZE : self::HERO_DESKTOP_SIZE;
    }

    /**
     * What one hero slide draws on each screen, given the hero it belongs to.
     *
     * frameFor() answers for a banner in isolation and is still the only place
     * the fallback chain lives. This adds the one thing a slide cannot know by
     * itself: a carousel draws every slide in ONE box, so when that box is the
     * desktop shape - because some other banner has no phone artwork to fill a
     * narrow one - reaching for this banner's own phone still would only get it
     * cropped. The desktop file is then the right answer for both screens.
     *
     * It lives here, beside frameFor(), because the home page and the API both
     * have to reach the same conclusion. The website deciding this inline was
     * exactly the drift frameFor()'s own docblock promises cannot happen.
     *
     * @return array{desktop: ?array, mobile: ?array}
     */
    public function heroFrames(bool $usesPhoneBox): array
    {
        $desktop = $this->frameFor('desktop');

        return [
            'desktop' => $desktop,
            'mobile' => $usesPhoneBox ? $this->frameFor('mobile') : $desktop,
        ];
    }

    /**
     * What one breakpoint should actually draw, after the fallbacks.
     *
     * The two screens each have an order of preference, and they are not the
     * same one. A desktop would rather show a phone still than nothing, but it
     * will never reach for the phone CLIP - a portrait video letterboxed into a
     * 3.85:1 strip is worse than the still it replaced. A phone, having neither
     * of its own, takes whatever the desktop has, in the desktop's own order.
     *
     *   desktop: desktop video -> desktop image -> mobile image
     *   mobile:  mobile video  -> mobile image  -> desktop video -> desktop image
     *
     * Returns null when the banner carries nothing at all, which is the signal
     * for the storefront to draw no frame rather than an empty box.
     *
     * One method, read by the home page and by the API, so the app and the
     * website cannot disagree about which file a phone is supposed to get.
     *
     * @return array{kind: string, src: string, poster: ?string, webp: ?string}|null
     */
    public function frameFor(string $device): ?array
    {
        $video = fn (?string $path, ?string $poster) => $path
            ? ['kind' => 'video', 'src' => $this->mediaUrl($path), 'poster' => $poster, 'webp' => null]
            : null;

        $image = fn (?string $path) => $path
            ? [
                'kind' => 'image',
                'src' => $this->mediaUrl($path),
                'poster' => null,
                'webp' => ($key = BannerMedia::webpFor($path)) ? $this->mediaUrl($key) : null,
            ]
            : null;

        // A clip's poster is the still for the same screen where there is one,
        // so the first painted frame is artwork rather than a black rectangle.
        $desktopPoster = $this->image_url ? $this->mediaUrl($this->image_url) : null;
        $mobilePoster = $this->mobile_image_url ? $this->mediaUrl($this->mobile_image_url) : $desktopPoster;

        $chain = $device === 'mobile'
            ? [
                fn () => $video($this->mobile_video_url, $mobilePoster),
                fn () => $image($this->mobile_image_url),
                fn () => $video($this->video_url, $desktopPoster),
                fn () => $image($this->image_url),
            ]
            : [
                fn () => $video($this->video_url, $desktopPoster),
                fn () => $image($this->image_url),
                fn () => $image($this->mobile_image_url),
            ];

        foreach ($chain as $step) {
            if ($frame = $step()) {
                return $frame;
            }
        }

        return null;
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
