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
        $notifications = $request->user()->notifications()
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

        $notification->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
