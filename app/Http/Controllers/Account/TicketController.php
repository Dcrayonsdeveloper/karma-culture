<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct()
    {
        abort_unless(Setting::get('support_tickets_enabled', true), 404);
    }

    public function index(Request $request): View
    {
        $query = $request->user()->supportTickets();

        if ($status = $request->input('status')) {
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
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:general,order,payment,product,account,other'],
            'priority' => ['required', 'string', 'in:low,normal,high'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
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
            'message' => ['required', 'string', 'min:5', 'max:5000'],
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
