<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EnquiryReplied;
use App\Models\Enquiry;
use App\Models\EnquiryReply;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $applySearch = function ($query) use ($search) {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                });
            }

            return $query;
        };

        $query = $applySearch(Enquiry::query());

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $enquiries = $query->latest()->paginate(15)->withQueryString();

        // Counts follow the search so the tab labels never contradict the rows
        // underneath them. `closed` was missing entirely, which left the Closed
        // tab as the only one without a number.
        $counts = $applySearch(Enquiry::query())
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = [
            'total' => (int) $counts->sum(),
            'new' => (int) $counts->get('new', 0),
            'read' => (int) $counts->get('read', 0),
            'replied' => (int) $counts->get('replied', 0),
            'closed' => (int) $counts->get('closed', 0),
        ];

        return view('admin.enquiries.index', compact('enquiries', 'stats'));
    }

    public function show(Enquiry $enquiry): View
    {
        $enquiry->markAsRead();

        // Mark related notifications as read
        Notification::where('type', 'new_enquiry')
            ->where('data->enquiry_id', $enquiry->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        $enquiry->load('replies.user');

        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function reply(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        EnquiryReply::create([
            'enquiry_id' => $enquiry->id,
            'user_id' => auth('admin')->id(),
            'message' => $validated['message'],
        ]);

        // An enquiry comes from the public contact form, so there is no account to
        // notify in-app -- email is the only way the reply reaches the sender. Keep
        // the typed reply when the address is unroutable or the mailer is down, but
        // leave the status alone: an undelivered reply is still an open enquiry, and
        // marking it "replied" would drop it out of the triage queue for good.
        try {
            Mail::to($enquiry->email)->send(new EnquiryReplied($enquiry, $validated['message']));
        } catch (\Throwable $e) {
            Log::error('Failed to email enquiry reply', [
                'enquiry_id' => $enquiry->id,
                'email' => $enquiry->email,
                'error' => $e->getMessage(),
            ]);

            return back()->with('warning', "Reply saved, but the email to {$enquiry->email} could not be sent. The enquiry has been left open.");
        }

        $enquiry->update(['status' => 'replied']);

        return back()->with('success', "Reply sent to {$enquiry->email}.");
    }

    public function toggleRead(Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->is_read) {
            $enquiry->update([
                'is_read' => false,
                'read_at' => null,
                'status' => 'new',
            ]);
        } else {
            $enquiry->markAsRead();
        }

        return back()->with('success', $enquiry->is_read ? 'Marked as read.' : 'Marked as unread.');
    }

    public function updateStatus(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:new,read,replied,closed'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $enquiry->update([
            'status' => $request->input('status'),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return back()->with('success', 'Enquiry status updated.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry deleted.');
    }
}
