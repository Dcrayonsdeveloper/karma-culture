<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\View\View;

class FlashSaleController extends Controller
{
    /**
     * The public page for a sale.
     *
     * The popup used to send shoppers to the home page with a query string
     * nothing read, so a sale advertising "5 products" gave them no way to see
     * which five.
     */
    public function show(FlashSale $flashSale): View
    {
        abort_unless($flashSale->is_active, 404);

        $flashSale->load(['products' => fn ($q) => $q->where('is_active', true)
            ->with(['category:id,name', 'brand:id,name', 'primaryImage'])]);

        $hasEnded = $flashSale->ends_at && $flashSale->ends_at->isPast();
        $hasStarted = ! $flashSale->starts_at || $flashSale->starts_at->isPast();

        return view('flash-sales.show', [
            'flashSale' => $flashSale,
            'isLive' => $hasStarted && ! $hasEnded,
            'hasEnded' => $hasEnded,
            'remainingSeconds' => $hasEnded ? 0 : max(0, now()->diffInSeconds($flashSale->ends_at, false)),
        ]);
    }
}
