<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Notification;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $perPage = request()->input('per_page', 10);
        $notifications = Notification::latest()->paginate($perPage)->withQueryString();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function read(Notification $notification): RedirectResponse
    {
        $notification->markAsRead();

        // Redirect to the relevant page, falling back to notifications list if record was deleted
        if ($notification->type === 'new_enquiry' && isset($notification->data['enquiry_id'])) {
            if (Enquiry::find($notification->data['enquiry_id'])) {
                return redirect()->route('admin.enquiries.show', $notification->data['enquiry_id']);
            }
        }

        if ($notification->type === 'new_ticket' && isset($notification->data['ticket_id'])) {
            if (SupportTicket::find($notification->data['ticket_id'])) {
                return redirect()->route('admin.support-tickets.show', $notification->data['ticket_id']);
            }
        }

        return redirect()->route('admin.notifications');
    }
}
