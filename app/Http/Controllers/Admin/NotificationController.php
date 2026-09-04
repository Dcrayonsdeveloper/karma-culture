<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Review;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        // The three status alerts SendOrderNotification raises through
        // ADMIN_ALERTS. They carry order_id exactly as new_order does, but had
        // no entry here, so opening one - from the bell, from the list, and now
        // from a toast - dropped the admin back on the notifications list
        // instead of on the order it was telling them about.
        'order_cancelled' => ['order_id', Order::class, 'admin.orders.show'],
        'order_returned' => ['order_id', Order::class, 'admin.orders.show'],
        'order_on_hold' => ['order_id', Order::class, 'admin.orders.show'],
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

    /**
     * How far back a poll may look, and how much one poll may carry.
     *
     * The lookback is what stops a client - or someone hand-editing the query
     * string - asking for `since=1970` and being handed a screenful of ancient
     * toasts. A tab that slept through the night still recovers everything from
     * the last day, and anything older is still sitting on this page.
     */
    private const UPDATES_LOOKBACK_HOURS = 24;

    private const UPDATES_LIMIT = 25;

    /**
     * The grace the cursor is rewound by on every answer.
     *
     * A notification is written inside whatever transaction its trigger ran in,
     * so a row can be timestamped a second or two BEFORE the poll that misses
     * it and only become visible afterwards. Handing the client back a cursor a
     * few seconds behind `now` means the next poll re-reads that overlap, and
     * the client throws away anything whose uuid it has already seen. Without
     * it, a notification written during a slow checkout would silently never
     * reach the bell.
     */
    private const UPDATES_OVERLAP_SECONDS = 5;

    /**
     * What has arrived for this admin since they last looked.
     *
     * The admin shell polls this every ten seconds. Everything the bell shows
     * used to be computed in a @php block in admin.partials.header, so the only
     * way to see a new notification was to load another page; this is the same
     * two questions - "how many are unread" and "what is new" - asked without
     * one.
     *
     * Reading is not the same as being read: nothing here writes is_read. That
     * stays where it was, on admin.notifications.read and the mark-all form.
     */
    public function updates(Request $request): JsonResponse
    {
        $adminId = auth('admin')->id();

        $mine = fn () => Notification::query()
            ->where('user_id', $adminId)
            ->forAdmin();

        $now = now();

        // Every answer carries the cursor for the next question, rewound by the
        // overlap. The client never sends its own clock: a browser minutes out
        // of step with the server would otherwise ask for a window that has
        // already passed, or one that has not started.
        $nextSince = $now->copy()->subSeconds(self::UPDATES_OVERLAP_SECONDS);

        $since = $this->updatesCursor($request->input('since'), $now);

        // No usable cursor means this client has no baseline yet. Answer with
        // the count and a cursor and let the NEXT poll be the first that can
        // find anything: returning rows here is exactly what would make a
        // freshly opened tab announce the whole backlog.
        $rows = $since === null
            ? collect()
            // Newest first so that a tab which slept through a busy morning
            // wakes to the most recent notifications rather than to the oldest
            // ones still inside the window; reversed below so the client can
            // draw them in the order they happened.
            : $mine()
                ->where('created_at', '>=', $since)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::UPDATES_LIMIT)
                ->get(['id', 'uuid', 'type', 'title', 'content', 'channel', 'is_read', 'created_at']);

        $payload = [
            'notifications' => [],
            // Counted AFTER the rows, so the badge can never be behind the rows
            // in the same answer. The other order left a ten-second window in
            // which the bell said 3 while the list had just gained a fourth.
            'unread_count' => $mine()->unread()->count(),
            'next_since' => $nextSince->toIso8601String(),
            // The cursor moves to `now` whatever happens, so when a burst fills
            // the answer the rows beyond it are not carried anywhere - they are
            // on /admin/notifications, and the unread count above still includes
            // them. Saying so lets the client count honestly ("25+ new") rather
            // than announce a number it was never given.
            'truncated' => $rows->count() >= self::UPDATES_LIMIT,
        ];

        $payload['notifications'] = $rows
            ->reverse()
            ->map(fn (Notification $notification) => [
                'id' => $notification->id,
                // The client de-duplicates on this. Two genuine notifications
                // can carry identical wording - two enquiries with the same
                // subject - so the text can never be the identity.
                'uuid' => $notification->uuid,
                'type' => $notification->type,
                'title' => $notification->title,
                'content' => $notification->content,
                // Carried so a row the poller inserts on the notifications page
                // wears the same badges as one the server drew.
                'channel' => $notification->channel,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at->toIso8601String(),
                'created_at_for_humans' => $notification->created_at->diffForHumans(),
                // Where this row opens, decided by DESTINATIONS from the
                // notification's own data - never by matching on its wording.
                'url' => route('admin.notifications.read', $notification),
            ])
            ->values()
            ->all();

        return response()->json($payload);
    }

    /**
     * The window a poll may ask for, or null when the client has no baseline.
     */
    private function updatesCursor(mixed $since, \Illuminate\Support\Carbon $now): ?\Illuminate\Support\Carbon
    {
        // A timestamp is about twenty-five characters. The bound is here so that
        // nothing longer than a timestamp ever reaches Carbon's parser, which
        // accepts a great deal more than one - relative expressions included.
        if (! is_string($since) || $since === '' || strlen($since) > 64) {
            return null;
        }

        // "+05:30" travels through a query string as "%2B05:30", and a "+" that
        // was not encoded arrives as a space. The poller encodes properly, but a
        // cursor mangled that way parses as nothing and quietly puts the client
        // back on a baseline - no rows, no error, forever - which is a great deal
        // worse than a rejected request. Put the sign back.
        $since = preg_replace('/ (\d{2}:?\d{2})$/', '+$1', $since);

        try {
            $cursor = \Illuminate\Support\Carbon::parse($since);
        } catch (\Exception) {
            return null;
        }

        $floor = $now->copy()->subHours(self::UPDATES_LOOKBACK_HOURS);

        // A cursor from the future would answer nothing for as long as the
        // client kept sending it, so it is pulled back to the present too.
        return $cursor->lessThan($floor)
            ? $floor
            : ($cursor->greaterThan($now) ? $now : $cursor);
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
