<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    /**
     * List transfers for the current store (incoming + outgoing).
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');

        $transfers = StoreTransfer::with(['fromStore:id,name', 'toStore:id,name', 'items', 'requestedBy.user:id,first_name,name'])
            ->where(fn ($q) => $q->where('from_store_id', $storeId)->orWhere('to_store_id', $storeId))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (StoreTransfer $t) => [
                'id'              => $t->id,
                'transfer_number' => $t->transfer_number,
                'from_store'      => $t->fromStore->name,
                'to_store'        => $t->toStore->name,
                'direction'       => $t->from_store_id === $storeId ? 'outgoing' : 'incoming',
                'status'          => $t->status,
                'items_count'     => $t->items->count(),
                'total_qty'       => $t->items->sum('quantity_requested'),
                'requested_by'    => $t->requestedBy?->user?->first_name ?? 'Staff',
                'notes'           => $t->notes,
                'rejection_reason' => $t->rejection_reason,
                'created_at'      => $t->created_at->format('d M Y, g:i A'),
                'items'           => $t->items->map(fn ($i) => [
                    'product_name'       => $i->product_name,
                    'sku'                => $i->sku,
                    'quantity_requested' => $i->quantity_requested,
                    'quantity_sent'      => $i->quantity_sent,
                    'quantity_received'  => $i->quantity_received,
                ]),
            ]);

        $stores = Store::where('id', '!=', $storeId)
            ->where('is_active', true)
            ->get(['id', 'name', 'code']);

        return response()->json([
            'transfers' => $transfers,
            'stores'    => $stores,
        ]);
    }

    /**
     * Create a new transfer request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_store_id'            => ['required', 'integer', 'exists:stores,id'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id'     => ['nullable', 'integer'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ]);

        $storeId = $request->session()->get('pos_store_id');
        $staffId = $request->session()->get('pos_staff_id');

        if ($validated['to_store_id'] == $storeId) {
            return response()->json(['message' => 'Cannot transfer to the same store.'], 422);
        }

        $transfer = DB::transaction(function () use ($validated, $storeId, $staffId) {
            $transfer = StoreTransfer::create([
                'from_store_id' => $storeId,
                'to_store_id'   => $validated['to_store_id'],
                'requested_by'  => $staffId,
                'status'        => 'pending',
                'notes'         => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $variant = isset($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;

                $transfer->items()->create([
                    'product_id'         => $item['product_id'],
                    'variant_id'         => $item['variant_id'] ?? null,
                    'product_name'       => $product->name . ($variant ? ' - ' . $variant->name : ''),
                    'sku'                => $variant->sku ?? $product->sku ?? '',
                    'quantity_requested' => $item['quantity'],
                ]);
            }

            return $transfer;
        });

        return response()->json([
            'success'         => true,
            'transfer_number' => $transfer->transfer_number,
            'message'         => 'Transfer request created.',
        ]);
    }

    /**
     * Approve a transfer request (receiving store manager).
     */
    public function approve(Request $request, StoreTransfer $transfer): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $staffId = $request->session()->get('pos_staff_id');

        if ($transfer->to_store_id !== $storeId && $transfer->from_store_id !== $storeId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer is not pending.'], 422);
        }

        $transfer->update([
            'status'      => 'approved',
            'approved_by' => $staffId,
        ]);

        return response()->json(['success' => true, 'message' => 'Transfer approved.']);
    }

    /**
     * Reject a transfer request.
     */
    public function reject(Request $request, StoreTransfer $transfer): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $storeId = $request->session()->get('pos_store_id');

        if ($transfer->to_store_id !== $storeId && $transfer->from_store_id !== $storeId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (! in_array($transfer->status, ['pending', 'approved'])) {
            return response()->json(['message' => 'Cannot reject this transfer.'], 422);
        }

        $transfer->update([
            'status'           => 'rejected',
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Transfer rejected.']);
    }

    /**
     * Complete transfer — deduct from source, add to destination.
     */
    public function complete(Request $request, StoreTransfer $transfer): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');

        if ($transfer->from_store_id !== $storeId) {
            return response()->json(['message' => 'Only the sending store can complete a transfer.'], 403);
        }

        if ($transfer->status !== 'approved') {
            return response()->json(['message' => 'Transfer must be approved first.'], 422);
        }

        try {
            DB::transaction(function () use ($transfer) {
                foreach ($transfer->items as $item) {
                    $qty = $item->quantity_requested;

                    if ($item->variant_id) {
                        // Deduct from source variant — fail hard if insufficient stock
                        $deducted = ProductVariant::where('id', $item->variant_id)
                            ->where('stock_quantity', '>=', $qty)
                            ->decrement('stock_quantity', $qty);

                        if (! $deducted) {
                            throw new \RuntimeException(
                                "Insufficient stock for variant #{$item->variant_id} ({$item->product_name}). Transfer aborted."
                            );
                        }

                        // Increment at destination (same variant — global stock means net-zero,
                        // but this correctly models the physical movement between locations)
                        ProductVariant::where('id', $item->variant_id)
                            ->increment('stock_quantity', $qty);

                    } else {
                        $deducted = Product::where('id', $item->product_id)
                            ->where('stock_quantity', '>=', $qty)
                            ->decrement('stock_quantity', $qty);

                        if (! $deducted) {
                            throw new \RuntimeException(
                                "Insufficient stock for product #{$item->product_id} ({$item->product_name}). Transfer aborted."
                            );
                        }

                        Product::where('id', $item->product_id)
                            ->increment('stock_quantity', $qty);
                    }

                    $item->update([
                        'quantity_sent'     => $qty,
                        'quantity_received' => $qty,
                    ]);
                }

                $transfer->update(['status' => 'completed']);
            });

            return response()->json(['success' => true, 'message' => 'Transfer completed. Stock updated.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Transfer failed: ' . $e->getMessage()], 500);
        }
    }
}
