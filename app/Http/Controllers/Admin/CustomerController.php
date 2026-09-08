<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsAdminList;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use App\Services\ReportExportService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    // csvCell(), the calendar window and the filename/format decision, shared
    // with the orders, returns and staff exports rather than pasted into four
    // controllers. See the trait for why each one is written the way it is.
    use ExportsAdminList;

    /**
     * The export's header row, in the order exportRow() emits its cells.
     *
     * Wider than the table on screen on purpose. The five columns the page
     * shows are what an admin needs to recognise a row; a spreadsheet is what
     * they open to mail-merge, chase a payment or answer "where do we ship
     * this". Splitting the name into three columns and carrying the address
     * costs nothing per row - both come off data the query already has.
     */
    private const EXPORT_HEADERS = [
        'Customer ID', 'Name', 'First name', 'Last name', 'Email', 'Phone',
        'Status', 'Verified', 'Email verified at', 'Phone verified at',
        'Orders', 'Total spent', 'Average order value', 'Last order',
        'Signed up', 'Last login', 'Last login IP',
        'Addresses on file', 'City', 'State', 'Postal code', 'Country', 'Address',
    ];

    public function index(Request $request): View
    {
        // Validated, not read raw. Three of these were reachable only by hand
        // typing the URL and none of them were checked: ?per_page=abc reached
        // ceil($total / 'abc') and was a 500, ?per_page=0 a DivisionByZeroError,
        // and ?per_page=1000000 paginated the whole users table into one
        // response. Now that the calendar filter puts from/to on the screen,
        // the same applies to a date nobody can parse.
        $filters = $request->validate($this->filterRules());

        $customers = $this->listQuery($this->filtered($filters))
            // The id is a tiebreaker, not decoration: a seeded shop can hold
            // dozens of accounts on the same created_at, and MySQL is free to
            // order those differently on every page - which duplicates a row
            // onto page 2 and drops another entirely.
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        $stats = $this->stats($filters);

        return view('admin.customers.index', [
            'customers' => $customers,
            'stats' => $stats,
            // Drives the tile labels: with a filter on, "Total customers" is no
            // longer the whole book, and a number that silently means something
            // else than its label is worse than no number.
            'filtersApplied' => $request->anyFilled(['search', 'status', 'from', 'to']),
            'xlsxRowCap' => self::XLSX_MAX_ROWS,
            'xlsxRowCapNotice' => self::xlsxRowCapNotice(),
        ]);
    }

    /**
     * The same list, as a file.
     *
     * The one property that matters here: this method validates with
     * filterRules() and builds with filtered(), exactly as index() does. An
     * admin who narrows the table and presses Export must not be handed the
     * unfiltered book, and a filter added to one of those two methods later
     * cannot reach the page without also reaching the file.
     */
    public function export(Request $request, ReportExportService $exporter): StreamedResponse
    {
        $filters = $request->validate($this->filterRules());

        // lazy(), not get(): the CSV path streams through fputcsv as the
        // generator yields, so the whole customer book is never resident. The
        // eager loads on exportQuery() are what keep that from being an N+1 -
        // three of them are subselects that ride on the main SELECT, and
        // defaultAddress costs one query per 1,000-row chunk, not per customer.
        $rows = $this->exportQuery($this->filtered($filters))
            // Same order as the page, and the same tiebreaker. lazy() walks the
            // table with offset paging, so rows tied on created_at with no
            // stable second key can be emitted twice or skipped.
            ->latest()
            ->orderByDesc('id')
            ->lazy()
            ->map(fn (User $customer): array => $this->exportRow($customer));

        // ?format is passed straight through: the trait decides csv vs xlsx,
        // and picks the extension and Content-Type to match.
        return $this->streamExport(
            self::EXPORT_HEADERS,
            $rows,
            'customers',
            'Customers',
            $filters['format'] ?? null,
            $exporter,
        );
    }

    public function show(User $customer): View
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $customer->load(['orders.items', 'addresses', 'reviews']);

        $stats = [
            'total_orders' => $customer->orders->count(),
            'total_spent' => $customer->orders->sum('total'),
            'avg_order_value' => $customer->orders->count() > 0
                ? $customer->orders->sum('total') / $customer->orders->count()
                : 0,
        ];

        $recentOrders = $customer->orders()->with('items')->latest()->take(10)->get();

        return view('admin.customers.show', compact('customer', 'stats', 'recentOrders'));
    }

    public function edit(User $customer): View
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $validated = $request->validate([
            // max:50, not the old max:255: users.first_name and users.last_name
            // are both varchar(50), so the previous rule waved through a name
            // twice the width of the column it was about to be written into.
            'first_name' => V::name(max: 50),

            // Optional, matching sign-up and the customer's own profile form.
            // RegisterController splits one "full name" field on the first
            // space, so an account created as "dev" has last_name = ''. Marking
            // this required meant staff could not save this form at all for
            // such a customer - not even to correct a phone number - without
            // inventing a surname and writing it onto the account.
            'last_name' => V::name(required: false, max: 50),

            // email:strict, matching registration. Plain 'email' is
            // RFC-permissive and accepts "dev@gmail" with no TLD, so the old
            // rule let staff change an address to one the customer could never
            // have signed up with - and could not then use to sign in.
            'email' => [
                ...V::email(),
                Rule::unique('users', 'email')->ignore($customer->id),
            ],

            // The old 'nullable|string|max:20' accepted anything twenty
            // characters wide and stored it verbatim, so this form was the way
            // "78657 86785" got into a column that holds bare ten-digit
            // numbers everywhere else - and the way a second account could end
            // up on a number already in use, since nothing checked uniqueness
            // on the canonical form here.
            'phone' => [
                ...V::mobile(required: false),
                function (string $attribute, mixed $value, Closure $fail) use ($customer): void {
                    $normalized = IndianMobile::normalize(is_scalar($value) ? (string) $value : null);

                    if ($normalized !== null
                        && User::where('phone', $normalized)->whereKeyNot($customer->id)->exists()) {
                        $fail('An account with this mobile number already exists.');
                    }
                },
            ],

            'is_active' => 'boolean',
        ], [
            'first_name.required' => 'Please enter a first name.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Enter a valid email address, like name@example.com.',
            'email.unique' => 'An account already exists for this email address.',
        ]);

        // Built field by field rather than mass-assigning $validated, so the
        // phone lands in the column canonicalised.
        $customer->update([
            'first_name' => $validated['first_name'],
            // The column is NOT NULL, and sign-up writes '' for a single-word
            // name; keep that shape rather than introducing nulls.
            'last_name' => $validated['last_name'] ?? '',
            'email' => $validated['email'],
            // Store the bare ten digits, not whatever spacing was typed.
            'phone' => IndianMobile::normalize($validated['phone'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function toggleStatus(User $customer): RedirectResponse
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $customer->update(['is_active' => !$customer->is_active]);

        $status = $customer->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Customer account {$status}.");
    }

    public function orders(User $customer): View
    {
        abort_if(!in_array($customer->role, ['customer', 'delivery_partner']), 404);

        $perPage = request()->input('per_page', 10);
        $orders = $customer->orders()
            ->with('items')
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.customers.orders', compact('customer', 'orders'));
    }

    /**
     * One rule set for the page and the file.
     *
     * ?status used to be read as `where('is_active', $request->status ===
     * 'active')`, so anything that was not the exact string "active" - a typo,
     * "Active" with a capital, "banana" - was evaluated as false and quietly
     * showed the deactivated accounts instead. Rule::in makes that a validation
     * failure the admin can see rather than a wrong table they cannot.
     *
     * @return array<string, mixed>
     */
    private function filterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            // from/to, both nullable dates, to on or after from. Shared with the
            // orders, returns and staff screens so a calendar window means the
            // same thing on all four.
            ...$this->dateWindowRules(),
            // Unbounded per_page is a live denial-of-service class in this
            // codebase; several controllers carry the same clamp.
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'format' => $this->exportFormatRule(),
        ];
    }

    /**
     * The filtered customer book, with no aggregates on it.
     *
     * Aggregates are added by listQuery() and exportQuery() instead, so the
     * three stat tiles can count off this builder without dragging four
     * subselects through a COUNT(*).
     *
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters, bool $applyStatus = true): Builder
    {
        $query = User::query()->where('role', 'customer');

        if (! empty($filters['search'])) {
            // % and _ are wildcards inside LIKE, so a search for "100%" or
            // "a_b" used to match far more than the admin asked for.
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';

            // The third column on screen is the phone number, so an admin who
            // can read one on the page could not search for it. normalize()
            // also lets a pasted "+91 98765 43210" find the bare ten digits
            // update() actually stores.
            $mobile = IndianMobile::normalize($filters['search']);

            $query->where(function (Builder $q) use ($term, $mobile): void {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);

                if ($mobile !== null) {
                    $q->orWhere('phone', $mobile);
                }
            });
        }

        if ($applyStatus && ! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        // users.created_at is the signup date: what latest() already orders the
        // list by, and the only indexed timestamp on the table. The trait uses
        // bare >= / <= comparisons rather than the whereDate() this controller
        // used to, because DATE(created_at) is a function on the column and the
        // index cannot be used through one. Qualified because the search clause
        // above sits on the same table and a later whereHas would make a bare
        // column name ambiguous.
        return $this->applyDateWindow(
            $query,
            'users.created_at',
            $filters['from'] ?? null,
            $filters['to'] ?? null,
        );
    }

    /** The two aggregates the table on screen prints. */
    private function listQuery(Builder $query): Builder
    {
        return $query
            ->withCount('orders')
            ->withSum('orders', 'total');
    }

    /**
     * Everything the file needs, in subselects and two per-chunk loads.
     *
     * Deliberately no ->with('orders') and no ->with('addresses'): either would
     * hydrate every related row of every customer, which is exactly what lazy()
     * is here to avoid. The aggregates ride on the main SELECT and cost nothing
     * per row; defaultAddress is one query per chunk.
     */
    private function exportQuery(Builder $query): Builder
    {
        return $query
            ->withCount(['orders', 'addresses'])
            ->withSum('orders', 'total')
            ->withMax('orders', 'created_at')
            ->with('defaultAddress');
    }

    /**
     * One customer, as one row of the file.
     *
     * Every cell a customer or a third party could have typed goes through
     * csvCell(). A shopper is free to register as `=HYPERLINK("http://evil",
     * "hi")`, and both Excel and Google Sheets treat a leading =, +, - or @ as
     * a formula - so without the de-fang, opening the export executes it. Ids,
     * counts and formatted dates are ours and cannot lead with one of those.
     *
     * @return array<int, mixed>
     */
    private function exportRow(User $customer): array
    {
        // defaultAddress only, and eager loaded. User has no latestAddress
        // relation, and $customer->addresses->first() would pull every address
        // of every customer through a full-table export - so a customer who
        // never flagged a default has blank address cells. "Addresses on file"
        // is what tells the admin which of the two it is.
        $address = $customer->defaultAddress;

        $orders = (int) $customer->orders_count;
        $spent = (float) ($customer->orders_sum_total ?? 0);

        return [
            $customer->id,
            // getFullNameAttribute() is deliberately not trimmed on the model -
            // an account with no last name has always carried a trailing space
            // there and two other features test against it. Trim at the call
            // site instead.
            $this->csvCell(trim($customer->full_name)),
            $this->csvCell($customer->first_name),
            $this->csvCell($customer->last_name),
            $this->csvCell($customer->email),
            $this->csvCell($customer->phone ?? ''),
            $customer->is_active ? 'Active' : 'Inactive',
            $customer->is_verified ? 'Yes' : 'No',
            $customer->email_verified_at?->format('Y-m-d H:i') ?? '',
            $customer->phone_verified_at?->format('Y-m-d H:i') ?? '',
            $orders,
            // Same figures as the page, including the fact that both count
            // cancelled and returned orders - an export must agree with the
            // list it was taken from, and correcting one without the other
            // would just move the disagreement.
            number_format($spent, 2, '.', ''),
            $orders > 0 ? number_format($spent / $orders, 2, '.', '') : '0.00',
            // Carbon::parse rather than ?->format: an aggregate alias is not
            // covered by the related model's datetime cast on every driver, so
            // this arrives as a string or a Carbon depending on where it ran.
            $customer->orders_max_created_at
                ? Carbon::parse($customer->orders_max_created_at)->format('Y-m-d H:i')
                : '',
            // The column the calendar filter cuts on. A date-filtered file with
            // no dates in it has nothing to show for its own filter.
            $customer->created_at?->format('Y-m-d H:i') ?? '',
            $customer->last_login_at?->format('Y-m-d H:i') ?? '',
            $this->csvCell($customer->last_login_ip ?? ''),
            (int) ($customer->addresses_count ?? 0),
            $this->csvCell($address?->city ?? ''),
            $this->csvCell($address?->state ?? ''),
            $this->csvCell($address?->postal_code ?? ''),
            $this->csvCell($address?->country ?? ''),
            $this->csvCell($address?->full_address ?? ''),
        ];
    }

    /**
     * The three tiles, counted off the same filtered builder as the table.
     *
     * They used to be unfiltered totals, so "Total customers" kept showing the
     * whole book while the rows underneath showed a subset. Nobody noticed
     * while from/to were unreachable; the moment a date picker exists, that is
     * a contradiction an admin will read off one screen and report.
     *
     * The status clause is left out on purpose, so Active stays a breakdown of
     * the current window rather than collapsing to either the total or zero.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function stats(array $filters): array
    {
        $total = $this->filtered($filters, applyStatus: false)->count();

        return [
            'total' => $total,
            // The rows the table and the export actually carry, status clause
            // included. The first tile relabels itself "Customers matching" as
            // soon as any filter is set, and with ?status=inactive the window
            // total is not that number - it read "Customers matching 500" above
            // twelve rows. It is also the count the xlsx row-cap warning has to
            // be measured against, or an admin filtering to a forty-row slice
            // is told their spreadsheet will be truncated at two thousand.
            'matching' => empty($filters['status'])
                ? $total
                : $this->filtered($filters)->count(),
            'active' => $this->filtered($filters, applyStatus: false)
                ->where('is_active', true)
                ->count(),
            // Range comparisons, not whereMonth()/whereYear(), for the same
            // reason the window filter avoids whereDate().
            'new_this_month' => $this->filtered($filters, applyStatus: false)
                ->where('created_at', '>=', now()->startOfMonth())
                ->where('created_at', '<=', now()->endOfMonth())
                ->count(),
        ];
    }
}
