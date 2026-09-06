<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'category',
        'tags',
        'author_id',
        'is_published',
        'published_at',
        'newsletter_sent_at',
        'seo_data',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'tags'         => 'array',
            'seo_data'     => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'newsletter_sent_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->where(function ($q) {
                         $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                     });
    }

    /**
     * Published, and the subscribers have not been told yet.
     *
     * Built on scopePublished() rather than beside it, so a post scheduled for
     * next Tuesday is not mailed out today: the same clause decides who can
     * read it and who gets told about it.
     */
    public function scopeAwaitingNewsletter($query)
    {
        return $query->published()->whereNull('newsletter_sent_at');
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        return (int) ceil($wordCount / 200) ?: 1;
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }
}
