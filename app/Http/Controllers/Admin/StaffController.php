<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsAdminList;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use App\Rules\ValidationRules as V;
use App\Services\ReportExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffController extends Controller
{
    // csvCell(), the calendar window and the CSV/xlsx decision, shared with the
    // other three list exports so a fix to any of them is a fix to all four.
    use ExportsAdminList;

    /**
     * The staff roles, in one place.
     *
     * store() and update() spell the same four out as an `in:` string and the
     * enum column spells them out a third time. The filter reads them from
     * here, so a fifth role added to the column cannot leave the dropdown
     * quietly offering four.
     */
    private const ROLES = ['manager', 'cashier', 'support', 'warehouse'];

    /**
     * The export's header row.
     *
     * A constant rather than an inline array because the row builder below has
     * to stay exactly as wide as it: a column added to one and not the other
     * shifts every cell after it by one, which is a spreadsheet that is wrong
     * rather than one that fails.
     */
    private const EXPORT_HEADERS = [
        'Staff ID', 'Employee ID', 'User ID', 'Name', 'Email', 'Phone', 'Role', 'Status',
        'Login enabled', 'Store', 'Store code', 'Commission rate (%)', 'Permissions',
        'Joined at', 'Created (Joined)', 'Last login', 'Last login IP',
    ];

    public function index(Request $request): View
    {
        // This method used to take no Request at all and read exactly one
        // parameter, per_page, through the helper. The blade has always
        // rendered a search box, a Search button, a "Clear all" link and a
        // search-aware empty state, every one of which submitted ?search= to a
        // controller that discarded it - while the box echoed the term back
        // into itself, so it looked as though it had worked.
        $filters = $request->validate($this->filterRules());

        $staff = $this->filtered($filters)
            ->with('user')
            ->orderByDesc('staff.created_at')
            // The id tiebreak the other three lists carry. A seeder or a bulk
            // import writes a whole batch on the same second, and MySQL is free
            // to order rows tied on created_at differently for each page, which
            // repeats one staff member onto page 2 and drops another entirely.
            ->orderByDesc('staff.id')
            // Was request()->input('per_page', 10) with no validation and no
            // ceiling, so ?per_page=9999999 paginated the whole table in one
            // request. The clamp is in filterRules() with the rest.
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return view('admin.staff.index', [
            'staff' => $staff,
            'filters' => $filters,
            'roles' => self::ROLES,
            // Shown beside the Export Excel button, so the row ceiling is
            // learned before the download rather than in its last row.
            'xlsxRowCapNotice' => self::xlsxRowCapNotice(),
        ]);
    }

    /**
     * The rows the list is showing, as a CSV or a spreadsheet.
     *
     * The shape below is the whole point: this action cannot disagree with
     * index(), because both validate through filterRules() and both build
     * through filtered(). An admin who narrows the table and presses Export
     * gets those rows, never the unfiltered staff list.
     *
     * This is the most sensitive of the four exports - it carries emails,
     * phones, permission arrays and last-login IPs - which is why its route
     * sits inside the admin.section:staff group beside the page itself.
     */
    public function export(Request $request, ReportExportService $exporter): StreamedResponse
    {
        $filters = $request->validate($this->filterRules());

        // lazy(), not get(): it chunks at 1000 and re-runs the eager loads once
        // per chunk, so with() is still honoured and nothing beyond a chunk is
        // held. A bare cursor() would stream too, but it does not eager load,
        // and six of the seventeen cells below come off the user relation.
        $rows = $this->filtered($filters)
            ->with(['user', 'store'])
            ->orderByDesc('staff.created_at')
            // Load-bearing here rather than merely tidy: lazy() walks the table
            // with LIMIT/OFFSET and re-runs the query per chunk, so rows tied on
            // created_at across the 1000/1001 boundary can be emitted twice and
            // others skipped. A headcount that is quietly wrong is worse than
            // one that fails.
            ->orderByDesc('staff.id')
            ->lazy()
            ->map(fn (Staff $s): array => [
                $s->id,
                // Hand-entered, so it can open with a sigil even though the
                // ones this panel mints ("EMP-0001") never do.
                $this->csvCell($s->employee_id),
                $s->user_id,
                // ?-> throughout the user columns: user() is a belongsTo onto a
                // SoftDeletes model, so a trashed login makes the whole
                // relation null. trim() because the accessor leaves a trailing
                // space when last_name is empty, deliberately and by contract.
                $this->csvCell(trim((string) $s->user?->full_name)),
                $this->csvCell($s->user?->email),
                $this->csvCell($s->user?->phone),
                // Shaped exactly as the badge on the page shapes it, so a cell
                // and the row it came from read the same.
                $this->csvCell(ucfirst(str_replace('_', ' ', $s->role ?? 'staff'))),
                $s->is_active ? 'Active' : 'Inactive',
                // A second, independent switch the page never shows: a staff
                // row can read Active while the login itself is disabled.
                // Long-hand because $s->user?->is_active ? 'Yes' : 'No' would
                // render a missing user as "No" rather than as blank.
                $s->user === null ? '' : ($s->user->is_active ? 'Yes' : 'No'),
                $this->csvCell($s->store?->name),
                $this->csvCell($s->store?->code),
                // The decimal:2 cast hands back a string, so the (float) hop is
                // load-bearing before number_format.
                number_format((float) $s->commission_rate, 2, '.', ''),
                // An array cast over a JSON column, hence ?? [] rather than a
                // relation. Free-form, so it goes through the de-fanging too.
                $this->csvCell(implode(', ', $s->permissions ?? [])),
                // Blank for every row this panel has ever created - see the
                // note on the filter column in filtered(). Carried anyway,
                // because a seeder or the POS may have set it.
                $s->joined_at?->format('Y-m-d H:i'),
                $s->created_at->format('Y-m-d H:i'),
                $s->user?->last_login_at?->format('Y-m-d H:i'),
                $this->csvCell($s->user?->last_login_ip),
            ]);

        // The raw ?format goes straight through: streamExport() decides between
        // CSV and xlsx, names the file and caps the spreadsheet.
        return $this->streamExport(
            self::EXPORT_HEADERS,
            $rows,
            'staff',
            'Staff',
            $filters['format'] ?? null,
            $exporter,
        );
    }

    public function create(): View
    {
        return view('admin.staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Was 'string|max:50', which let a staff name be saved as digits or
            // symbol soup. These write users.first_name/last_name - the same two
            // varchar(50) columns admin/customers and the customer's own profile
            // form already guard with V::name - so the rule matches those, and
            // matches the keystroke filter now on the two boxes in the blade.
            'first_name' => V::name(max: 50),
            'last_name' => V::name(max: 50),
            'email' => 'required|email|max:50|unique:users,email',
            // Was 'required|min:8|confirmed' - eight characters of anything -
            // while every other form on the site that mints a password goes
            // through V::password(). A staff row is a real login to the admin
            // panel, so it is held to the same policy as a customer's: ten
            // characters with mixed case, a number and a symbol.
            'password' => [...V::password(), 'max:255'],
            'role' => 'required|in:manager,cashier,support,warehouse',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:dashboard,orders,abandoned_carts,catalog,customers,sellers,staff,marketing,storefront,content,reports,settings',
        ], V::passwordMessages());

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'staff',
        ]);

        $employeeId = 'EMP-' . str_pad(Staff::max('id') + 1, 4, '0', STR_PAD_LEFT);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member created');
    }

    public function edit(Staff $staff): View
    {
        $staff->load('user');

        return view('admin.staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $validated = $request->validate([
            // Was 'string|max:50', which let a staff name be saved as digits or
            // symbol soup. These write users.first_name/last_name - the same two
            // varchar(50) columns admin/customers and the customer's own profile
            // form already guard with V::name - so the rule matches those, and
            // matches the keystroke filter now on the two boxes in the blade.
            'first_name' => V::name(max: 50),
            'last_name' => V::name(max: 50),
            'email' => 'required|email|max:50|unique:users,email,' . $staff->user_id,
            // Optional here - the box says "Leave blank to keep current" - but
            // a password that IS typed meets the same policy as a new one.
            'password' => [...V::password(required: false), 'max:255'],
            'role' => 'required|in:manager,cashier,support,warehouse',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:dashboard,orders,abandoned_carts,catalog,customers,sellers,staff,marketing,storefront,content,reports,settings',
        ], V::passwordMessages());

        $staff->user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        if ($request->filled('password')) {
            $staff->user->update(['password' => Hash::make($validated['password'])]);
        }

        $staff->update([
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
            'permissions' => $validated['permissions'] ?? null,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $staff->user->delete();
        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff member deleted');
    }

    /**
     * The one rule set, shared by the page and by the file.
     *
     * @return array<string, mixed>
     */
    private function filterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            // Spreads to ['from' => nullable|date, 'to' => nullable|date|after_or_equal:from],
            // the same pair the other three screens validate their calendar with.
            ...$this->dateWindowRules(),
            'role' => ['nullable', Rule::in(self::ROLES)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            // Unbounded per_page is a live denial-of-service class in this
            // codebase; several controllers carry the same clamp.
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            // csv | xlsx | excel. Only export() acts on it, but it is validated
            // for both so a stray ?format on the page cannot become an error
            // that only appears once somebody clicks Export.
            'format' => $this->exportFormatRule(),
        ];
    }

    /**
     * The one builder, shared by the page and by the file.
     *
     * Every clause here applies to both, which is the property this whole
     * feature exists for: there is no second place where export() could add a
     * where() the list does not have, or drop one that it does.
     *
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        $query = Staff::query();

        if (! empty($filters['search'])) {
            // % and _ are wildcards inside LIKE and the binding does not escape
            // them, so a search for "50%" would otherwise match every row.
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';

            $query->where(function (Builder $q) use ($term) {
                // whereHas() applies the User model's SoftDeletingScope, so a
                // staff row whose login has been trashed cannot match on name
                // or email. That is true of the export as well, because it is
                // the same builder - the two must never disagree on the row set.
                $q->whereHas('user', fn (Builder $u) => $u
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    // Neither half alone matches "Priya Sharma". Names are
                    // stored folded to lower case and utf8mb4_unicode_ci makes
                    // LIKE case-insensitive, so this searches the raw columns
                    // rather than the display-cased accessor.
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$term])
                    ->orWhere('email', 'like', $term))
                    // Qualified: the whereHas subquery puts a `users` table in
                    // play, and employee_id belongs to this one.
                    ->orWhere('staff.employee_id', 'like', $term);
            });
        }

        // staff.created_at, NOT staff.joined_at. joined_at exists, is fillable
        // and is cast to datetime, but neither store() nor update() has ever
        // written it and no other code path does either - it is NULL for every
        // row the admin panel has made, so a calendar bound to it would return
        // an empty table. created_at is also what the "Joined" column on the
        // page actually prints, so the filter cuts on the dates the admin can
        // see. Both columns are in the export.
        $this->applyDateWindow(
            $query,
            'staff.created_at',
            $filters['from'] ?? null,
            $filters['to'] ?? null,
        );

        if (! empty($filters['role'])) {
            $query->where('staff.role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('staff.is_active', $filters['status'] === 'active');
        }

        return $query;
    }
}
