<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(): View
    {
        $query = NewsletterSubscriber::query();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('is_active', $status === 'active');
        }

        if ($source = request('source')) {
            $query->where('source', $source);
        }

        $subscribers = $query->latest()->paginate(20)->withQueryString();

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
            'action' => 'required|in:delete,activate,deactivate',
            'ids'    => 'required|array',
            'ids.*'  => 'integer|exists:newsletter_subscribers,id',
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

    public function export(): Response
    {
        $query = NewsletterSubscriber::query();

        if ($status = request('status')) {
            $query->where('is_active', $status === 'active');
        }

        $subscribers = $query->orderBy('email')->get();

        $csv = "Email,Name,Source,Status,Subscribed At\n";
        foreach ($subscribers as $sub) {
            $csv .= implode(',', [
                $sub->email,
                '"' . ($sub->name ?? '') . '"',
                $sub->source,
                $sub->is_active ? 'Active' : 'Inactive',
                $sub->subscribed_at?->format('Y-m-d H:i') ?? $sub->created_at->format('Y-m-d H:i'),
            ]) . "\n";
        }

        $filename = 'newsletter-subscribers-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
