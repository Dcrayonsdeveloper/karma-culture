<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Category;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    /** The four discount mechanics the model and the storefront know how to apply. */
    private const TYPES = ['percentage', 'fixed', 'free_shipping', 'buy_x_get_y'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(self::TYPES)],
            // Tabs on the index. Anything else is not a filter, it is a typo or
            // a probe. The keys live on the model so the tab strip, the filter
            // and the badge cannot drift apart again.
            'status' => ['nullable', Rule::in(array_keys(Coupon::STATUSES))],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        // An unbounded per_page let a single request ask for every coupon in the
        // table, which is a denial of service dressed up as a page size.
        $perPage = $filters['per_page'] ?? 10;

        $query = Coupon::query();

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }

        // The old $stats were four counts the view never rendered. These are
        // the tab counts, taken before the status filter narrows $query but
        // after search and type, so each number is exactly what clicking that
        // tab would return rather than a global total that contradicts the
        // list underneath it. They come from the same scope the tab links
        // filter by, so a count cannot disagree with its own tab.
        $counts = ['' => (clone $query)->count()];

        foreach (array_keys(Coupon::STATUSES) as $key) {
            $counts[$key] = (clone $query)->statusIs($key)->count();
        }

        // This used to spell the predicate out again in SQL, and it spelled it
        // out differently to Coupon::isValid(), which paints the row badge:
        // starts_at and usage_limit were missing, so a scheduled or used-up
        // coupon sat under "Active" wearing an "Inactive" badge, and nothing
        // that was merely switched off could be reached from the tabs at all.
        if ($status = $filters['status'] ?? null) {
            $query->statusIs($status);
        }

        $coupons = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.coupons.index', compact('coupons', 'counts'));
    }

    public function create(): View
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        return view('admin.coupons.create', compact('categories'));
    }

    /**
     * The rule set shared by store() and update().
     *
     * `value` is the field that actually needed a ceiling. It was
     * `numeric|min:0`, so a percentage coupon could be saved at 5000% and the
     * cart would discount an order straight past zero. The bound depends on the
     * type chosen, so it is decided here rather than sitting in a static array.
     *
     * The integer ceilings are column widths rather than opinions:
     * usage_per_user is an unsignedSmallInteger, so 65535 is the largest value
     * that survives the insert - past that MySQL either truncates silently or
     * throws a 1264 out-of-range error, depending on the server's strict mode.
     *
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?Coupon $coupon = null): array
    {
        // Everything except a fixed-amount discount expresses `value` as a
        // percentage - buy_x_get_y uses it for the discount on the free items -
        // so 100 is the ceiling for all of them.
        $isPercentage = $request->input('type') !== 'fixed';

        $rules = [
            // Codes get typed by hand at checkout, so hold them to the
            // characters a keyboard produces without a modifier key.
            'code' => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'name' => V::text(max: 255, min: 2),
            'description' => V::textarea(required: false, max: 1000),
            'type' => V::option(self::TYPES),
            'value' => [
                'required', 'numeric', 'decimal:0,2', 'min:0',
                $isPercentage ? 'max:100' : 'max:9999999.99',
            ],
            'max_discount' => V::money(required: false),
            'min_order_amount' => V::money(required: false),
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'usage_per_user' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'starts_at' => V::scheduleStart(required: false, current: $coupon?->starts_at),
            'expires_at' => V::scheduleEnd('starts_at', required: false, current: $coupon?->expires_at),
            'is_active' => V::boolean(),
            'auto_apply' => V::boolean(),
            'applicable_products' => ['nullable', 'array', 'max:500'],
            'applicable_products.*' => ['integer', Rule::exists('products', 'id')],
            'applicable_categories' => ['nullable', 'array', 'max:200'],
            'applicable_categories.*' => ['integer', Rule::exists('categories', 'id')],
        ];

        if ($request->input('type') === 'buy_x_get_y') {
            $rules['conditions.buy_qty'] = ['required', 'integer', 'min:1', 'max:100'];
            $rules['conditions.get_qty'] = ['required', 'integer', 'min:1', 'max:100'];
        }

        return $rules;
    }

    /** Messages that name the rule rather than the regex behind it. */
    private function messages(): array
    {
        return [
            'code.regex' => 'The code may only contain letters, numbers, hyphens and underscores.',
            'value.max' => 'A percentage discount cannot be more than 100.',
            'expires_at.after' => 'The expiry date must be later than the start date.',
            'starts_at.date' => 'Enter a valid start date and time.',
            'expires_at.date' => 'Enter a valid expiry date and time.',
        ];
    }

    /**
     * Field names as they read in a message.
     *
     * Without these the schedule rules produce "The starts at cannot be set in
     * the past." - the column name, not the label on the form.
     *
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return [
            'starts_at' => 'start date',
            'expires_at' => 'expiry date',
        ];
    }

    /**
     * Normalise the validated payload into the shape the columns expect.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepare(Request $request, array $validated): array
    {
        // Ensure boolean defaults
        $validated['is_active'] = $request->boolean('is_active');
        $validated['auto_apply'] = $request->boolean('auto_apply');

        // min_order_amount and usage_per_user are NOT NULL in the schema but are
        // optional on the form. A blank field arrives as null (ConvertEmptyStringsToNull)
        // and passes `nullable` validation, so fall back to the column defaults rather
        // than letting the insert fail with a 1048 integrity-constraint error.
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;
        $validated['usage_per_user']   = $validated['usage_per_user'] ?? 1;

        // Build conditions for BOGO
        if ($request->input('type') === 'buy_x_get_y') {
            $validated['conditions'] = [
                'buy_qty' => (int) $request->input('conditions.buy_qty'),
                'get_qty' => (int) $request->input('conditions.get_qty'),
            ];
        } else {
            $validated['conditions'] = null;
        }

        return $validated;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request), $this->messages(), $this->attributes());

        Coupon::create($this->prepare($request, $validated));

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully');
    }

    public function edit(Coupon $coupon): View
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        return view('admin.coupons.edit', compact('coupon', 'categories'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $this->prepare(
            $request,
            $request->validate($this->rules($request, $coupon), $this->messages(), $this->attributes())
        );

        // Clear arrays if not sent (unchecked)
        if (!$request->has('applicable_products')) {
            $validated['applicable_products'] = null;
        }
        if (!$request->has('applicable_categories')) {
            $validated['applicable_categories'] = null;
        }

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully');
    }
}
