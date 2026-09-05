<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Scoped to the customer audience because an admin is a users row with
        // role = 'admin', so an admin's own notifications() relation carries the
        // store's admin alerts as well. This is the shopper-facing API.
        $notifications = $request->user()->notifications()
            ->forCustomer()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        // Was a bare abort(403), which serialises to {"message":""} - a status
        // with an empty body, so a client had nothing to show and fell back to
        // its own generic wording or to silence. The reason is not a secret worth
        // an empty body: the caller has either sent a stale notification id or is
        // poking at somebody else's bell, and one sentence answers both without
        // confirming which.
        abort_if(
            $notification->user_id !== $request->user()->id,
            403,
            'This notification is not yours.'
        );

        // Ownership is not enough on its own: an admin owns admin-audience rows
        // under the same user_id, and those belong to the admin bell.
        if ($notification->audience !== Notification::AUDIENCE_CUSTOMER) {
            abort(403);
        }

        // This used to be update(['read_at' => now()]), which left is_read
        // false. Notification::scopeUnread() and every unread badge and list
        // highlight in the product key off is_read, so reading a notification
        // through the API changed nothing the customer could actually see.
        // The model method writes both columns.
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        // Selecting on whereNull('read_at') meant this could never even repair
        // the rows the old markAsRead() had half-updated: they already carried a
        // read_at, so the bulk update skipped them while they went on showing as
        // unread everywhere else. Select on is_read - the column the rest of the
        // app believes - and write both.
        $request->user()->notifications()
            ->forCustomer()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
