<?php

namespace App\Http\Controllers\Admin;

use App\Events\RefundProcessed;
use App\Events\ReturnRequested;
use App\Http\Controllers\Concerns\ExportsAdminList;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\OrderReturn;
use App\Services\ReportExportService;
use App\Services\StoreCreditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReturnController extends Controller
{
    // csvCell(), the calendar window and the csv/xlsx decision all live in the
    // trait, so this screen, orders, customers and staff cannot drift apart on
    // any of the three.
    use ExportsAdminList;

    /**
     * Every value of the returns.status enum, in lifecycle order.
     *
     * The list was written out twice - once as an `in:` rule on updateStatus()
     * and once, five of the eight, as the blade's tab strip - so a status added
     * to the migration reached neither. It is one list now, and the filter
     * validates against it: without a Rule::in, ?status=shipped rendered a
     * perfectly valid-looking page with no rows and no explanation, and
     * ?status[]=x passed an array into where() and 500d.
     */
    public const STATUSES = [
        'requested',
        'approved',
        'rejected',
        'pickup_scheduled',
        'picked_up',
        'received',
        'processed',
        'completed',
    ];

    /**
     * The export's columns.
     *
     * Wider than the screen on purpose. The table shows what an admin scans for
     * - number, customer, type, status, reason, refund - while the file is what
     * they reconcile against: the phone number they will ring to arrange the
     * pickup, the description (which is also the only place processRefund()
     * writes refund notes), the pickup partner, who processed it, and every
     * lifecycle timestamp the schema actually keeps.
     *
     * There is deliberately no "Rejected at" or "Received at": the enum has
     * those states but the table has no timestamp for them, and inventing a
     * column an admin would read as fact is worse than the honest omission -
     * "Last updated" is what stands in for them.
     */
    private const EXPORT_HEADERS = [
        'Return',
        'Order',
        'Requested at',
        'Customer',
        'Email',
        'Phone',
        'Type',
        'Status',
        'Reason',
        'Description',
        'Items',
        'Units',
        'Refund amount',
        'Refund method',
        'Refund preference',
        'Refund coupon',
        'Order total',
        'Pickup partner',
        'Pickup partner ID',
        'Processed by',
        'Approved at',
        'Pickup scheduled at',
        'Picked up at',
        'Completed at',
        'Exchange order',
        'Last updated',
    ];

    public function index(Request $request): View
    {
        $validator = $this->filterValidator($request);
        $filters = $this->filters($validator);

        $returns = $this->filtered($filters)
            ->with(['order', 'order.user'])
            // Was latest(). The id tiebreak is what makes page 2 of a list of
            // returns filed in the same second the rows page 1 did not show.
            ->orderByDesc('returns.created_at')
            ->orderByDesc('returns.id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        // Shared, not ->withErrors() on the view: both things that print these -
        // the x-admin.form-errors strip and the date range filter's inline
        // message - are Blade components, and a component's template is
        // rendered with its own props plus the *shared* data. An error bag put
        // on this view alone would reach neither of them, and the dropped
        // filter would go back to being silent.
        if ($validator->fails()) {
            view()->share('errors', view()->shared('errors', new ViewErrorBag)->put('default', $validator->errors()));
        }

        return view('admin.returns.index', [
            'returns' => $returns,
            // The screen prints and re-links these, so it gets the validated
            // copy rather than reading the query string a second time: ?status[]
            // arrives as an array, and {{ }} cannot print one - it threw
            // "htmlspecialchars(): Argument #1 must be of type string" straight
            // out of the search bar's hidden input.
            'filters' => $filters,
            // Off the same filtered builder as the rows, so a tile can never
            // contradict the table underneath it.
            'stats' => $this->statusCounts($filters),
            'xlsxNotice' => self::xlsxRowCapNotice(),
        ]);
    }

    /**
     * The list as a file.
     *
     * Same rules and the same builder as index(), which is the entire point: an
     * admin who narrows the screen to last week and presses Export must not
     * quietly receive the whole table.
     */
    public function export(Request $request, ReportExportService $exporter): StreamedResponse
    {
        $filters = $this->filters($this->filterValidator($request));

        // lazy(), not get(). A return drags an order, a customer, a pickup
        // partner and its own line items behind it; get() would hold every one
        // of them before the first byte was written. with() still batches
        // through a LazyCollection - it eager-loads per 1,000-row chunk.
        //
        // items is counted and summed rather than loaded: both are select
        // subqueries, where ->with('items') would hydrate every ReturnItem in
        // the table, which is the exact thing lazy() is here to prevent.
        $rows = $this->filtered($filters)
            ->with(['order.user', 'pickupPartner.user', 'processedBy', 'exchangeOrder', 'refundCoupon'])
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->orderByDesc('returns.created_at')
            ->orderByDesc('returns.id')
            ->lazy()
            ->map(fn (OrderReturn $return) => [
                $this->csvCell($return->return_number),
                $this->csvCell($return->order?->order_number),
                $return->created_at?->format('Y-m-d H:i'),
                // The Order accessors rather than the screen's own
                // `order->user->full_name ?? 'N/A'`: they still name a guest or
                // a soft-deleted customer from the address snapshot instead of
                // printing N/A over a real person.
                $this->csvCell($return->order?->customer_name),
                $this->csvCell($this->contactEmail($return)),
                $this->csvCell($return->order?->customer_phone),
                ucfirst((string) ($return->type ?? 'return')),
                $this->statusLabel((string) $return->status),
                $this->csvCell($return->reason),
                $this->csvCell($return->description),
                (int) ($return->items_count ?? 0),
                // withSum yields NULL, not 0, for a return with no lines.
                (int) ($return->items_sum_quantity ?? 0),
                // decimal:2 casts to a *string*; number_format() on that is a
                // deprecation on PHP 8.4. No thousands separator either, or the
                // spreadsheet reads the money as text.
                number_format((float) $return->refund_amount, 2, '.', ''),
                $this->csvCell($return->refund_method),
                // What the customer asked for, beside what they were paid by.
                // Above the order threshold the return form promises store
                // credit and offers nothing else, so the method alone cannot
                // tell a coupon that was chosen from one that was owed - and a
                // return still in the queue has a preference but no method yet.
                // Null for anything raised before the preference existed.
                $this->csvCell($return->preferenceLabel()),
                $this->csvCell($return->refundCoupon?->code),
                number_format((float) ($return->order?->total ?? 0), 2, '.', ''),
                // Both ?-> are load-bearing: pickup_partner_id is nullable and
                // User is soft-deleted, so a deleted partner account leaves the
                // company name as the only thing left to print.
                $this->csvCell($return->pickupPartner?->user?->full_name ?: $return->pickupPartner?->company_name),
                $this->csvCell($return->pickupPartner?->partner_id),
                $this->csvCell($return->processedBy?->full_name),
                $return->approved_at?->format('Y-m-d H:i'),
                $return->pickup_scheduled_at?->format('Y-m-d H:i'),
                $return->picked_up_at?->format('Y-m-d H:i'),
                $return->completed_at?->format('Y-m-d H:i'),
                $this->csvCell($return->exchangeOrder?->order_number),
                $return->updated_at?->format('Y-m-d H:i'),
            ]);

        // The raw ?format goes straight through: the trait owns the csv/xlsx
        // decision so all four screens spell it the same way.
        return $this->streamExport(
            self::EXPORT_HEADERS,
            $rows,
            'returns',
            'Returns',
            $filters['format'] ?? null,
            $exporter,
        );
    }

    public function show(OrderReturn $return): View
    {
        $return->load(['order', 'order.user', 'items.orderItem.product', 'pickupPartner.user', 'refundCoupon']);

        $activePartners = DeliveryPartner::with('user')->where('is_active', true)->get();

        return view('admin.returns.show', compact('return', 'activePartners'));
    }

    public function updateStatus(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'approved' && !$return->approved_at) {
            $updates['approved_at'] = now();
        } elseif ($validated['status'] === 'pickup_scheduled' && !$return->pickup_scheduled_at) {
            $updates['pickup_scheduled_at'] = now();
        } elseif ($validated['status'] === 'picked_up' && !$return->picked_up_at) {
            $updates['picked_up_at'] = now();
        } elseif ($validated['status'] === 'completed' && !$return->completed_at) {
            $updates['completed_at'] = now();
            $updates['processed_by'] = auth()->id();
        }

        $previousStatus = $return->status;

        $return->update($updates);

        // Only on an actual transition. The listener behind this event mails
        // "Return Request Approved" whenever the return *is* approved rather
        // than when it *becomes* approved, and this dispatch fired on every
        // submit - so a double-click, a browser refresh of the PUT, or saving
        // the form again after assigning a pickup partner sent the customer
        // another approval email each time. Order::updateStatus has guarded its
        // own event this way all along; this is the same guard.
        if ($previousStatus !== $validated['status']) {
            ReturnRequested::dispatch($return);
        }

        return back()->with('success', 'Return status updated');
    }

    public function assignPartner(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'pickup_partner_id' => 'nullable|exists:delivery_partners,id',
        ]);

        $return->update(['pickup_partner_id' => $validated['pickup_partner_id']]);

        if ($validated['pickup_partner_id']) {
            $partner = DeliveryPartner::with('user')->find($validated['pickup_partner_id']);
            $name = $partner?->user?->full_name ?? 'partner';
            return back()->with('success', "Pickup partner assigned: {$name}");
        }

        return back()->with('success', 'Pickup partner removed');
    }

    public function processRefund(Request $request, OrderReturn $return, StoreCreditService $credit): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'refund_method' => 'required|in:wallet,original,bank,coupon',
            'notes' => 'nullable|string',
        ]);

        // The customer's stated preference wins over the posted field.
        //
        // Above the order threshold the store told them the money would come
        // back as credit and gave them no other option, so paying it out to a
        // card instead would quietly break the promise the form made. The
        // single-option select on the admin screen is honesty about that, not
        // the thing that enforces it - a stale form, or a hand-made POST, must
        // reach the same answer.
        $method = $return->wantsCoupon() ? 'coupon' : $validated['refund_method'];

        // 'notes' is nullable, and a validated array only carries the keys that
        // were actually sent - so reading it directly raised "Undefined array
        // key" and turned any post without the field into a 500. The admin form
        // always sends it, which is why the shop never saw this; anything else
        // that refunds a return did.
        $notes = trim((string) ($validated['notes'] ?? ''));

        $description = $notes === ''
            ? $return->description
            : ($return->description
                ? $return->description."\n\nRefund notes: ".$notes
                : 'Refund notes: '.$notes);

        $coupon = null;

        DB::transaction(function () use ($return, $validated, $method, $description, $credit, &$coupon) {
            $return->update([
                'refund_amount' => $validated['amount'],
                'refund_method' => $method,
                'description' => $description,
                'status' => 'completed',
                'completed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Minted in the same transaction as the row that says the refund
            // happened, so the shop can never record a completed credit refund
            // with no credit behind it. issueFor() is idempotent, so a double
            // submit re-uses the voucher rather than paying twice.
            if ($method === 'coupon' && $validated['amount'] > 0) {
                $coupon = $credit->issueFor($return, (float) $validated['amount']);
            }
        });

        // Dispatched after the commit: the listeners mail the customer, and a
        // message naming a coupon code that a rolled-back transaction never
        // created would be worse than a late one.
        RefundProcessed::dispatch($return->refresh(), (float) $validated['amount'], $method);

        if ($coupon) {
            return back()->with('success',
                'Store credit of '.format_price($validated['amount']).' issued as coupon '.$coupon->code.'.');
        }

        return back()->with('success', "Refund of " . format_price($validated['amount']) . " credited to customer's account");
    }

    /**
     * What the screen and the file are both allowed to be cut by.
     *
     * None of these were validated before, and every one of them was reachable
     * from the address bar: ?status[]=x and ?search[]=x were plain 500s (an
     * array into where(), then "Array to string conversion"), ?per_page=0 was a
     * DivisionByZeroError inside the paginator and ?per_page=999999 was a live
     * denial of service on a shared box.
     *
     * @return array<string, mixed>
     */
    private function filterRules(): array
    {
        return [
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'search' => ['nullable', 'string', 'max:120'],
            ...$this->dateWindowRules(),
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'format' => $this->exportFormatRule(),
        ];
    }

    /**
     * Validate the query string without ever refusing to answer.
     *
     * ReportRange's rule, applied to a list screen: a malformed window is
     * corrected, not rejected. $request->validate() would throw a
     * ValidationException on a hand-typed ?from=lastweek, and the redirect back
     * that produces goes to whichever page the admin came from - or, on a link
     * opened cold, straight back to the same URL. A list screen has to render.
     *
     * So the failing keys are dropped and the rest of the filter survives, and
     * the messages come back with the validator: index() shares them, so the
     * x-admin.date-range-filter component prints the reason underneath its own
     * two inputs. Dropped, but never silently.
     */
    private function filterValidator(Request $request): Validator
    {
        return ValidatorFactory::make($request->query(), $this->filterRules());
    }

    /**
     * The filters that survived validation, as a fixed shape.
     *
     * valid() hands back the whole payload minus the attributes that failed, so
     * it is narrowed to the keys this screen knows: everything downstream can
     * then read $filters['from'] without wondering whether the array also
     * carries ?page or whatever else was in the URL.
     *
     * @return array<string, mixed>
     */
    private function filters(Validator $validator): array
    {
        $valid = $validator->valid();

        // A window is one filter in two fields, so it fails as a pair. valid()
        // drops only the attribute that failed, and for a backwards range that
        // is `to` alone - which leaves `from` standing and answers with every
        // return from that date onward, strictly MORE than the admin asked for.
        // The list at least carries a banner saying a filter was ignored; the
        // file carries nothing, and wider-than-asked is the one direction an
        // export must never fail in. The other three screens refuse the request
        // outright; this one has to render, so it drops both ends instead.
        if ($validator->errors()->hasAny(['from', 'to'])) {
            unset($valid['from'], $valid['to']);
        }

        return [
            'status' => $valid['status'] ?? null,
            'search' => $valid['search'] ?? null,
            'from' => $valid['from'] ?? null,
            'to' => $valid['to'] ?? null,
            'per_page' => $valid['per_page'] ?? null,
            'format' => $valid['format'] ?? null,
        ];
    }

    /**
     * The one query behind the screen, the tiles and the export.
     *
     * $applyStatus is what lets the tiles count the other statuses inside the
     * search and the date window the admin has set, the way the abandoned carts
     * screen counts its tabs.
     *
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters, bool $applyStatus = true): Builder
    {
        $query = OrderReturn::query();

        if ($applyStatus && ! empty($filters['status'])) {
            $query->where('returns.status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            // % and _ are wildcards inside LIKE, so a search for "50%" used to
            // match everything from "50" onwards.
            $term = '%'.addcslashes((string) $filters['search'], '%_\\').'%';

            $query->where(function (Builder $q) use ($term) {
                $q->where('returns.return_number', 'like', $term)
                    ->orWhereHas('order', fn (Builder $oq) => $oq->where('order_number', 'like', $term))
                    ->orWhereHas('order.user', fn (Builder $uq) => $uq
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term));
            });
        }

        // created_at IS the request timestamp - there is no requested_at column,
        // and both places that file a return set nothing but status - so this is
        // the same date the third line of the "Return" cell prints and the same
        // one the list is sorted by. Qualified because the search above adds
        // whereHas subqueries against orders and users.
        return $this->applyDateWindow(
            $query,
            'returns.created_at',
            $filters['from'] ?? null,
            $filters['to'] ?? null,
        );
    }

    /**
     * The six tiles, counted inside the current filters.
     *
     * They used to be six bare OrderReturn::count() queries against the whole
     * table, so searching for one customer left the tiles reporting the entire
     * shop above the single row that matched. A date window makes that actively
     * misleading - filter to last week, read all-time figures - so the counts
     * come off the same builder as the rows, minus the status clause.
     *
     * Padded to all eight statuses so 'total' is the true total: the strip shows
     * only five of them, and a return sitting in pickup_scheduled, picked_up or
     * processed still has to be counted somewhere.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function statusCounts(array $filters): array
    {
        $counts = $this->filtered($filters, applyStatus: false)
            ->selectRaw('returns.status as status, COUNT(*) as total')
            ->groupBy('returns.status')
            ->pluck('total', 'status')
            ->all();

        $padded = array_replace(array_fill_keys(self::STATUSES, 0), $counts);
        $padded['total'] = array_sum($padded);

        return array_map('intval', $padded);
    }

    /**
     * Whatever address is left to reach this customer on.
     *
     * There is no customer_email accessor on Order, and three separate things
     * make the obvious `order->user->email` empty: returns.user_id went nullable
     * for guest returns, orders.user_id is nullable for guest checkout, and User
     * is soft-deleted. In all three the checkout snapshot is the only surviving
     * copy of the address, so a guest return would otherwise export with a blank
     * contact column.
     */
    private function contactEmail(OrderReturn $return): ?string
    {
        $order = $return->order;

        if (! $order) {
            return null;
        }

        $email = $order->user?->email
            ?: data_get($order->shipping_address_snapshot, 'email')
            ?: data_get($order->metadata, 'guest_email');

        return is_scalar($email) ? (string) $email : null;
    }

    /**
     * The status, worded exactly as the badge on the screen words it.
     *
     * 'processed' reads as "Refund Processed" there, and a file that called the
     * same row something else would be the first thing an admin distrusted.
     */
    private function statusLabel(string $status): string
    {
        return $status === 'processed'
            ? 'Refund Processed'
            : ucfirst(str_replace('_', ' ', $status));
    }
}
