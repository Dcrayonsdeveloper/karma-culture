<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Rules\ValidationRules as V;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TicketController extends Controller
{
    /** The categories the form offers. */
    public const CATEGORIES = ['general', 'order', 'payment', 'product', 'account', 'other'];

    /** The priorities the form offers. */
    public const PRIORITIES = ['low', 'normal', 'high'];

    /** The statuses the filter tabs offer, and the only ones a ticket holds. */
    public const STATUSES = ['open', 'answered', 'closed'];

    public function __construct(private NotificationService $notifications)
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

        // Through the service instead of a hand-rolled loop over the admin
        // users. That loop wrote its rows without an audience, so the shop's
        // alerts and a shopper's own order updates landed in the table
        // indistinguishable from one another - and an admin who also shops saw
        // both in the same bell. notifyAdmins() stamps audience = admin, and is
        // now the one path this and reply() below both take.
        $this->alertAdmins('new_ticket', 'New Support Ticket',
            "New ticket from {$request->user()->full_name}: {$ticket->subject}",
            [
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
            ]
        );

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

        // A customer's reply reopened the ticket but told nobody it had, so a
        // ticket already marked answered dropped back to the bottom of the
        // queue and the reply sat there unread.
        $this->alertAdmins('ticket_customer_reply', 'Customer Replied',
            "{$request->user()->full_name} replied to ticket: {$ticket->subject}",
            [
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject,
            ]
        );

        return back()->with('success', 'Reply sent.');
    }

    /**
     * Fan a ticket event out to the admins without it ever costing the customer
     * the thing they came here to do.
     *
     * Both callers run after the row the customer cares about is committed, so
     * a notification that throws has to be logged and dropped: turning it into
     * an error page would tell them their ticket or reply failed when it is
     * sitting in the database.
     */
    private function alertAdmins(string $type, string $title, string $content, array $data): void
    {
        try {
            $this->notifications->notifyAdmins($type, $title, $content, $data);
        } catch (\Throwable $e) {
            Log::error('Failed to notify admins of a support ticket event', [
                'type' => $type,
                'ticket_id' => $data['ticket_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
