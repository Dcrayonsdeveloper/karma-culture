<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();

        return view('account.addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('account.addresses.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            // Was min:2|max:255 with no character rule, which let
            // "!%@#%$ @FDASDF" through as a recipient name and a 200-character
            // run of junk through as a street. V::name() applies PersonName
            // (Unicode letters, marks and the separators real names use); the
            // closure guards the two varchar(50) columns this one field is
            // split across, which max:255 alone did not.
            'name' => [
                ...V::name(max: 100),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $parts = explode(' ', trim($value), 2);

                    if (mb_strlen($parts[0]) > 50 || mb_strlen(trim($parts[1] ?? '')) > 50) {
                        $fail('Please enter a first and last name, each 50 characters or fewer.');
                    }
                },
            ],
            'phone' => V::mobile(),
            'address_line1' => V::addressLine(),
            'address_line2' => V::addressLine(required: false),
            'city' => V::name(max: 100),
            'state' => V::name(max: 100),
            'postal_code' => V::pincode(),
            'country' => 'required|string|size:2|alpha',
            'label' => V::text(required: false, max: 50),
            'is_default' => V::boolean(),
        ], [
            'postal_code.regex' => 'Please enter a valid 6-digit PIN code.',
        ]);

        $nameParts = explode(' ', $request->name, 2);

        $data = [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $request->phone,
            'address_line_1' => $request->address_line1,
            'address_line_2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'label' => $request->label,
            'is_default' => $request->boolean('is_default'),
        ];

        // If setting as default, unset other defaults
        if ($data['is_default']) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        // If this is the first address, make it default
        if ($request->user()->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $address = $request->user()->addresses()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Address added successfully.', 'address_id' => $address->id]);
        }

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address added successfully.');
    }

    public function edit(Request $request, UserAddress $address): View
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        return view('account.addresses.edit', compact('address'));
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $request->validate([
            // Was min:2|max:255 with no character rule, which let
            // "!%@#%$ @FDASDF" through as a recipient name and a 200-character
            // run of junk through as a street. V::name() applies PersonName
            // (Unicode letters, marks and the separators real names use); the
            // closure guards the two varchar(50) columns this one field is
            // split across, which max:255 alone did not.
            'name' => [
                ...V::name(max: 100),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $parts = explode(' ', trim($value), 2);

                    if (mb_strlen($parts[0]) > 50 || mb_strlen(trim($parts[1] ?? '')) > 50) {
                        $fail('Please enter a first and last name, each 50 characters or fewer.');
                    }
                },
            ],
            'phone' => V::mobile(),
            'address_line1' => V::addressLine(),
            'address_line2' => V::addressLine(required: false),
            'city' => V::name(max: 100),
            'state' => V::name(max: 100),
            'postal_code' => V::pincode(),
            'country' => 'required|string|size:2|alpha',
            'label' => V::text(required: false, max: 50),
            'is_default' => V::boolean(),
        ], [
            'postal_code.regex' => 'Please enter a valid 6-digit PIN code.',
        ]);

        $nameParts = explode(' ', $request->name, 2);

        $data = [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $request->phone,
            'address_line_1' => $request->address_line1,
            'address_line_2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'label' => $request->label,
            'is_default' => $request->boolean('is_default'),
        ];

        // If setting as default, unset other defaults
        if ($data['is_default']) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address updated successfully.');
    }

    public function destroy(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $wasDefault = $address->is_default;

        $address->delete();

        // If deleted address was default, make another address default
        if ($wasDefault) {
            $newDefault = $request->user()->addresses()->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address deleted successfully.');
    }
}
