<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    const OVERLAY_STYLES = [
        'none' => 'No Overlay',
        'left-dark' => 'Dark from Left',
        'right-dark' => 'Dark from Right',
        'full-dark' => 'Full Dark',
        'center-vignette' => 'Center Vignette',
        'purple-gradient' => 'Purple Gradient',
    ];

    protected $fillable = [
        'name',
        'title',
        'subtitle',
        'button_text',
        'position',
        'image_url',
        'mobile_image_url',
        'video_url',
        'link',
        'overlay_style',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority');
    }

    // Helper methods
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function getImageAttribute(): string
    {
        if (!$this->image_url) {
            return asset('images/placeholder-banner.jpg');
        }
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }
        if (str_starts_with($this->image_url, '/')) {
            return asset(ltrim($this->image_url, '/'));
        }
        return asset_v('storage/' . $this->image_url);
    }

    public function getMobileImageAttribute(): string
    {
        return $this->mobile_image_url
            ? asset_v('storage/' . $this->mobile_image_url)
            : $this->image;
    }

    /** Browser-usable video URL, or '' when this banner has no video. */
    public function getVideoAttribute(): string
    {
        if (! $this->video_url) {
            return '';
        }
        if (str_starts_with($this->video_url, 'http')) {
            return $this->video_url;
        }
        if (str_starts_with($this->video_url, '/')) {
            return asset(ltrim($this->video_url, '/'));
        }

        return asset_v('storage/' . $this->video_url);
    }

    public function getHasVideoAttribute(): bool
    {
        return (bool) $this->video_url;
    }

    public function getOverlayCssAttribute(): string
    {
        return match ($this->overlay_style ?? 'left-dark') {
            'none' => '',
            'left-dark' => 'bg-linear-to-r from-black/50 via-black/20 to-transparent',
            'right-dark' => 'bg-linear-to-l from-black/50 via-black/20 to-transparent',
            'full-dark' => 'bg-black/40',
            'center-vignette' => 'bg-radial-[ellipse_at_center] from-transparent via-black/20 to-black/50',
            'purple-gradient' => 'bg-linear-to-r from-purple-900/60 via-purple-800/30 to-transparent',
            default => 'bg-linear-to-r from-black/50 via-black/20 to-transparent',
        };
    }
}
