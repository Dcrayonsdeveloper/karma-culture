<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return response()->json([
            'data' => $addresses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($validated);

        return response()->json([
            'message' => 'Address created successfully',
            'data' => $address,
        ], 201);
    }

    public function show(Request $request, UserAddress $address): JsonResponse
    {
        $this->assertOwned($request, $address);

        return response()->json([
            'data' => $address,
        ]);
    }

    public function update(Request $request, UserAddress $address): JsonResponse
    {
        $this->assertOwned($request, $address);

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated successfully',
            'data' => $address,
        ]);
    }

    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        $this->assertOwned($request, $address);

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully',
        ]);
    }

    /**
     * The one answer for an address book entry that is not the caller's.
     *
     * All three checks used to be a bare abort(403), which serialises to
     * {"message":""} - a status with an empty body, so a client had nothing to
     * show and fell back to its own generic wording or to silence. The reason is
     * not a secret worth an empty body either: the caller has either sent an
     * address id they have since deleted or is probing somebody else's address
     * book, and one sentence answers both without confirming which.
     */
    private function assertOwned(Request $request, UserAddress $address): void
    {
        abort_if($address->user_id !== $request->user()->id, 403, 'You do not have access to this address.');
    }
}
