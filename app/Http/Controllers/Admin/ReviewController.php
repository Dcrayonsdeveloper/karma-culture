<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::with(['product:id,name,slug', 'user:id,first_name,last_name']);

        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        $perPage = $request->input('per_page', 10);
        $reviews = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function pending(): View
    {
        $perPage = request()->input('per_page', 10);
        $reviews = Review::where('is_approved', false)
            ->with(['product:id,name,slug', 'user:id,first_name,last_name'])
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.reviews.pending', compact('reviews'));
    }

    public function show(Review $review): View
    {
        $review->load(['product', 'user']);

        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => true]);

        return back()->with('success', 'Review approved');
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => false]);

        return back()->with('success', 'Review rejected');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Review deleted');
    }
}
