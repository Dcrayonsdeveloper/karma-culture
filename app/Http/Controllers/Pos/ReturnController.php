<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\PosCashMovement;
use App\Models\PosSale;
use App\Models\Staff;
use App\Models\PosReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    /**
     * Returns page placeholder — returns are processed via modals from the billing screen.
     */
    public function index()
    {
        return response()->json(['message' => 'Use findSale to start a return.']);
    }

    /**
     * Find a sale by sale number or customer phone for return.
     */
    public function findSale(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 3) {
            return response()->json(['sales' => []]);
        }

        $storeId = $request->session()->get('pos_store_id');

        $sales = PosSale::with(['items', 'customer'])
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->where(function ($q) use ($query) {
                $q->where('sale_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', fn ($cq) => $cq->where('phone', 'like', "%{$query}%")
                      ->orWhere('first_name', 'like', "%{$query}%"));
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (PosSale $sale) => [
                'id'          => $sale->id,
                'sale_number' => $sale->sale_number,
                'date'        => $sale->created_at->format('d M Y, g:i A'),
                'total'       => (float) $sale->total,
                'customer'    => $sale->customer
                    ? trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? ''))
                    : 'Walk-in',
                'items'       => $sale->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'price'        => (float) $item->price,
                    'total'        => (float) $item->total,
                    'product_id'   => $item->product_id,
                    'variant_id'   => $item->variant_id,
                ]),
            ]);

        return response()->json(['sales' => $sales]);
    }

    /**
     * Process a return.
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pos_sale_id'    => ['required', 'integer', 'exists:pos_sales,id'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.reason'       => ['nullable', 'string', 'max:100'],
            'items.*.condition'    => ['nullable', 'in:unused_with_tags,used,defective'],
            'refund_method'  => ['required', 'in:cash,original_payment,credit_note'],
            'reason'         => ['nullable', 'string', 'max:500'],
            'authorized_by'  => ['nullable', 'integer', 'exists:staff,id'],  // manager who approved
        ]);

        $staffId = $request->session()->get('pos_staff_id');
        $storeId = $request->session()->get('pos_store_id');

        // Verify the authorizer is actually a manager or supervisor, not just any staff ID
        if (! empty($validated['authorized_by'])) {
            $isManager = Staff::where('id', $validated['authorized_by'])
                ->where('store_id', $storeId)
                ->whereIn('role', ['manager', 'supervisor'])
                ->exists();
            if (! $isManager) {
                return response()->json(['message' => 'The specified authorizer is not a manager or supervisor.'], 422);
            }
        }

        $sale = PosSale::with('items')
            ->where('id', $validated['pos_sale_id'])
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->firstOrFail();

        // Require manager authorization for returns above ₹500
        $returnThreshold = (float) \App\Models\Setting::get('pos_return_auth_threshold', 500);

        try {
            $result = DB::transaction(function () use ($sale, $validated, $staffId, $storeId, $returnThreshold) {
                $totalRefund = 0;
                $returnItems = [];

                foreach ($validated['items'] as $returnItemData) {
                    $saleItem = $sale->items->firstWhere('id', $returnItemData['sale_item_id']);
                    if (! $saleItem) continue;

                    // Reject (don't silently cap) if requested qty exceeds sold qty
                    if ($returnItemData['quantity'] > $saleItem->quantity) {
                        throw new \RuntimeException(
                            "Cannot return {$returnItemData['quantity']} units of \"{$saleItem->product_name}\" — only {$saleItem->quantity} were sold."
                        );
                    }

                    $qty = (int) $returnItemData['quantity'];

                    // Refund the proportional GST-inclusive amount actually paid per unit
                    $pricePerUnit = $saleItem->quantity > 0
                        ? (float) $saleItem->total / $saleItem->quantity
                        : (float) $saleItem->price;
                    $refundAmount = round($pricePerUnit * $qty, 2);
                    $totalRefund += $refundAmount;

                    $returnItems[] = [
                        'product_id'    => $saleItem->product_id,
                        'variant_id'    => $saleItem->variant_id,
                        'product_name'  => $saleItem->product_name,
                        'quantity'      => $qty,
                        'price'         => $saleItem->price,
                        'refund_amount' => $refundAmount,
                        'reason'        => $returnItemData['reason'] ?? null,
                        'condition'     => $returnItemData['condition'] ?? 'unused_with_tags',
                    ];

                    // Restore stock
                    if ($saleItem->variant_id) {
                        ProductVariant::where('id', $saleItem->variant_id)->increment('stock_quantity', $qty);
                    } else {
                        Product::where('id', $saleItem->product_id)->increment('stock_quantity', $qty);
                    }
                }

                // Manager authorization required for returns above threshold
                if ($totalRefund > $returnThreshold && empty($validated['authorized_by'])) {
                    throw new \RuntimeException(
                        "Returns above ₹{$returnThreshold} require manager authorization. Total: ₹{$totalRefund}."
                    );
                }

                // Create return record
                $creditNote = null;
                $return = PosReturn::create([
                    'pos_sale_id'   => $sale->id,
                    'store_id'      => $storeId,
                    'staff_id'      => $staffId,
                    'customer_id'   => $sale->customer_id,
                    'amount'        => $totalRefund,
                    'refund_method' => $validated['refund_method'],
                    'reason'        => $validated['reason'] ?? null,
                    'status'        => 'completed',
                    'type'          => 'return',
                    'authorized_by' => $validated['authorized_by'] ?? null,
                ]);

                // Create return line items
                foreach ($returnItems as $ri) {
                    $return->items()->create($ri);
                }

                // Generate credit note if refund method is credit_note
                if ($validated['refund_method'] === 'credit_note' && $sale->customer_id) {
                    $creditNote = CreditNote::create([
                        'user_id'          => $sale->customer_id,
                        'amount'           => $totalRefund,
                        'remaining_amount' => $totalRefund,
                        'status'           => 'active',
                        'expires_at'       => now()->addYear(),
                    ]);

                    $return->update(['credit_note_id' => $creditNote->id]);
                }

                return [
                    'return'       => $return,
                    'refund'       => $totalRefund,
                    'credit_note'  => $creditNote?->credit_note_number,
                ];
            });

            // Record cash movement for cash refunds
            if ($validated['refund_method'] === 'cash') {
                try {
                    $shiftId = $request->session()->get('pos_shift_id');
                    if ($shiftId) {
                        PosCashMovement::record(
                            $shiftId,
                            $staffId,
                            'refund',
                            (float) $result['refund'],
                            'pos_return',
                            $result['return']->id,
                            'Return ' . $result['return']->return_number
                        );
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed to record refund cash movement', ['error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success'       => true,
                'return_number' => $result['return']->return_number,
                'refund_amount' => $result['refund'],
                'credit_note'   => $result['credit_note'],
                'message'       => 'Return processed successfully.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Return processing failed. ' . $e->getMessage(),
            ], 500);
        }
    }
}
