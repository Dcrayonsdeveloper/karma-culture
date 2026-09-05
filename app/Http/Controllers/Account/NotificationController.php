<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        // Only the customer audience: an admin is a users row with role =
        // 'admin', so without this filter an admin who also shops would find the
        // store's new-order alerts sitting in their own account page.
        $notifications = $request->user()->notifications()
            ->forCustomer()
            ->latest()
            ->paginate(20);

        $unreadCount = $request->user()->notifications()
            ->forCustomer()
            ->unread()
            ->count();

        return view('account.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Open what a notification is about, marking it read on the way through.
     *
     * The account page had an index() and nothing else, so a customer had no
     * way to mark anything read from the website: every notification they had
     * ever received rendered as unread forever and the read/unread styling the
     * page is built around never had a chance to fire.
     */
    public function read(Request $request, Notification $notification): RedirectResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        // An admin-audience row can be owned by this same users row; it belongs
        // to the admin bell and must not be clearable from the account page.
        if ($notification->audience !== Notification::AUDIENCE_CUSTOMER) {
            abort(403);
        }

        $notification->markAsRead();

        $data = $notification->data ?? [];

        // The record a notification points at can be gone by the time it is
        // clicked - a cancelled return, a purged order - and redirecting to a
        // missing id would 404 the customer out of their own account. Same
        // defensive check as app/Http/Controllers/Admin/NotificationController.
        if (str_starts_with($notification->type, 'order_') && isset($data['order_id'])) {
            if (Order::find($data['order_id'])) {
                return redirect()->route('account.orders.show', $data['order_id']);
            }
        }

        // refund_processed is about a return too - it carries return_id, not an
        // id of its own - so it shares this branch with the return_* types.
        if ((str_starts_with($notification->type, 'return_') || $notification->type === 'refund_processed')
            && isset($data['return_id'])) {
            if (OrderReturn::find($data['return_id'])) {
                return redirect()->route('account.returns.show', $data['return_id']);
            }
        }

        if ($notification->type === 'ticket_reply' && isset($data['ticket_id'])) {
            if (SupportTicket::find($data['ticket_id'])) {
                return redirect()->route('account.tickets.show', $data['ticket_id']);
            }
        }

        // Everything else - back_in_stock, price_drop, anything added later -
        // has nowhere of its own to go, so the list is the destination.
        return redirect()->route('account.notifications');
    }

    public function readAll(Request $request): RedirectResponse
    {
        // Both columns, because scopeUnread() and every unread badge read
        // is_read while the page's styling reads read_at.
        $request->user()->notifications()
            ->forCustomer()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
