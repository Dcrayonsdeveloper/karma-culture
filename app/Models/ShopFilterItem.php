<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Legacy: the hand-typed "Shop It Your Way" hangers.
 *
 * The rails are derived from the catalogue now - see {@see
 * \App\Support\ShopFilterCatalogue} - so nothing on the storefront or in the
 * admin reads this table any more. The rows are kept rather than dropped
 * because they are production data an admin entered, and because a migration
 * carried the switched-off ones across as filter exclusions: if one of those
 * decisions turns out to be wrong, the row that explains it is still here. The
 * only live reader left is the SeedTestCloneProducts console command, which
 * uses the labels as a palette when generating demo products.
 */
class ShopFilterItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'label', 'shade_hex',
        'query_string', 'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    public const TYPES = ['size', 'price', 'shade'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('type')->orderBy('position')->orderBy('id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
