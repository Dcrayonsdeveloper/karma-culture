<?php

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with(['items.product:id,name,slug,images'])
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->assertOwned($request, $order);

        $order->load([
            'items.product:id,name,slug,images',
            'shippingAddress',
            'billingAddress',
            'payments',
            'shipments',
        ]);

        return response()->json([
            'data' => $order,
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->assertOwned($request, $order);

        // 409, not 422. Nothing about the REQUEST is wrong here - there is no
        // field to correct and no `errors` map to send - the order has simply
        // moved past the point where it can be cancelled, usually while the
        // caller was looking at a stale copy of it. The other two "already done"
        // refusals in this API (Review\ReviewController::store,
        // User\WishlistController::store) answer that same class of failure with
        // 409, and 422 is reserved for a body that failed validation, which is
        // what the framework itself returns on these routes. A client can now
        // branch on the status alone: 409 means refresh and look again, 422 means
        // read the field messages.
        //
        // A status is part of the published surface, not an implementation detail:
        // doc/api-documentation.md section 10.3 is what a client was written from,
        // so it now says 409 as well. Changing one without the other would only move
        // the failure - a client still branching on 422 falls through to its generic
        // handler and shows the caller nothing useful.
        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Order cannot be cancelled at this stage',
            ], 409);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Order cancelled successfully',
        ]);
    }

    /**
     * The one answer for an order that is not the caller's.
     *
     * Both checks used to be a bare abort(403), which serialises to
     * {"message":""} - a status with an empty body, so a client had nothing to
     * show and fell back to its own generic wording or to silence. The reason is
     * not a secret worth an empty body either: the caller has either sent a
     * stale order id or is probing somebody else's orders, and one sentence
     * answers both without confirming which. Being a body a client can render,
     * it is published too - doc/api-documentation.md sections 10.2 and 10.3 - so
     * it has to move with the page rather than drift away from it.
     */
    private function assertOwned(Request $request, Order $order): void
    {
        abort_if($order->user_id !== $request->user()->id, 403, 'You do not have access to this order.');
    }
}
