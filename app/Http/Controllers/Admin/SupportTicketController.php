<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupportTicket::with('user');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'open')->count(),
            'answered' => SupportTicket::where('status', 'answered')->count(),
            'closed' => SupportTicket::where('status', 'closed')->count(),
        ];

        return view('admin.support-tickets.index', compact('tickets', 'stats'));
    }

    public function show(SupportTicket $supportTicket): View
    {
        $supportTicket->load(['user', 'replies.user']);

        // Mark related notifications as read
        Notification::where('type', 'new_ticket')
            ->where('data->ticket_id', $supportTicket->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('admin.support-tickets.show', compact('supportTicket'));
    }

    public function reply(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        SupportTicketReply::create([
            'support_ticket_id' => $supportTicket->id,
            'user_id' => auth('admin')->id(),
            'message' => $validated['message'],
            'is_admin' => true,
        ]);

        $supportTicket->update(['status' => 'answered']);

        // Notify the customer
        Notification::create([
            'user_id' => $supportTicket->user_id,
            'type' => 'ticket_reply',
            'title' => 'Ticket Reply',
            'content' => "Your ticket \"{$supportTicket->subject}\" has been replied to.",
            'data' => [
                'ticket_id' => $supportTicket->id,
                'subject' => $supportTicket->subject,
            ],
            'channel' => 'database',
        ]);

        return back()->with('success', 'Reply sent to customer.');
    }

    public function updateStatus(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:open,answered,closed'],
        ]);

        $supportTicket->update(['status' => $request->input('status')]);

        return back()->with('success', 'Ticket status updated.');
    }

    public function destroy(SupportTicket $supportTicket): RedirectResponse
    {
        $supportTicket->delete();

        return redirect()->route('admin.support-tickets.index')->with('success', 'Ticket deleted.');
    }
}
