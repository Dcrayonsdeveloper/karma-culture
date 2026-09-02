<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    /** The categories the form offers. */
    public const CATEGORIES = ['general', 'order', 'payment', 'product', 'account', 'other'];

    /** The priorities the form offers. */
    public const PRIORITIES = ['low', 'normal', 'high'];

    /** The statuses the filter tabs offer, and the only ones a ticket holds. */
    public const STATUSES = ['open', 'answered', 'closed'];

    public function __construct()
    {
        abort_unless(Setting::get('support_tickets_enabled', true), 404);
    }

    public function index(Request $request): View
    {
        $query = $request->user()->supportTickets();

        // The tab links are the only intended source of ?status=, but the query
        // string is the customer's to write. An unknown value now shows the
        // unfiltered list rather than being passed into the where clause.
        $status = $request->query('status');

        if (is_string($status) && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        return view('account.tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('account.tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // NotBlank and NoHtml on top of the old string|max: the subject is
            // rendered in the admin queue and in the notification body below,
            // and "   " used to be a valid subject.
            'subject' => V::text(max: 255, min: 3),
            'category' => V::option(self::CATEGORIES),
            'priority' => V::option(self::PRIORITIES),
            'message' => V::textarea(max: 5000, min: 10),
        ], [
            'category.in' => 'Please choose a category from the list.',
            'priority.in' => 'Please choose a priority from the list.',
            'message.min' => 'Please describe the issue in at least 10 characters.',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
        ]);

        // Notify admin users
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_ticket',
                'title' => 'New Support Ticket',
                'content' => "New ticket from {$request->user()->full_name}: {$ticket->subject}",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'category' => $ticket->category,
                    'priority' => $ticket->priority,
                ],
                'channel' => 'database',
            ]);
        }

        return redirect()->route('account.tickets.show', $ticket)
            ->with('success', 'Your ticket has been submitted. We\'ll get back to you soon.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        $ticket->load(['replies.user']);

        return view('account.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);
        abort_if($ticket->status === 'closed', 403, 'This ticket is closed.');

        $validated = $request->validate([
            'message' => V::textarea(max: 5000, min: 5),
        ], [
            'message.min' => 'Please write at least 5 characters.',
        ]);

        SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_admin' => false,
        ]);

        // Reopen if it was closed/resolved
        if ($ticket->status !== 'open') {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Reply sent.');
    }
}
