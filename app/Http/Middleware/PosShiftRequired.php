<?php

namespace App\Http\Middleware;

use App\Models\StaffShift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosShiftRequired
{
    /**
     * Ensure an open shift exists before allowing billing operations.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shiftId = $request->session()->get('pos_shift_id');

        if (! $shiftId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No open shift. Please open a shift first.'], 403);
            }
            return redirect()->route('pos.shift.open');
        }

        // Verify shift is actually still open
        $shift = StaffShift::find($shiftId);
        if (! $shift || ! $shift->isOpen()) {
            $request->session()->forget('pos_shift_id');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Shift has been closed. Please open a new shift.'], 403);
            }
            return redirect()->route('pos.shift.open');
        }

        return $next($request);
    }
}
