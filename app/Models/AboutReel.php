<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One clip in the About Us reel strip.
 *
 * Replaces the three fixed `about_us_video_*` settings keys, so the strip can
 * hold one reel or eight and a reel can be taken out of the middle without
 * shuffling files between slots.
 */
class AboutReel extends Model
{
    use HasFactory;

    protected $fillable = ['video_path', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
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
     * What the storefront and the admin preview put in `src`.
     *
     * An absolute URL is used as it stands; anything else is a path inside the
     * site (an upload under storage/, or a clip bundled with the build) and
     * goes through asset_v() for its cache-busting stamp.
     */
    public function getUrlAttribute(): string
    {
        $path = trim((string) $this->video_path);

        if ($path === '') {
            return '';
        }

        return str_starts_with($path, 'http') ? $path : asset_v($path);
    }

    /**
     * True when the file lives on the public disk, i.e. it was uploaded here
     * and this row is the only thing pointing at it.
     *
     * The bundled defaults (videos/karmaa-about.mp4) and https:// links are
     * not ours to delete - one ships with the repo, the other is somebody
     * else's server.
     */
    public function ownsFile(): bool
    {
        return str_starts_with((string) $this->video_path, 'storage/');
    }

    /** The path as Storage::disk('public') knows it. */
    public function storagePath(): string
    {
        return substr((string) $this->video_path, strlen('storage/'));
    }
}
