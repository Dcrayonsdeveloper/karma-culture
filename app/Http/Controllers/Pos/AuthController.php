<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosRegister;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the POS login screen.
     */
    public function showLogin(Request $request)
    {
        // If already logged in with valid session, redirect
        if ($request->session()->has('pos_staff_id')) {
            if ($request->session()->has('pos_shift_id')) {
                return redirect()->route('pos.dashboard');
            }
            return redirect()->route('pos.shift.open');
        }

        $deviceId = $request->session()->get('pos_device_id');
        $register = null;

        if ($deviceId) {
            $register = PosRegister::with('store')->where('device_id', $deviceId)->where('status', 'active')->first();
        }

        return view('pos.login', compact('register', 'deviceId'));
    }

    /**
     * Register this device as a POS terminal.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:50'],
        ]);

        $register = PosRegister::with('store')
            ->where('device_id', $validated['device_id'])
            ->first();

        if (! $register) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal not found. Ask your admin to register this device.',
            ], 404);
        }

        if (! $register->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This terminal is inactive. Contact your administrator.',
            ], 403);
        }

        // Store device_id in session
        $request->session()->put('pos_device_id', $register->device_id);
        $request->session()->put('pos_store_id', $register->store_id);
        $request->session()->put('pos_register_id', $register->id);

        return response()->json([
            'success' => true,
            'terminal' => $register->name,
            'store'    => $register->store->name,
        ]);
    }

    /**
     * Authenticate staff via PIN.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:6'],
        ]);

        $storeId = $request->session()->get('pos_store_id');

        if (! $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal not registered. Please register this device first.',
            ], 400);
        }

        // Lockout key is per-device (not just per-IP) so staff on shared terminals aren't all locked out together
        $deviceId    = $request->session()->get('pos_device_id', 'unknown');
        $attemptsKey = 'pos_login_attempts_' . $deviceId . '_' . $request->ip();
        $attempts    = (int) cache()->get($attemptsKey, 0);

        if ($attempts >= 5) {
            $ttl = cache()->get($attemptsKey . '_ttl', 0);
            $remaining = max(0, 30 - (time() - $ttl));

            return response()->json([
                'success'   => false,
                'message'   => "Too many failed attempts. Try again in {$remaining} seconds.",
                'locked'    => true,
                'remaining' => $remaining,
            ], 429);
        }

        // Find staff by PIN at this store
        $staff = Staff::with('user')
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->get()
            ->first(fn (Staff $s) => Hash::check($validated['pin'], $s->pin));

        if (! $staff) {
            // Increment failed attempts
            cache()->put($attemptsKey, $attempts + 1, 300); // 5 min window
            cache()->put($attemptsKey . '_ttl', time(), 300);

            return response()->json([
                'success'  => false,
                'message'  => 'Invalid PIN.',
                'attempts' => $attempts + 1,
            ], 401);
        }

        // Clear login attempts on success
        cache()->forget($attemptsKey);
        cache()->forget($attemptsKey . '_ttl');

        // Create POS session
        $request->session()->put('pos_staff_id', $staff->id);
        $request->session()->put('pos_staff_name', $staff->user->first_name ?? $staff->user->name ?? 'Staff');
        $request->session()->put('pos_staff_role', $staff->role);

        // Update register last_sync
        PosRegister::where('id', $request->session()->get('pos_register_id'))
            ->update(['last_sync_at' => now()]);

        // Check for open shift
        $openShift = $staff->shifts()
            ->where('store_id', $storeId)
            ->where('status', 'open')
            ->latest('shift_start')
            ->first();

        if ($openShift) {
            $request->session()->put('pos_shift_id', $openShift->id);
        }

        return response()->json([
            'success'    => true,
            'staff_name' => $request->session()->get('pos_staff_name'),
            'role'       => $staff->role,
            'has_shift'  => (bool) $openShift,
            'redirect'   => $openShift ? route('pos.dashboard') : route('pos.shift.open'),
        ]);
    }

    /**
     * Logout from POS.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget([
            'pos_staff_id',
            'pos_staff_name',
            'pos_staff_role',
            'pos_shift_id',
        ]);

        return response()->json([
            'success'  => true,
            'redirect' => route('pos.login'),
        ]);
    }

    /**
     * Validate a manager/supervisor PIN for restricted actions.
     */
    public function authorizeAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin'    => ['required', 'string', 'min:4', 'max:6'],
            'action' => ['required', 'string', 'max:50'],
        ]);

        $storeId = $request->session()->get('pos_store_id');

        // Find a supervisor or manager with this PIN at this store
        $manager = Staff::with('user')
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->whereIn('role', ['manager', 'supervisor'])
            ->whereNotNull('pin')
            ->get()
            ->first(fn (Staff $s) => Hash::check($validated['pin'], $s->pin));

        if (! $manager) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid manager PIN.',
            ], 401);
        }

        return response()->json([
            'success'       => true,
            'authorized_by' => $manager->id,
            'manager_name'  => $manager->user->first_name ?? 'Manager',
        ]);
    }
}
