<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    /**
     * Validate a credit note code for use in POS payment.
     * If a customer is attached to the cart, ownership is verified.
     */
    public function validate(Request $request, string $code): JsonResponse
    {
        $creditNote = CreditNote::where('secure_code', $code)
            ->orWhere('credit_note_number', $code)
            ->first();

        if (! $creditNote) {
            return response()->json([
                'valid'   => false,
                'message' => 'Credit note not found.',
            ], 404);
        }

        // Ownership check: if a customer is on the cart, credit note must belong to them
        $cartCustomerId = $request->session()->get('pos_cart')['customer']['id'] ?? null;
        if ($cartCustomerId && $creditNote->user_id !== $cartCustomerId) {
            return response()->json([
                'valid'   => false,
                'message' => 'This credit note does not belong to the current customer.',
            ], 403);
        }

        if ($creditNote->status !== 'active') {
            return response()->json([
                'valid'   => false,
                'message' => 'This credit note is no longer active.',
            ], 422);
        }

        if ($creditNote->expires_at && $creditNote->expires_at->isPast()) {
            return response()->json([
                'valid'   => false,
                'message' => 'This credit note has expired.',
            ], 422);
        }

        return response()->json([
            'valid'     => true,
            'id'        => $creditNote->id,
            'number'    => $creditNote->credit_note_number,
            'remaining' => (float) $creditNote->remaining_amount,
            'expires'   => $creditNote->expires_at?->format('d M Y'),
        ]);
    }
}
