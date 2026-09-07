<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * A named, percentage-wide sale over a hand-picked set of products.
 *
 * Distinct from {@see FlashSale}, which prices each product individually and
 * for a window. This is one percentage, thrown at a list, live until an admin
 * says otherwise - which is what a Diwali or a Holi sale actually is.
 *
 * The prices themselves are not computed here. They are written into the
 * catalogue by {@see \App\Services\FestivalSaleService} so that every listing,
 * filter, sort and cart line sees them without knowing this class exists; the
 * pivot rows are the receipt that makes it reversible.
 */
class FestivalSale extends Model
{
    /** How long the header may go on believing there is (or is not) a live sale. */
    private const LIVE_CACHE_TTL = 60;

    private const LIVE_CACHE_KEY = 'kk_festival_sale_live';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_percent',
        'banner_path',
        'banner_mobile_path',
        'is_active',
        'show_on_home',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The header asks "is there a live sale?" on every page load, so the
        // answer is cached; saving or deleting a sale is exactly when that
        // answer stops being true.
        static::saved(fn () => self::forgetLive());
        static::deleted(fn () => self::forgetLive());
    }

    public static function forgetLive(): void
    {
        Cache::forget(self::LIVE_CACHE_KEY);
    }

    /**
     * The bare facts about the running sale, or null - id, slug and name.
     *
     * More than one row can be flagged active - the service refuses to let two
     * of them own the same product, not to let only one exist - so "the" live
     * sale is the most recently updated one. That is the one an admin has just
     * switched on, which is the one they expect the header to point at.
     *
     * Cached as an array rather than as a model because the header asks for it
     * on every page load and only ever wants the slug. Miss or hit, that is
     * then zero queries rather than one.
     *
     * @return array{id: int, slug: string, name: string}|null
     */
    public static function liveSummary(): ?array
    {
        $row = Cache::remember(
            self::LIVE_CACHE_KEY,
            self::LIVE_CACHE_TTL,
            fn () => self::query()
                ->where('is_active', true)
                ->latest('updated_at')
                ->first(['id', 'slug', 'name'])
                ?->only(['id', 'slug', 'name']) ?? []
        );

        return $row === [] ? null : $row;
    }

    /** The slug the "Introductory Offer" button points at, or null. */
    public static function liveSlug(): ?string
    {
        return self::liveSummary()['slug'] ?? null;
    }

    /** The running sale as a model, for the pages that need its banner. */
    public static function live(): ?self
    {
        $id = self::liveSummary()['id'] ?? null;

        return $id ? self::find($id) : null;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'festival_sale_products')
            // applied_at belongs in this list as much as the prices do. Without
            // it the column is written but never SELECTed, so every read sees
            // null, "is this product already discounted?" is always no, and a
            // second save takes the percentage off the sale price again -
            // 1000 to 750 to 562.50 - instead of reverting first.
            ->withPivot(['original_price', 'original_mrp', 'sale_price', 'applied_at'])
            ->withTimestamps();
    }

    public function variantPrices(): HasMany
    {
        return $this->hasMany(FestivalSaleVariantPrice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** The multiplier a price is taken through. 25% off -> 0.75. */
    public function multiplier(): float
    {
        return max(0.0, 1 - ((float) $this->discount_percent / 100));
    }

    /** What a given figure becomes under this sale, rounded to paise. */
    public function priceFor(float $base): float
    {
        // Floored at ₹1 rather than ₹0: a 100% sale is almost certainly a typo,
        // and a free product would sail through the payment gateway as a zero
        // total. The same floor products/show.blade.php already uses.
        return max(1.0, round($base * $this->multiplier(), 2));
    }

    public function bannerUrl(): ?string
    {
        return $this->mediaUrl($this->banner_path);
    }

    public function bannerMobileUrl(): ?string
    {
        return $this->mediaUrl($this->banner_mobile_path) ?? $this->bannerUrl();
    }

    /**
     * Same three cases Banner::mediaUrl covers: an absolute URL, a web-root
     * path, or a key on the public disk.
     */
    private function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset_v(ltrim($path, '/'));
        }

        return asset_v('storage/'.$path);
    }
}
