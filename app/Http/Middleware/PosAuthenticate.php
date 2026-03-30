<?php

namespace App\Http\Middleware;

use App\Models\PosRegister;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosAuthenticate
{
    // Staff inactivity timeout in seconds (default 15 minutes; set pos_inactivity_timeout in settings)
    const INACTIVITY_TIMEOUT = 900;

    /**
     * Verify that the request comes from a registered POS terminal
     * AND has a valid staff session with no inactivity timeout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for valid staff session
        if (! $request->session()->has('pos_staff_id')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'POS session expired. Please log in.'], 401);
            }
            return redirect()->route('pos.login');
        }

        // Inactivity auto-logout: staff-level only (device stays registered)
        $timeout        = (int) \App\Models\Setting::get('pos_inactivity_timeout', self::INACTIVITY_TIMEOUT);
        $lastActivityAt = $request->session()->get('pos_last_activity', time());

        if ((time() - $lastActivityAt) > $timeout) {
            $request->session()->forget(['pos_staff_id', 'pos_staff_name', 'pos_staff_role', 'pos_shift_id', 'pos_last_activity']);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session timed out due to inactivity. Please log in again.', 'timeout' => true], 401);
            }
            return redirect()->route('pos.login')->with('error', 'You were logged out due to inactivity.');
        }

        // Refresh activity timestamp on every request
        $request->session()->put('pos_last_activity', time());

        // Check device is registered and active
        $deviceId = $request->session()->get('pos_device_id');
        if ($deviceId) {
            $register = PosRegister::where('device_id', $deviceId)->first();
            if (! $register || ! $register->isActive()) {
                $request->session()->forget(['pos_staff_id', 'pos_device_id', 'pos_store_id', 'pos_register_id', 'pos_shift_id', 'pos_last_activity']);
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Terminal deactivated. Contact admin.'], 403);
                }
                return redirect()->route('pos.login')->with('error', 'This terminal has been deactivated. Contact your administrator.');
            }
        }

        return $next($request);
    }
}
