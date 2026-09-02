<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    /**
     * The filters the list and the export both understand.
     *
     * They are shared deliberately. The export used to accept `status` alone
     * while the list also filtered on `search` and `source`, so an admin who
     * searched for one subscriber and hit Export CSV silently downloaded the
     * entire list. Both entry points now validate and apply the same set.
     */
    private function filterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function filtered(array $filters): Builder
    {
        $query = NewsletterSubscriber::query();

        if ($search = $filters['search'] ?? null) {
            // % and _ are LIKE wildcards. An admin pasting a phone number or an
            // address is typing a literal, not a pattern, so they are escaped.
            $term = '%'.addcslashes($search, '%_\\').'%';

            $query->where(function ($q) use ($term) {
                $q->where('email', 'like', $term)
                  ->orWhere('name', 'like', $term)
                  ->orWhere('phone', 'like', $term);
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('is_active', $status === 'active');
        }

        if ($source = $filters['source'] ?? null) {
            $query->where('source', $source);
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $filters = $request->validate($this->filterRules());

        $subscribers = $this->filtered($filters)->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'    => NewsletterSubscriber::count(),
            'active'   => NewsletterSubscriber::active()->count(),
            'inactive' => NewsletterSubscriber::inactive()->count(),
            'sources'  => NewsletterSubscriber::select('source')
                ->distinct()->pluck('source')->sort()->values(),
        ];

        return view('admin.newsletter.index', compact('subscribers', 'stats'));
    }

    public function destroy(NewsletterSubscriber $newsletter): RedirectResponse
    {
        $newsletter->delete();

        return back()->with('success', 'Subscriber removed.');
    }

    public function toggleStatus(NewsletterSubscriber $newsletter): RedirectResponse
    {
        $newsletter->update([
            'is_active'        => !$newsletter->is_active,
            'unsubscribed_at'  => $newsletter->is_active ? now() : null,
        ]);

        return back()->with('success', 'Subscriber status updated.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['delete', 'activate', 'deactivate'])],
            // The page posts one hidden ids[] input per ticked row, so this is
            // a real array rather than a JSON string. A ceiling keeps a forged
            // request from turning one click into an unbounded write.
            'ids'    => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*'  => ['integer', Rule::exists('newsletter_subscribers', 'id')],
        ]);

        $subscribers = NewsletterSubscriber::whereIn('id', $validated['ids']);

        match ($validated['action']) {
            'delete'     => $subscribers->delete(),
            'activate'   => $subscribers->update(['is_active' => true, 'unsubscribed_at' => null]),
            'deactivate' => $subscribers->update(['is_active' => false, 'unsubscribed_at' => now()]),
        };

        $count = count($validated['ids']);

        return back()->with('success', "{$count} subscriber(s) updated.");
    }

    /**
     * One CSV field, quoted and de-fanged.
     *
     * Two separate problems were being ignored. A name containing a comma or a
     * double quote broke the column alignment of the whole row, because the
     * value was wrapped in quotes without doubling the ones inside it. And a
     * value opening with =, +, - or @ is a formula to Excel and Google Sheets:
     * a subscriber who signs up as `=HYPERLINK("http://evil","click")` gets
     * that executed in the spreadsheet of whoever opens the export. Prefixing a
     * tab makes the cell text, and the tab is invisible in the sheet.
     */
    private function csvCell(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            $value = "\t".$value;
        }

        return '"'.str_replace('"', '""', $value).'"';
    }

    public function export(Request $request): Response
    {
        $filters = $request->validate($this->filterRules());

        $subscribers = $this->filtered($filters)->orderBy('email')->get();

        // Phone is in the export because it is the reason the offer popup asks
        // for one: the list is worked in WhatsApp, and a number that only ever
        // sits in the database is a number nobody can use.
        $csv = "Email,Name,Phone,Source,Status,Subscribed At\n";
        foreach ($subscribers as $sub) {
            $csv .= implode(',', [
                $this->csvCell($sub->email),
                $this->csvCell($sub->name),
                $this->csvCell($sub->phone),
                $this->csvCell($sub->source),
                $this->csvCell($sub->is_active ? 'Active' : 'Inactive'),
                $this->csvCell($sub->subscribed_at?->format('Y-m-d H:i') ?? $sub->created_at->format('Y-m-d H:i')),
            ])."\n";
        }

        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
