<?php

namespace App\Support;

use App\Models\Review;
use Illuminate\Http\UploadedFile;

class ReviewMediaUploader
{
    /**
     * Persist uploaded images/videos to a review (review_images table, public disk).
     *
     * @param  array<int, UploadedFile|null>  $images
     * @param  array<int, UploadedFile|null>  $videos
     */
    public static function attach(Review $review, array $images = [], array $videos = []): void
    {
        $position = 0;

        foreach (array_slice(array_filter($images), 0, 5) as $image) {
            if (! $image instanceof UploadedFile) {
                continue;
            }
            $review->images()->create([
                'media_type' => 'image',
                'url' => $image->store('reviews', 'public'),
                'position' => $position++,
            ]);
        }

        foreach (array_slice(array_filter($videos), 0, 2) as $video) {
            if (! $video instanceof UploadedFile) {
                continue;
            }
            $review->images()->create([
                'media_type' => 'video',
                'url' => $video->store('reviews', 'public'),
                'position' => $position++,
            ]);
        }
    }
}
