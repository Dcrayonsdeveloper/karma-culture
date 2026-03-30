<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::whereHas('product', fn($q) => $q->where('seller_id', $request->user()->seller->id))
            ->with(['product:id,name,slug', 'user:id,first_name,last_name'])
            ->latest()
            ->paginate(20);

        return view('seller.reviews.index', compact('reviews'));
    }

    public function show(Request $request, Review $review): View
    {
        $review->load(['product', 'user']);

        return view('seller.reviews.show', compact('review'));
    }

    public function respond(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'response' => 'required|string|max:1000',
        ]);

        $review->update([
            'seller_response' => $validated['response'],
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Response submitted');
    }
}
