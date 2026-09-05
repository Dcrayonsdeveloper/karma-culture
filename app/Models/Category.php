<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasSlug;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        // A system row is one of the built-in listings - New In, Bestsellers,
        // Introductory Offer, Shop All - which now live in this table too. The
        // handle ties the row to the page it overrides; matching on name or
        // slug would break the moment somebody renamed it.
        'handle',
        'is_system',
        'description',
        'image_url',
        'video_url',
        'icon',
        'position',
        'level',
        'path',
        'is_active',
        'is_featured',
        'seo_data',
        'attributes_schema',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_system' => 'boolean',
            'seo_data' => 'array',
            'attributes_schema' => 'array',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    protected static function booted(): void
    {
        // System rows are destinations, not classifications: they own a URL and
        // a hand-picked list, and no product is ever "a Bestseller" the way it
        // is "a Kurta". So they are invisible to every ordinary category query
        // - the tree, the mega menu, breadcrumbs, the shop facet, the sitemap,
        // search, the API, and every admin parent/primary picker.
        //
        // Note scopeRoots() could not have done this: a system row has a null
        // parent_id too, so it looks like a root.
        //
        // The places that DO want them - Category::pickedProductIds(), the
        // storefront collection page, the merged admin screen, and the product
        // form's "also show in" list - opt in with ->withSystem().
        static::addGlobalScope('kk_real_categories', function ($query) {
            $query->where('categories.is_system', false);
        });

        // The navigation menu is cached for five minutes. Without this, renaming
        // or adding a category left the old menu on the site and a browser hard
        // refresh could not fix it, because the stale copy lives on the server.
        $forgetMenu = function () {
            \Illuminate\Support\Facades\Cache::forget('kk_mega_menu_v5');
        };

        static::saved($forgetMenu);
        static::deleted($forgetMenu);
        static::creating(function ($category) {
            $category->level = $category->parent ? $category->parent->level + 1 : 0;
            $category->path = $category->parent
                ? $category->parent->path . '/' . $category->id
                : (string) $category->id;
        });

        static::created(function ($category) {
            // Update path after creation when ID is available
            $category->update([
                'path' => $category->parent
                    ? $category->parent->path . '/' . $category->id
                    : (string) $category->id,
            ]);
        });
    }

    /**
     * Route model binding ignores the "real categories only" scope.
     *
     * Binding is an identity lookup - "the row with this key" - not a browse
     * surface, and the scope exists to keep system rows out of menus, facets
     * and pickers. Leaving it on here meant the admin could not open, edit or
     * deactivate a built-in listing at all: the route simply failed to resolve
     * it, which surfaces as a silent 404 rather than an error anyone could act
     * on. The pages that must NOT serve a system row say so themselves - the
     * storefront category page 404s on one explicitly.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->newQuery()
            ->withoutGlobalScope('kk_real_categories')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * The products hand-picked for one of the built-in listings, if any.
     *
     * An empty array means nobody has ticked anything, and the page it belongs
     * to keeps computing itself - New In by date, Bestsellers by sales count,
     * Introductory Offer by discount, Shop All the whole catalogue. Ticking one
     * product switches that page over to the picks; unticking them all switches
     * it back.
     *
     * Moved here from ProductCollection when the two tables became one. The
     * behaviour is unchanged, including the part that looks like a bug: every
     * ticked product is returned, live or not. The listing filters on is_active
     * anyway, so a product taken off sale still disappears - but it does NOT
     * take the override with it. Filtering here instead meant deactivating the
     * only pick emptied the list, which read as "nobody has picked anything"
     * and quietly put the whole catalogue back on a page the admin had curated
     * down to one product.
     *
     * @return array<int, int>
     */
    public static function pickedProductIds(string $handle): array
    {
        $row = static::query()
            ->withSystem()
            ->where('handle', $handle)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return [];
        }

        return $row->shownProducts()->pluck('products.id')->all();
    }

    /**
     * Every product shown under this row.
     *
     * The same pivot the storefront already filters through, so a system row's
     * picks and a category's shelf are one relation rather than two.
     */
    public function shownProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Real categories: the tree, and nothing else.
     *
     * This is the default for every surface that answers "what is this product"
     * or "what can I browse" - the mega menu, breadcrumbs, the shop facet, the
     * sitemap, the product form's category select. A system row is a
     * destination, not a classification: it has a URL and a hand-picked list,
     * and no product is ever "a Bestseller" the way it is "a Kurta".
     *
     * scopeRoots() is deliberately NOT enough on its own - a system row has a
     * null parent_id too, so it looks like a root.
     */
    public function scopeReal($query)
    {
        return $query->withoutGlobalScope('kk_real_categories')->where('categories.is_system', false);
    }

    /** The built-in listings: New In, Bestsellers, Introductory Offer, Shop All. */
    public function scopeSystem($query)
    {
        return $query->withoutGlobalScope('kk_real_categories')->where('categories.is_system', true);
    }

    /** Everything, tree and built-in listings alike. */
    public function scopeWithSystem($query)
    {
        return $query->withoutGlobalScope('kk_real_categories');
    }

    public function getAncestorsAttribute()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    public function getAllDescendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }

        return $ids;
    }

    /**
     * All categories labelled with their full tree path ("Men's › Kurtas") and
     * sorted by it, for admin dropdowns. Bare names collide there: Kurtas
     * exists under Men's, under Women's, and as a legacy root, and a flat
     * name-sorted list shows them as indistinguishable duplicates.
     */
    public static function optionsWithPath()
    {
        $all = static::orderBy('name')->get();
        $byId = $all->keyBy('id');

        return $all->map(function ($category) use ($byId) {
            $label = $category->name;
            $parentId = $category->parent_id;
            $depth = 0;
            while ($parentId && isset($byId[$parentId]) && $depth++ < 6) {
                $label = $byId[$parentId]->name.' › '.$label;
                $parentId = $byId[$parentId]->parent_id;
            }
            $category->setAttribute('path_label', $label.($category->is_active ? '' : ' (inactive)'));

            return $category;
        })->sortBy('path_label', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * The same list, minus every category that has children, for the product
     * form's category picker. Parents like "Men" are shelves rather than
     * buckets: a product filed straight onto one never appears under any
     * sub-category shoppers actually browse, so only the bottom level is
     * offered. $keepId re-admits one category regardless - pass the product's
     * current category on the edit screen, so an older product sitting on a
     * parent keeps its value instead of silently reverting to "Select".
     */
    public static function assignableOptions(?int $keepId = null)
    {
        $parentIds = array_flip(
            static::whereNotNull('parent_id')->distinct()->pluck('parent_id')->all()
        );

        return static::optionsWithPath()
            ->filter(fn ($category) => ! isset($parentIds[$category->id]) || $category->id === $keepId)
            ->values();
    }

    /**
     * Browser-ready URL for the category image, or null when none is set.
     * image_url holds a storage-relative path (admin upload), but may also be
     * a full URL or an absolute path - resolve all three like Banner does.
     */
    public function getImageSrcAttribute(): ?string
    {
        $path = $this->image_url;

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
