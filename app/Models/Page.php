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
