<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A hand-picked group of products with its own page and optional header link.
 *
 * Named ProductCollection rather than Collection: the class would otherwise
 * shadow Illuminate\Support\Collection in every file that touches both, and
 * this model travels through controllers that are full of collections in the
 * other sense. The table keeps the short name.
 */
class ProductCollection extends Model
{
    protected $table = 'collections';

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'show_in_header', 'position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_header' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_product', 'collection_id', 'product_id');
    }

    /**
     * The collections the header offers, in the order the admin set.
     *
     * An empty collection is still listed. The admin put it in the header on
     * purpose, and quietly dropping a link they configured is how a menu ends
     * up disagreeing with the screen that manages it - the collection's own
     * page says it is empty instead.
     */
    public static function forHeader()
    {
        return static::query()
            ->where('is_active', true)
            ->where('show_in_header', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
