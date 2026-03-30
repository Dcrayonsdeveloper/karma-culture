<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    protected $fillable = [
        'location', 'label', 'url', 'icon',
        'parent_id', 'position', 'is_active', 'open_in_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'open_in_new_tab' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NavigationMenu::class, 'parent_id')->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public static function getByLocation(string $location)
    {
        return static::active()
            ->location($location)
            ->roots()
            ->with('children')
            ->orderBy('position')
            ->get();
    }
}
