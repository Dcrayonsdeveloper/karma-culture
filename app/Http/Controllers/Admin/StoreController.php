<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreController extends Controller
{
    /**
     * One rule set for both create and edit, so the two cannot drift apart.
     *
     * This form used to validate its contact fields as 'nullable|string|max:20'
     * and 'nullable|email', so "Yucgguctu65_533" saved cleanly as a phone number
     * and an address with no TLD reached the column. Both now go through the
     * shared rules in {@see V}, the same ones the customer and site-settings
     * forms already use:
     *
     *  - phone: {@see IndianMobile} - ten digits opening 6-9, +91/0 prefixes and
     *    spacing tolerated, repdigits refused. Persisted normalised (below), so
     *    the column holds the same bare-digit shape as users.phone.
     *  - email: 'email:strict' (Egulias NoRFCWarnings). Plain 'email' is
     *    RFC-permissive and waves through "x@gmail" with no TLD, which is half
     *    of what the old rule let past.
     *
     * strictShape is deliberately NOT passed to V::email(): a store's address is
     * a business contact being recorded, not an account being minted, so this
     * matches HomepageController's contact_email rather than signup.
     *
     * The name is lettersOnly: it was V::text, which took "Store #1!!" and any
     * other punctuation soup. Letters and spaces only was the shape asked for,
     * and it is the same PersonName rule the contact form uses - so a store
     * called "Store 2" is refused. Widen this rather than the column if
     * numbered branches are ever needed.
     */
    private function rules(?Store $store = null): array
    {
        return [
            'name' => V::name(required: true, min: 2, max: 255, lettersOnly: true),

            // stores.code is varchar(20) and UNIQUE. The old max:50 was accepted
            // here and then truncated (or rejected) by MySQL - the same bug
            // InventoryLocationController already carries a note about.
            //
            // The charset is wider than that controller's [A-Za-z0-9_-] on
            // purpose: this rule is going onto a table that already has rows,
            // and a legacy code written "MAIN STORE" or "KK/DEL/01" must stay
            // editable. It still has to open on a letter or digit, which is
            // what keeps emoji and punctuation soup out.
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9][A-Za-z0-9 _\-\/]*$/',
                Rule::unique('stores', 'code')->ignore($store?->id),
            ],

            'address' => V::addressLine(required: false, max: 255),
            'phone' => V::mobile(required: false),
            // 200, not the rule's 255 default: the column is varchar(255), so
            // this is a deliberate ceiling rather than a schema limit.
            'email' => V::email(required: false, max: 200),
            'is_active' => V::boolean(),
        ];
    }

    private function messages(): array
    {
        return [
            'code.regex' => 'The code must start with a letter or number and may then contain letters, numbers, spaces, hyphens, underscores and slashes.',
        ];
    }

    /**
     * Store the phone as the bare ten digits, never the decoration the admin
     * typed. "+91 98765 43210" and "098765 43210" are the same store line, and
     * a column holding three spellings of it cannot be searched or dialled
     * programmatically.
     */
    private function normalize(array $validated): array
    {
        if (array_key_exists('phone', $validated)) {
            $validated['phone'] = IndianMobile::normalize($validated['phone']);
        }

        return $validated;
    }

    public function index(): View
    {
        $perPage = request()->input('per_page', 10);
        $stores = Store::orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.stores.index', compact('stores'));
    }

    public function create(): View
    {
        return view('admin.stores.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        Store::create($this->normalize($validated));

        return redirect()->route('admin.stores.index')->with('success', 'Store created');
    }

    public function edit(Store $store): View
    {
        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $validated = $request->validate($this->rules($store), $this->messages());

        $store->update($this->normalize($validated));

        return redirect()->route('admin.stores.index')->with('success', 'Store updated');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $store->delete();

        return redirect()->route('admin.stores.index')->with('success', 'Store deleted');
    }
}
