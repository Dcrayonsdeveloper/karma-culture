<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Search customers by name, phone, or email.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = User::where('role', 'customer')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('first_name')
            ->limit(15)
            ->get()
            ->map(fn (User $u) => [
                'id'    => $u->id,
                'name'  => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'phone' => $u->phone,
                'email' => $u->email,
            ]);

        return response()->json(['customers' => $customers]);
    }

    /**
     * Create a quick customer record for POS.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        // Check if customer with this phone already exists
        $existing = User::where('phone', $validated['phone'])->first();
        if ($existing) {
            return response()->json([
                'customer' => [
                    'id'    => $existing->id,
                    'name'  => trim(($existing->first_name ?? '') . ' ' . ($existing->last_name ?? '')),
                    'phone' => $existing->phone,
                    'email' => $existing->email,
                ],
                'message' => 'Customer already exists with this phone number.',
            ]);
        }

        // Parse name into first/last
        $nameParts = explode(' ', $validated['name'], 2);

        $customer = User::create([
            'first_name' => $nameParts[0],
            'last_name'  => $nameParts[1] ?? '',
            'phone'      => $validated['phone'],
            'email'      => $validated['email'] ?? $validated['phone'] . '@pos.local',
            'password'   => Hash::make(Str::random(16)),
            'role'       => 'customer',
        ]);

        return response()->json([
            'customer' => [
                'id'    => $customer->id,
                'name'  => $validated['name'],
                'phone' => $customer->phone,
                'email' => $validated['email'] ?? null,
            ],
        ]);
    }
}
