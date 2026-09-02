<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
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
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = trim($filters['search'] ?? '');

        // The search box submits this and always had; nothing read it, so the
        // Search button just reloaded the unfiltered list.
        $matching = fn () => Review::query()->when($search !== '', fn ($q) => $this->applySearch($q, $search));

        $query = $matching()->with(['product:id,name,slug', 'user:id,first_name,last_name']);

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
            // Counted over the same search, so the tabs say how many matches sit
            // behind each one rather than advertising rows the search excluded.
            'counts' => $this->statusCounts($matching()),
        ]);
    }

    /**
     * The Pending tab on the index does this with counts, search and badges,
     * and nothing links here - so keep the URL working and send it there.
     */
    public function pending(): RedirectResponse
    {
        return redirect()->route('admin.reviews.index', ['status' => 'pending']);
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
     * Match the term against what the box promises - product or customer - across
     * both kinds of reviewer, the signed-in user and the guest name on the row.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        // LIKE wildcards are not escaped by the binding, so a term containing %
        // would otherwise match everything.
        $term = '%'.addcslashes($search, '%_\\').'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->whereHas('product', fn (Builder $p) => $p->where('name', 'like', $term))
                ->orWhereHas('user', fn (Builder $u) => $u
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$term]))
                ->orWhere('guest_name', 'like', $term);
        });
    }

    /**
     * Tab counts, as one grouped query rather than a COUNT per tab in the view.
     *
     * @return array<string, int>
     */
    private function statusCounts(Builder $query): array
    {
        $counts = $query
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return array_replace(array_fill_keys(self::STATUSES, 0), $counts);
    }
}
