<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    protected $fillable = [
        'key', 'title', 'subtitle', 'type', 'content',
        'background_color', 'text_color', 'image_url',
        'button_text', 'button_link', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
