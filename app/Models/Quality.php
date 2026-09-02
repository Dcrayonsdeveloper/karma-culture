<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quality extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'image_url', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Public URL for the card's background image, or an empty string when the
     * card has none — the front end falls back to its flat "plain" layout
     * rather than reserving space for a picture that will never load.
     */
    public function getImageAttribute(): string
    {
        if (! $this->image_url) {
            return '';
        }
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }
        if (str_starts_with($this->image_url, '/')) {
            return asset(ltrim($this->image_url, '/'));
        }

        return asset_v('storage/' . $this->image_url);
    }
}
