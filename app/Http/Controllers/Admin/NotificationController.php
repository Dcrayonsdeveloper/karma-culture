<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Review;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Where each admin-audience notification type should land when it is opened.
     * Every entry is [the key in $notification->data holding the record id, the
     * model that confirms the record is still there, the route to open]. A
     * notification outlives the thing it announced, so a deleted enquiry or a
     * purged order must not 404 the admin out of their own bell -- a missing
     * record falls back to the list instead.
     */
    private const DESTINATIONS = [
        'new_order' => ['order_id', Order::class, 'admin.orders.show'],
        'new_return_request' => ['return_id', OrderReturn::class, 'admin.returns.show'],
        'new_review' => ['review_id', Review::class, 'admin.reviews.show'],
        'new_enquiry' => ['enquiry_id', Enquiry::class, 'admin.enquiries.show'],
        'new_ticket' => ['ticket_id', SupportTicket::class, 'admin.support-tickets.show'],
        'ticket_customer_reply' => ['ticket_id', SupportTicket::class, 'admin.support-tickets.show'],
    ];

    public function index(): View
    {
        // Straight off the query string this let ?per_page=999999 pull the
        // whole table into one render.
        $perPage = min(max((int) request()->integer('per_page', 10), 5), 100);

        // One notifications table serves both bells and every row is keyed only
        // by user_id, so an unfiltered listing here printed every customer's
        // order numbers, return numbers, refund amounts and ticket subjects --
        // in customer voice, with no owner name against them. This page shows
        // only the rows addressed to this admin's own admin bell.
        $filter = request()->input('filter') === 'unread' ? 'unread' : 'all';

        $mine = fn () => Notification::query()
            ->where('user_id', auth('admin')->id())
            ->forAdmin();

        $notifications = $mine()
            ->when($filter === 'unread', fn ($query) => $query->unread())
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $unreadCount = $mine()->unread()->count();

        return view('admin.notifications.index', compact('notifications', 'filter', 'unreadCount'));
    }

    public function read(Notification $notification): RedirectResponse
    {
        // Route model binding resolves any id in the table, and this route is a
        // GET, which LogAdminActions skips -- so before the guard a hand-typed
        // /admin/notifications/{id}/read silently cleared any customer's unread
        // state with nothing recorded in the audit log.
        abort_if(
            $notification->user_id !== auth('admin')->id()
                || $notification->audience !== Notification::AUDIENCE_ADMIN,
            403
        );

        $notification->markAsRead();

        // A new subscriber has no detail page of its own; the list is the record.
        if ($notification->type === 'new_newsletter_subscriber') {
            return redirect()->route('admin.newsletter.index');
        }

        [$key, $model, $route] = self::DESTINATIONS[$notification->type] ?? [null, null, null];
        $id = $key ? ($notification->data[$key] ?? null) : null;

        if ($id && $model::whereKey($id)->exists()) {
            return redirect()->route($route, $id);
        }

        return redirect()->route('admin.notifications');
    }

    public function readAll(): RedirectResponse
    {
        Notification::query()
            ->where('user_id', auth('admin')->id())
            ->forAdmin()
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
