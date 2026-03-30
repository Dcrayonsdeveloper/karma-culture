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
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

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
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Order cannot be cancelled at this stage',
            ], 422);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Order cancelled successfully',
        ]);
    }
}
