<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
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
     * Browser-ready URL for the category image, or null when none is set.
     * image_url holds a storage-relative path (admin upload), but may also be
     * a full URL or an absolute path — resolve all three like Banner does.
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
            return asset(ltrim($path, '/'));
        }

        return asset('storage/'.$path);
    }
}
