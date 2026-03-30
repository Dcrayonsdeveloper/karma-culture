<?php

namespace App\Http\Controllers\Pos;

use App\Events\PosSaleCompleted;
use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\PosCashMovement;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * Complete a sale: create PosSale + items, deduct stock, clear cart.
     */
    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method'   => ['required', 'in:cash,card,upi,split'],
            'paid_amount'      => ['required', 'numeric', 'min:0'],
            'payment_ref'        => ['nullable', 'string', 'max:100'],
            'payment_details'    => ['nullable', 'array'],
            'credit_note_id'     => ['nullable', 'integer', 'exists:credit_notes,id'],
            'credit_note_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cart = $request->session()->get('pos_cart');

        if (! $cart || empty($cart['items'])) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $total = (float) $cart['total'];

        // Validate payment amount
        if ($validated['payment_method'] === 'cash') {
            if ($validated['paid_amount'] < $total) {
                return response()->json(['message' => 'Insufficient payment amount.'], 422);
            }
        }

        $staffId    = $request->session()->get('pos_staff_id');
        $storeId    = $request->session()->get('pos_store_id');
        $registerId = $request->session()->get('pos_register_id');

        try {
            $sale = DB::transaction(function () use ($cart, $validated, $staffId, $storeId, $registerId, $total) {
                // Create the sale
                $sale = PosSale::create([
                    'store_id'        => $storeId,
                    'register_id'     => $registerId,
                    'staff_id'        => $staffId,
                    'customer_id'     => $cart['customer']['id'] ?? null,
                    'subtotal'        => $cart['subtotal'],
                    'discount'        => $cart['discount'],
                    'tax'             => $cart['tax'],
                    'total'           => $total,
                    'paid_amount'     => $validated['paid_amount'],
                    'change_amount'   => max(0, $validated['paid_amount'] - $total),
                    'payment_method'  => $validated['payment_method'],
                    'payment_details' => $this->buildPaymentDetails($validated),
                    'status'          => 'completed',
                ]);

                $isInterstate = (bool) ($cart['is_interstate'] ?? false);

                // Create sale items & deduct stock
                foreach ($cart['items'] as $item) {
                    $taxAmount = 0;
                    $cgst = 0;
                    $sgst = 0;
                    $igst = 0;

                    if (($item['tax_rate'] ?? 0) > 0) {
                        $lineTotal = ($item['price'] * $item['quantity']) - ($item['discount'] ?? 0);
                        $taxAmount = round($lineTotal * $item['tax_rate'] / 100, 2);

                        if ($isInterstate) {
                            // Inter-state sale: entire tax is IGST
                            $igst = $taxAmount;
                        } else {
                            // Intra-state sale: split equally into CGST + SGST
                            $cgst = round($taxAmount / 2, 2);
                            $sgst = $taxAmount - $cgst; // remainder to avoid rounding loss
                        }
                    }

                    PosSaleItem::create([
                        'pos_sale_id'  => $sale->id,
                        'product_id'   => $item['product_id'],
                        'variant_id'   => $item['variant_id'] ?? null,
                        'barcode'      => $item['barcode'] ?? null,
                        'product_name' => $item['product_name'],
                        'hsn_code'     => $item['hsn_code'] ?? null,
                        'tax_rate'     => $item['tax_rate'] ?? 0,
                        'quantity'     => $item['quantity'],
                        'price'        => $item['price'],
                        'discount'     => $item['discount'] ?? 0,
                        'tax'          => $taxAmount,
                        'cgst'         => $cgst,
                        'sgst'         => $sgst,
                        'igst'         => $igst,
                        'total'        => $item['total'],
                    ]);

                    // Deduct stock
                    $this->deductStock($item);
                }

                // Increment coupon usage
                if (! empty($cart['coupon']['id'])) {
                    \App\Models\Coupon::where('id', $cart['coupon']['id'])->increment('times_used');
                }

                // Redeem credit note
                if (! empty($validated['credit_note_id']) && ($validated['credit_note_amount'] ?? 0) > 0) {
                    $cn = CreditNote::lockForUpdate()->find($validated['credit_note_id']);
                    if ($cn && $cn->isValid()) {
                        $redeemAmount = min($validated['credit_note_amount'], (float) $cn->remaining_amount);
                        $cn->remaining_amount -= $redeemAmount;
                        $cn->used_amount = ($cn->used_amount ?? 0) + $redeemAmount;
                        $cn->status = $cn->remaining_amount <= 0 ? 'fully_used' : 'partially_used';
                        $cn->save();

                        $cn->usages()->create([
                            'order_id' => null,
                            'amount'   => $redeemAmount,
                        ]);

                        // Record in payment details
                        $sale->payment_details = array_merge($sale->payment_details ?? [], [
                            'credit_note' => [
                                'id'     => $cn->id,
                                'number' => $cn->credit_note_number,
                                'amount' => $redeemAmount,
                            ],
                        ]);
                        $sale->save();
                    }
                }

                return $sale;
            });

            try {
                PosSaleCompleted::dispatch($sale);
            } catch (\Throwable $e) {
                \Log::warning('Failed to dispatch PosSaleCompleted event', ['error' => $e->getMessage()]);
            }

            try {
                AuditController::log($request, 'sale_completed', 'pos_sale', $sale->id, [
                    'new' => ['sale_number' => $sale->sale_number, 'total' => $sale->total, 'payment_method' => $sale->payment_method],
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Failed to log POS audit', ['error' => $e->getMessage()]);
            }

            // Record cash movement for this sale
            try {
                $shiftId = $request->session()->get('pos_shift_id');
                if ($shiftId) {
                    PosCashMovement::record(
                        $shiftId,
                        $staffId,
                        'sale',
                        (float) $sale->total,
                        'pos_sale',
                        $sale->id,
                        $sale->payment_method . ' — ' . $sale->sale_number
                    );
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to record cash movement', ['error' => $e->getMessage()]);
            }

            // Clear the cart
            $request->session()->put('pos_cart', [
                'items' => [], 'customer' => null, 'coupon' => null,
                'manual_discount' => null, 'subtotal' => 0, 'discount' => 0,
                'tax' => 0, 'total' => 0,
            ]);

            return response()->json([
                'success'      => true,
                'sale_number'  => $sale->sale_number,
                'change'       => max(0, $validated['paid_amount'] - $total),
                'receipt_url'  => route('pos.sale.receipt', $sale->id),
            ]);
        } catch (\RuntimeException $e) {
            // Stock race condition or business rule violation — show exact message
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('POS Sale failed: ' . $e->getMessage(), [
                'staff_id' => $staffId,
                'store_id' => $storeId,
                'cart'     => $cart,
            ]);

            return response()->json([
                'message' => 'Sale processing failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Show receipt for a completed sale.
     */
    public function receipt(Request $request, PosSale $sale)
    {
        $sale->load(['items.product', 'items.variant', 'store', 'staff.user', 'customer']);

        $storeId = $request->session()->get('pos_store_id');
        if ($sale->store_id !== $storeId) {
            abort(403);
        }

        return view('pos.receipt', compact('sale'));
    }

    /**
     * Return structured receipt data as JSON for the desktop EXE thermal printer.
     */
    public function receiptData(Request $request, PosSale $sale): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        if ($sale->store_id !== $storeId) {
            abort(403);
        }

        $sale->load(['items.product', 'items.variant', 'store', 'staff.user', 'customer']);

        $store = $sale->store;

        // Build GST breakup grouped by tax rate (handles both intra-state and inter-state)
        $gstGroups = [];
        foreach ($sale->items as $item) {
            $rate = (float) $item->tax_rate;
            if ($rate <= 0) continue;
            if (!isset($gstGroups[$rate])) {
                $gstGroups[$rate] = ['rate' => $rate, 'cgst' => 0, 'sgst' => 0, 'igst' => 0];
            }
            $gstGroups[$rate]['cgst'] += (float) $item->cgst;
            $gstGroups[$rate]['sgst'] += (float) $item->sgst;
            $gstGroups[$rate]['igst'] += (float) $item->igst;
        }

        // Payment reference for card/UPI
        $paymentDetails = $sale->payment_details ?? [];
        $paymentRef = $paymentDetails['reference'] ?? null;

        // Receipt footer from settings (falls back to default)
        $receiptFooter = \App\Models\Setting::get('pos_receipt_footer', 'Thank you for shopping with us!');
        $returnPolicy  = \App\Models\Setting::get('pos_return_policy', 'Exchange/Return within 7 days with receipt.');

        return response()->json([
            'store_name'     => $store->name ?? 'ForeverKids',
            'store_address'  => trim(implode(', ', array_filter([$store->address, $store->city, $store->state]))),
            'store_phone'    => $store->phone ?? '',
            'gstin'          => $store->gst_number ?? '',
            'sale_number'    => $sale->sale_number,
            'date'           => $sale->created_at->format('d/m/Y h:i A'),
            'cashier'        => $sale->staff?->user?->first_name ?? 'Staff',
            'customer'       => $sale->customer?->full_name,
            'items'          => $sale->items->map(fn ($item) => [
                'name'     => $item->product_name,
                'quantity' => (int) $item->quantity,
                'price'    => (float) $item->price,
                'discount' => (float) ($item->discount ?? 0),
                'tax'      => (float) $item->tax,
                'cgst'     => (float) $item->cgst,
                'sgst'     => (float) $item->sgst,
                'igst'     => (float) $item->igst,
                'total'    => (float) $item->total,
                'hsn'      => $item->hsn_code,
                'tax_rate' => (float) $item->tax_rate,
            ])->toArray(),
            'subtotal'        => (float) $sale->subtotal,
            'discount'        => (float) $sale->discount,
            'tax'             => (float) $sale->tax,
            'total'           => (float) $sale->total,
            'payment_method'  => $sale->payment_method,
            'payment_ref'     => $paymentRef,
            'payment_details' => $paymentDetails,
            'paid_amount'     => (float) $sale->paid_amount,
            'change_amount'   => (float) ($sale->change_amount ?? 0),
            'gst_breakup'     => array_values($gstGroups),
            'footer'          => $receiptFooter,
            'return_policy'   => $returnPolicy,
        ]);
    }

    /**
     * Build payment details JSON.
     */
    private function buildPaymentDetails(array $validated): array
    {
        $details = [];

        if ($validated['payment_method'] === 'split') {
            $details = $validated['payment_details'] ?? [];
        } elseif (in_array($validated['payment_method'], ['card', 'upi'])) {
            $details['reference'] = $validated['payment_ref'] ?? null;
        } elseif ($validated['payment_method'] === 'cash') {
            $details['received'] = $validated['paid_amount'];
        }

        return $details;
    }

    /**
     * Deduct stock for a sold item — throws if insufficient stock (race condition guard).
     */
    private function deductStock(array $item): void
    {
        if (! empty($item['variant_id'])) {
            $affected = ProductVariant::where('id', $item['variant_id'])
                ->where('stock_quantity', '>=', $item['quantity'])
                ->decrement('stock_quantity', $item['quantity']);
        } else {
            $affected = Product::where('id', $item['product_id'])
                ->where('stock_quantity', '>=', $item['quantity'])
                ->decrement('stock_quantity', $item['quantity']);
        }

        if (! $affected) {
            throw new \RuntimeException(
                "Insufficient stock for \"{$item['product_name']}\". Sale aborted."
            );
        }
    }
}
