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
            // The wording is the browser's, not ours. This form carries no
            // `novalidate`, so the site-wide validator in app.js owns these two
            // boxes and names them after their own <label>: "Subject is
            // required.", "Message must be at least 10 characters." The server
            // said "Please describe the issue in at least 10 characters." for
            // the identical rule, so one under-length message was complained
            // about in two different voices depending on which side caught it -
            // and both can be on screen at once, because a value the browser
            // accepts is not always one Laravel does: twelve spaces satisfy
            // minlength="10", and TrimStrings reduces them to nothing before
            // `required` here ever sees them.
            'subject.required' => 'Subject is required.',
            'subject.min' => 'Subject must be at least 3 characters.',
            'message.required' => 'Message is required.',
            'message.min' => 'Message must be at least 10 characters.',
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

        // The textarea carries aria-label="Reply", so app.js names it "Reply"
        // when it catches an empty or short box; these are the same two
        // sentences, so the customer is told one thing about one mistake
        // whichever side notices it first.
        $validated = $request->validate([
            'message' => V::textarea(max: 5000, min: 5),
        ], [
            'message.required' => 'Reply is required.',
            'message.min' => 'Reply must be at least 5 characters.',
        ]);

        // A ticket closed by support AFTER this page was rendered is a state
        // failure, not an authorisation one: the customer did nothing wrong and
        // has just typed a reply. abort(403) answered it with a bare error page
        // that threw the typed reply away and looked nothing like the other
        // failures on this form.
        //
        // The message is deliberately NOT put on the `message` key. back() lands
        // on the ticket, where the status is still closed, so the reply box - and
        // with it the only <x-field-error field="message"> on that page - is not
        // rendered at all; a message on that key was filtered out of the
        // form-level banner as "already handled inline" and therefore printed
        // nowhere, leaving the customer with the static closed panel and no hint
        // that his reply had been refused. Nor is this something he could fix by
        // editing that box, which is what a message under a field promises. So it
        // goes on a key no input owns and lands on the form-level line, which is
        // exactly what that line is reserved for. withInput() is what lets the
        // closed panel hand the typed reply back to be pasted into the new
        // ticket, so it is no longer decoration.
        //
        // Checked AFTER validation on purpose: an empty box is a "required"
        // failure, which outranks a business rule, so a customer who submits
        // nothing is told to write something rather than being sent away.
        if ($ticket->status === 'closed') {
            return back()->withInput()->withErrors([
                'ticket' => 'This ticket is closed, so your reply was not sent. Please raise a new ticket and we will pick it up from there.',
            ]);
        }

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
