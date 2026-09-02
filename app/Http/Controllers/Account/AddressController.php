<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressController extends Controller
{
    /**
     * The states and union territories the address form offers.
     *
     * The list used to live inside the two blades, which meant the server
     * accepted any name-shaped string for a state - "Narnia" saved cleanly and
     * only the courier found out. Both views now render this constant and both
     * writes validate against it, so the option list and the rule cannot drift.
     */
    public const STATES = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
        'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
        'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
        'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
    ];

    /** The only country the store ships to, and the only option rendered. */
    public const COUNTRIES = ['IN'];

    /** The three buttons the label picker offers. */
    public const LABELS = ['home', 'office', 'other'];

    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();

        return view('account.addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('account.addresses.create', ['states' => self::STATES]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules(), $this->messages(), $this->attributeNames());

        $data = $this->attributes($request);

        // If this is the first address, make it default
        if ($request->user()->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $address = $request->user()->addresses()->create($data);

        // If setting as default, unset the other defaults. Done after the
        // insert and excluding this row, so the address just marked default
        // cannot be cleared by its own update.
        if ($address->is_default) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Address added successfully.', 'address_id' => $address->id]);
        }

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address added successfully.');
    }

    public function edit(Request $request, UserAddress $address): View
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        return view('account.addresses.edit', ['address' => $address, 'states' => self::STATES]);
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $request->validate($this->rules(), $this->messages(), $this->attributeNames());

        $data = $this->attributes($request);

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

    /**
     * One rule set for both writes - the create and edit forms post identical
     * fields, and keeping two copies of the rules is how they drift apart.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
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
            // A select, so the value is whatever the client sent: checked
            // against the list the form actually offers rather than merely
            // being name-shaped.
            'state' => V::option(self::STATES),
            'postal_code' => V::pincode(),
            'country' => V::option(self::COUNTRIES),
            // Posted by a hidden input that the label buttons drive; only the
            // three labels those buttons can set are accepted.
            'label' => V::option(self::LABELS, required: false),
            'is_default' => V::boolean(),
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'postal_code.regex' => 'Please enter a valid 6-digit PIN code.',
            'state.in' => 'Please choose a state from the list.',
            'country.in' => 'We currently deliver within India only.',
            'label.in' => 'Please choose Home, Office or Other.',
        ];
    }

    /**
     * Field names as the form labels them, so a message reads "The address
     * line 1 ..." rather than "The address_line1 ...".
     *
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'name' => 'full name',
            'address_line1' => 'address line 1',
            'address_line2' => 'address line 2',
            'postal_code' => 'PIN code',
            'is_default' => 'default address',
        ];
    }

    /**
     * The column values for an already-validated request.
     *
     * @return array<string, mixed>
     */
    private function attributes(Request $request): array
    {
        $nameParts = explode(' ', trim((string) $request->input('name')), 2);

        return [
            'first_name' => $nameParts[0],
            'last_name' => trim($nameParts[1] ?? ''),
            // Store the bare ten digits rather than the "+91 98765 43210" the
            // form tolerates, so the column holds one shape and the courier and
            // the SMS gateway get a number they can actually dial.
            'phone' => IndianMobile::normalize($request->input('phone')),
            'address_line_1' => $request->input('address_line1'),
            'address_line_2' => $request->input('address_line2'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'label' => $request->input('label'),
            'is_default' => $request->boolean('is_default'),
        ];
    }
}
