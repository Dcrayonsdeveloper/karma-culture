<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /** The moderation states a review can be filtered by, matching the column enum. */
    private const STATUSES = ['pending', 'approved', 'rejected', 'flagged'];

    public function index(Request $request): View
    {
        // Read-only screen, so the only writable inputs are the filters - and
        // they still need bounds: per_page went straight into paginate().
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Review::with(['product:id,name,slug', 'user:id,first_name,last_name']);

        // Filter on the same column the badges and tab counts read. Filtering on
        // is_approved instead cannot tell "rejected" from "not moderated yet",
        // so a single rejection dragged every untouched review in with it.
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        $reviews = $query->latest()
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'counts' => $this->statusCounts(),
        ]);
    }

    public function pending(Request $request): View
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $reviews = Review::where('status', 'pending')
            ->with(['product:id,name,slug', 'user:id,first_name,last_name'])
            ->latest()
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return view('admin.reviews.pending', compact('reviews'));
    }

    public function show(Review $review): View
    {
        $review->load(['product', 'user']);

        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Review $review): RedirectResponse
    {
        // Goes through the model so status moves in step with is_approved -
        // writing the boolean alone left the row reading "Pending" forever.
        $review->approve(auth()->id());

        return back()->with('success', 'Review approved');
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->reject(auth()->id());

        return back()->with('success', 'Review rejected');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Review deleted');
    }

    /**
     * Tab counts, as one grouped query rather than a COUNT per tab in the view.
     *
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        $counts = Review::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return array_replace(array_fill_keys(self::STATUSES, 0), $counts);
    }
}
