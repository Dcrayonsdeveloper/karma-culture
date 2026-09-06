<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'seo_data',
        'is_published',
        'published_at',
    ];

    /**
     * The menu row generated from this page's placement field, if it has one.
     *
     * hasOne, not hasMany: the placement field offers a single location, and
     * syncMenuLink() keeps exactly one row per page. Links an admin hand-adds
     * in the Navigation editor carry no page_id and are not matched here.
     */
    public function menuLink(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\NavigationMenu::class);
    }

    /**
     * Named routes that serve one of these pages at an address of their own.
     *
     * Four legal pages are reachable twice: once at the tidy path their own
     * route defines, and once at the generic /page/{slug}. Both answered 200
     * with identical content, and the canonical tag was url()->current() - so
     * each address declared *itself* the canonical one. That is eight indexable
     * URLs for four documents, with the tag that exists to consolidate them
     * instead endorsing the split.
     *
     * @var array<string, string> slug => route name
     */
    public const DEDICATED_ROUTES = [
        'privacy-policy' => 'privacy',
        'terms-of-service' => 'terms',
        'cookie-policy' => 'cookie-policy',
        'gdpr' => 'gdpr',
    ];

    /**
     * The one address this page should be indexed at.
     *
     * A page with a route of its own canonicalises there; everything else -
     * anything an admin creates in the Pages editor - keeps /page/{slug},
     * which is the only address it has. Route::has() guards the lookup so
     * renaming a route degrades to the generic path rather than throwing.
     */
    public function canonicalUrl(): string
    {
        $route = self::DEDICATED_ROUTES[$this->slug] ?? null;

        return $route !== null && \Illuminate\Support\Facades\Route::has($route)
            ? route($route)
            : route('page.show', $this->slug);
    }

    /**
     * The same address as a site-relative path, for storing in a menu row.
     *
     * Menu URLs are kept relative so the table survives a domain change.
     */
    public function canonicalPath(): string
    {
        $route = self::DEDICATED_ROUTES[$this->slug] ?? null;

        return $route !== null && \Illuminate\Support\Facades\Route::has($route)
            ? route($route, absolute: false)
            : route('page.show', $this->slug, absolute: false);
    }

    /** Whether this page is being served at an address that is not its canonical one. */
    public function hasDedicatedRoute(): bool
    {
        $route = self::DEDICATED_ROUTES[$this->slug] ?? null;

        return $route !== null && \Illuminate\Support\Facades\Route::has($route);
    }

    protected function casts(): array
    {
        return [
            'seo_data' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
