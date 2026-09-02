<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Rules\ValidationRules as V;
use App\Support\ReviewMediaUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = $request->user()->reviews()
            ->with('product')
            ->latest()
            ->paginate(10);

        return view('account.reviews.index', compact('reviews'));
    }

    public function create(Request $request, Product $product): View
    {
        // Check if user has purchased this product
        $hasPurchased = $request->user()->orders()
            ->where('status', 'delivered')
            ->whereHas('items', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->exists();

        // Check if user already reviewed this product
        $existingReview = $request->user()->reviews()
            ->where('product_id', $product->id)
            ->first();

        return view('account.reviews.create', compact('product', 'hasPurchased', 'existingReview'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $existingReview = $request->user()->reviews()
            ->where('product_id', $product->id)
            ->first();

        if ($existingReview) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        $hasPurchased = $request->user()->orders()
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            // The three text fields were plain string|max rules, so a review
            // could carry markup into a column that the product page, the
            // moderation queue and the review emails all render.
            'title' => V::text(required: false, max: 255),
            'content' => V::textarea(max: 2000),
            'pros' => V::textarea(required: false, max: 1000),
            'cons' => V::textarea(required: false, max: 1000),
            'images' => 'nullable|array|max:5',
            // mimes checks the extension, mimetypes sniffs the bytes: with
            // mimes alone a PHP script named photo.jpg passes.
            'images.*' => V::image(required: false, maxKb: 5120),
            'videos' => 'nullable|array|max:2',
            'videos.*' => [
                'nullable',
                'file',
                'mimes:mp4,webm,mov',
                'mimetypes:video/mp4,video/webm,video/quicktime',
                'max:20480',
            ],
        ], [
            'rating.required' => 'Please choose a star rating.',
            'content.required' => 'Please write a few words about the product.',
        ]);

        if (! empty($validated['pros'])) {
            $validated['pros'] = array_filter(array_map('trim', explode("\n", $validated['pros'])));
        }
        if (! empty($validated['cons'])) {
            $validated['cons'] = array_filter(array_map('trim', explode("\n", $validated['cons'])));
        }

        // Only mass-assign review columns (exclude file inputs).
        $reviewData = collect($validated)->except(['images', 'videos'])->all();
        $reviewData['user_id'] = $request->user()->id;
        $reviewData['product_id'] = $product->id;
        $reviewData['status'] = 'pending';
        $reviewData['is_verified_purchase'] = $hasPurchased;

        $review = Review::create($reviewData);

        ReviewMediaUploader::attach(
            $review,
            $request->file('images', []),
            $request->file('videos', []),
        );

        return redirect()->route('account.reviews')
            ->with('success', 'Thank you for your review! It will be published after moderation.');
    }
}
