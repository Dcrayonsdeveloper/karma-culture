<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Rules\ValidationRules as V;
use App\Support\ReviewMediaUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            // Was 'string|max:100'. This name is PUBLISHED under the review on
            // the product page, so a length was the only thing standing between
            // a drive-by "686876988" - or a URL - and the storefront. V::name()
            // is the same PersonName charset every other name box on the site
            // uses, and the form's pattern spells it out for the browser.
            'guest_name' => V::name(max: 100),
            'guest_email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|min:20|max:2000',
            'honeypot' => 'max:0', // anti-spam: must be empty
            // Media (Task 10): up to 5 images, up to 2 videos.
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',      // 5 MB
            'videos' => 'nullable|array|max:2',
            'videos.*' => 'mimetypes:video/mp4,video/webm,video/quicktime|max:20480', // 20 MB
        ]);

        // Check for duplicate guest review on same product
        $exists = Review::where('product_id', $product->id)
            ->where('guest_email', $validated['guest_email'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        $review = Review::create([
            'product_id' => $product->id,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'],
            'rating' => $validated['rating'],
            // 'title' is optional, so it is absent from the validated set when
            // the field is not submitted at all (API clients, older caches).
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'is_verified_purchase' => false,
            'is_approved' => false,
            'status' => 'pending',
        ]);

        ReviewMediaUploader::attach(
            $review,
            $request->file('images', []),
            $request->file('videos', []),
        );

        return back()->with('success', 'Thank you for your review! It will be visible after moderation.');
    }
}
