<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Http\Controllers\Concerns\ExportsAdminList;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\ReportExportService;
use App\Services\ShiprocketService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    // The calendar window, the ?format spellings and the CSV de-fanging are all
    // shared with the returns, customers and staff exports - see the trait for
    // why they are not a private method on each of the four.
    use ExportsAdminList;

    /**
     * The payment enum. Order::STATUS_TRANSITIONS covers the fulfilment side,
     * but nothing on the model names the payment values, so the migration's
     * enum is transcribed once here rather than inline in two places.
     */
    private const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded', 'partial_refund'];

    /**
     * The export's header row, in the order exportRow() builds its cells.
     *
     * The two are deliberately adjacent in the file so they can be read
     * together: a column added to one and not the other shifts every heading
     * after it, and a shifted spreadsheet is the kind of wrong that gets acted
     * on rather than noticed.
     */
    private const EXPORT_HEADERS = [
        'Order', 'Placed on', 'Customer', 'Email', 'Phone',
        'Fulfilment status', 'Payment status', 'Payment method',
        'Subtotal', 'Discount', 'Tax', 'Shipping', 'Total', 'Paid', 'Balance due', 'Currency',
        'Items', 'Units', 'Coupon', 'Source',
        'Shipping address', 'City', 'State', 'Postcode', 'Country',
        'Carrier', 'Tracking number', 'Delivery partner',
        'Confirmed at', 'Packed at', 'Shipped at', 'Out for delivery at',
        'Delivered at', 'Cancelled at', 'Expected delivery', 'Last updated',
    ];

    public function index(Request $request): View
    {
        // Same rules as export() below, and the rows come off the same builder,
        // so the file an admin downloads can never hold more than the list they
        // downloaded it from.
        $filters = $request->validate($this->filterRules());

        $orders = $this->filtered($filters)
            ->with('user')
            // withCount, not with('items'): the only thing the table does with
            // the lines is print how many there are, and hydrating every
            // OrderItem on the page to call count() on them is a lot of objects
            // for one integer.
            ->withCount('items')
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        $stats = $this->stats($filters);

        // The window that is actually in force, under its canonical names and
        // already order-corrected. The blade builds both export links from this
        // rather than from the query string, which is what keeps the file and
        // the table honest on a legacy ?date_from URL: pressing Apply with the
        // calendar empty submits from=&to= alongside the stale alias, the page
        // reads that as "no window", and a link built from the raw parameters
        // would have lost the empty pair and let the alias filter the download.
        $window = $this->resolvedWindow(
            $this->windowEnd($filters, 'from', 'date_from'),
            $this->windowEnd($filters, 'to', 'date_to'),
        );

        return view('admin.orders.index', compact('orders', 'stats', 'filters', 'window'));
    }

    public function export(Request $request, ReportExportService $exporter): StreamedResponse
    {
        // The identical validate() call and the identical builder. This is the
        // whole point of the feature: an admin who narrows the list and presses
        // Export must not quietly receive the unfiltered table.
        $filters = $request->validate($this->filterRules());

        // lazy(), not get() and not cursor(). get() would hold every order and
        // all of its relations in memory before the first byte is written;
        // cursor() streams but resolves the relations one row at a time, which
        // is an N+1 over the whole table. lazy() chunks and eager-loads per
        // chunk, which is the shape AbandonedCartController::export() uses.
        //
        // The id tie-break is load-bearing: lazy() pages with OFFSET, and a
        // non-unique sort key alone can drop or repeat a row across a chunk
        // boundary when several orders share a created_at.
        $rows = $this->filtered($filters)
            ->with(['user', 'coupon', 'deliveryPartner.user', 'shipments'])
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->lazy()
            ->map(fn (Order $order) => $this->exportRow($order));

        // The format is passed through rather than branched on: streamExport()
        // owns the csv/xlsx decision, the extension, the Content-Type and the
        // row cap on the spreadsheet path.
        return $this->streamExport(
            self::EXPORT_HEADERS,
            $rows,
            'orders',
            'Orders',
            $filters['format'] ?? null,
            $exporter,
        );
    }

    /**
     * One order as one export line.
     *
     * Every cell a customer, a courier or a marketing team can type into goes
     * through csvCell(): the name, the address, the coupon code and the carrier
     * are all free text from outside, and a value opening with "=" is a live
     * formula in Excel and in PhpSpreadsheet alike. Numbers and formatted dates
     * are generated here and need no de-fanging.
     *
     * @return array<int, mixed>
     */
    private function exportRow(Order $order): array
    {
        // The newest shipment, not shipments->first(). show() reads ->first(),
        // which is the OLDEST row, while updateStatus() and ship() both write
        // through shipments()->latest() - so an order that was re-shipped would
        // export the dead AWB if this copied show().
        $shipment = $order->shipments->sortByDesc('id')->first();

        $address = collect(['address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country'])
            ->map(fn (string $key) => trim((string) data_get($order->shipping_address_snapshot, $key)))
            ->filter()
            ->implode(', ');

        return [
            $this->csvCell($order->order_number),
            $order->created_at?->format('Y-m-d H:i'),
            // The accessor, not user->full_name: it falls back to the shipping
            // snapshot, so a guest order carries the name it was placed with
            // rather than the word "Guest".
            $this->csvCell($order->customer_name),
            // Two ?-> deep on purpose - user_id is a nullable nullOnDelete FK
            // AND User soft-deletes, so the relation is null for a customer who
            // has since been removed and their email survives only in the
            // order's own snapshot.
            $this->csvCell(
                $order->user?->email
                    ?: (string) data_get($order->metadata, 'guest_email')
                    ?: (string) data_get($order->shipping_address_snapshot, 'email')
            ),
            $this->csvCell($order->customer_phone),
            ucfirst(str_replace('_', ' ', (string) $order->status)),
            ucfirst(str_replace('_', ' ', (string) $order->payment_status)),
            // De-fanged, and de-fanged AFTER the case fold so the tab survives.
            // This cell is not one of ours: the accessor reads
            // metadata['payment_method'], and the API checkout accepts that as a
            // bare ['required', 'string'] - so a customer with an app account
            // chooses its contents, and uppercasing neutralises nothing (Excel
            // function names and URL schemes are case-insensitive, and a DDE
            // payload like =CMD|'/C CALC'!A1 is already uppercase).
            $this->csvCell(strtoupper((string) $order->payment_method)),
            // The decimal:2 casts hand back strings, and a sheet cannot sum a
            // currency symbol - so a bare machine-readable number here, never
            // @price / format_price().
            number_format((float) $order->subtotal, 2, '.', ''),
            number_format((float) $order->discount, 2, '.', ''),
            number_format((float) $order->tax, 2, '.', ''),
            // The column is shipping_cost. $order->shipping does not exist and
            // would export a silent empty column.
            number_format((float) $order->shipping_cost, 2, '.', ''),
            number_format((float) $order->total, 2, '.', ''),
            number_format((float) $order->paid_amount, 2, '.', ''),
            number_format((float) $order->balance_due, 2, '.', ''),
            $this->csvCell($order->currency),
            (int) $order->items_count,
            // NULL for an order with no lines, so the cast is what keeps the
            // cell a 0 rather than an empty string.
            (int) $order->items_sum_quantity,
            $this->csvCell($order->coupon?->code),
            // The API checkout writes "api" into metadata and leaves the column
            // at its "web" default, so the column alone reports every app order
            // as a web one.
            $this->csvCell((string) (data_get($order->metadata, 'source') ?: $order->source)),
            $this->csvCell($address),
            $this->csvCell((string) data_get($order->shipping_address_snapshot, 'city')),
            $this->csvCell((string) data_get($order->shipping_address_snapshot, 'state')),
            $this->csvCell((string) data_get($order->shipping_address_snapshot, 'postal_code')),
            $this->csvCell((string) data_get($order->shipping_address_snapshot, 'country')),
            $this->csvCell($shipment?->carrier ?: (string) data_get($order->metadata, 'shiprocket_courier')),
            $this->csvCell($shipment?->tracking_number ?: (string) data_get($order->metadata, 'shiprocket_awb')),
            $this->csvCell($order->deliveryPartner?->user?->full_name),
            $order->confirmed_at?->format('Y-m-d H:i'),
            $order->packed_at?->format('Y-m-d H:i'),
            $order->shipped_at?->format('Y-m-d H:i'),
            $order->out_for_delivery_at?->format('Y-m-d H:i'),
            $order->delivered_at?->format('Y-m-d H:i'),
            $order->cancelled_at?->format('Y-m-d H:i'),
            // Date-only on purpose: the column is a date, there is no time in it.
            $order->expected_delivery_date?->format('Y-m-d'),
            $order->updated_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * The rules the list and the export share.
     *
     * Every one is nullable, because adding validate() to a list screen changes
     * its failure mode from "renders anyway" to "redirects back", and an admin
     * following a link that carries a stale parameter should not be bounced off
     * the page. What is left rejects only input that is genuinely wrong - a
     * status outside the enum, a page size that would divide by zero, a
     * calendar window that reads backwards. That last one IS refused rather
     * than corrected - the same refusal on the list and on the export, so the
     * two can never disagree - and the swap inside applyDateWindow() is what
     * catches the case a per-field rule cannot see: a `from` set against a
     * stale `date_to`, which satisfies both pairs while still reading
     * backwards.
     *
     * @return array<string, mixed>
     */
    private function filterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            // The ten enum members, read off the transition map so a status
            // added there cannot become unfilterable here.
            'status' => ['nullable', Rule::in(array_keys(Order::STATUS_TRANSITIONS))],
            'payment_status' => ['nullable', Rule::in(self::PAYMENT_STATUSES)],
            // Both pairs have to be listed. validate() returns only the keys it
            // was given rules for, so leaving the older aliases out would not
            // merely skip validating them - it would strip them, and every link
            // still pointing at ?date_from would silently stop filtering.
            ...$this->dateWindowRules(),
            ...$this->dateWindowRules('date_from', 'date_to'),
            // min:5 is the fix, not the ceiling: the old clamp had a maximum
            // and no minimum, so ?per_page=0 reached paginate(0) and the
            // paginator divided the total by it.
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'format' => $this->exportFormatRule(),
        ];
    }

    /**
     * The one builder both the list and the export are cut from.
     *
     * $applyStatus is off when the stat tiles are counted, so a tile keeps
     * reporting how many orders are in a status while the table is narrowed to
     * one of them - the same trick AbandonedCartController::statusCounts() uses
     * for its tabs.
     *
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters, bool $applyStatus = true): Builder
    {
        $query = Order::query();

        if ($applyStatus && ! empty($filters['status'])) {
            $query->where('orders.status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('orders.payment_status', $filters['payment_status']);
        }

        if (! empty($filters['search'])) {
            // % and _ are wildcards inside LIKE, so an unescaped search for
            // "50%" matches every order number from "50" onwards.
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';

            $query->where(function (Builder $q) use ($term) {
                $q->where('orders.order_number', 'like', $term)
                    ->orWhereHas('user', fn (Builder $uq) => $uq->where('email', 'like', $term));
            });
        }

        // Qualified column: the search above can add a whereHas over users, and
        // that table has a created_at of its own.
        return $this->applyDateWindow(
            $query,
            'orders.created_at',
            $this->windowEnd($filters, 'from', 'date_from'),
            $this->windowEnd($filters, 'to', 'date_to'),
        );
    }

    /**
     * One end of the calendar window, canonical name first.
     *
     * array_key_exists rather than ??, and that is the whole subtlety. The
     * date-range component submits both of its inputs whether or not they were
     * filled, and carries every other parameter on the URL through as a hidden
     * input - a stale ?date_from included. Empty strings arrive as null
     * (ConvertEmptyStringsToNull), so `$filters['from'] ?? $filters['date_from']`
     * would fall through to the alias exactly when the admin had just cleared
     * the boxes, and the window they thought they had removed would stay on.
     *
     * Present-but-empty therefore means "no window", and the alias is consulted
     * only when the canonical name was never sent at all - which is what an old
     * ?date_from= link looks like.
     *
     * @param  array<string, mixed>  $filters
     */
    private function windowEnd(array $filters, string $canonical, string $alias): mixed
    {
        return array_key_exists($canonical, $filters)
            ? $filters[$canonical]
            : ($filters[$alias] ?? null);
    }

    /**
     * The six tiles above the table, off the same filtered builder.
     *
     * They used to be six unfiltered COUNT queries, so a date-filtered page
     * showed a narrowed table underneath whole-table totals - the numbers and
     * the rows were answers to different questions. One GROUP BY now, with the
     * filters applied and the status clause left off, so the tiles keep their
     * meaning while a tab is selected.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function stats(array $filters): array
    {
        $counts = $this->filtered($filters, applyStatus: false)
            ->selectRaw('orders.status as status, COUNT(*) as aggregate')
            ->groupBy('orders.status')
            ->pluck('aggregate', 'status')
            ->all();

        $sum = fn (array $statuses) => (int) array_sum(
            array_map(fn (string $status) => (int) ($counts[$status] ?? 0), $statuses)
        );

        return [
            'total' => (int) array_sum($counts),
            'confirmed' => $sum(['confirmed']),
            // Grouped exactly as the tiles have always been grouped: "packed"
            // is a stage of processing and "out for delivery" a stage of
            // shipping, and neither has a tile of its own.
            'processing' => $sum(['processing', 'packed']),
            'shipped' => $sum(['shipped', 'out_for_delivery']),
            'completed' => $sum(['delivered']),
            'cancelled' => $sum(['cancelled']),
        ];
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'items.product',
            'items.variant',
            'statusHistory',
            'shipments',
            'coupon',
            'deliveryPartner.user',
        ]);

        $trackingSteps = $order->getTrackingSteps();
        $latestShipment = $order->shipments->first();
        $activePartners = DeliveryPartner::with('user')->where('is_active', true)->get();
        $shiprocketEnabled = ShiprocketService::isEnabled();

        return view('admin.orders.show', compact('order', 'trackingSteps', 'latestShipment', 'activePartners', 'shiprocketEnabled'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,processing,packed,shipped,out_for_delivery,delivered,cancelled,returned'],
            'comment' => ['nullable', 'string', 'max:500'],
            'carrier' => ['nullable', 'required_if:status,shipped', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'required_if:status,shipped', 'string', 'max:100'],
        ]);

        $oldStatus = $order->status;

        // Validate state transitions against the workflow on the model, so the
        // dropdown and this guard can never drift apart. The model hands back
        // the reason so an admin blocked by the unpaid-prepaid rule is told
        // that, rather than a generic "cannot change status" that points at the
        // workflow they did follow.
        if ($reason = $order->transitionBlockedReason($validated['status'])) {
            return back()->with('error', $reason);
        }

        // Auto-push to Shiprocket when moving to "processing" (or "packed")
        $shiprocketPushed = false;
        if (ShiprocketService::isEnabled() && in_array($validated['status'], ['processing', 'packed'])) {
            $hasShiprocket = !empty($order->metadata['shiprocket_order_id']);
            if (!$hasShiprocket) {
                try {
                    $shiprocket = new ShiprocketService();
                    $result = $shiprocket->pushOrder($order);
                    $shiprocketPushed = true;
                } catch (\Exception $e) {
                    return back()->with('error', 'Shiprocket: ' . $e->getMessage());
                }
            }
        }

        // If shipping, create shipment record (only if not already created by Shiprocket)
        if ($validated['status'] === 'shipped' && !empty($validated['tracking_number'])) {
            $existingShipment = $order->shipments()->where('carrier_code', 'shiprocket')->first();
            if (!$existingShipment) {
                $order->shipments()->create([
                    'carrier' => $validated['carrier'],
                    'tracking_number' => $validated['tracking_number'],
                    'status' => 'in_transit',
                    'shipped_at' => now(),
                ]);
            }
        }

        // Update shipment status for out_for_delivery and delivered
        if (in_array($validated['status'], ['out_for_delivery', 'delivered'])) {
            $shipment = $order->shipments()->latest()->first();
            if ($shipment) {
                $shipmentStatus = $validated['status'] === 'out_for_delivery' ? 'out_for_delivery' : 'delivered';
                $shipment->update(['status' => $shipmentStatus]);
                if ($validated['status'] === 'delivered') {
                    $shipment->update(['delivered_at' => now()]);
                }
            }
        }

        $order->updateStatus($validated['status'], auth('admin')->id(), $validated['comment'] ?? null);

        OrderStatusChanged::dispatch($order, $oldStatus, $validated['status']);

        if ($validated['status'] === 'shipped') {
            $trackingNumber = $validated['tracking_number'] ?? $order->metadata['shiprocket_awb'] ?? null;
            OrderShipped::dispatch($order, $trackingNumber);
        } elseif ($validated['status'] === 'delivered') {
            OrderDelivered::dispatch($order);
        }

        $msg = "Order status updated from {$oldStatus} to {$validated['status']}";
        if ($shiprocketPushed) {
            $msg .= '. Pushed to Shiprocket automatically.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Settle an order's payment by hand. Needed because a prepaid order can no
     * longer be shipped while it reads unpaid, and payment_status had no admin
     * route at all - so a gateway callback that never arrived, or a customer
     * who paid by bank transfer, left the order unshippable with no way out.
     */
    public function recordPayment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:paid,failed,pending'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (in_array($order->payment_status, ['refunded', 'partial_refund'], true)) {
            return back()->with('error', 'This order has been refunded. Its payment status cannot be edited here.');
        }

        $order->update([
            'payment_status' => $validated['payment_status'],
            'paid_amount'    => $validated['payment_status'] === 'paid' ? $order->total : 0,
        ]);

        // Record it on the timeline at the order's current status: this is not
        // a fulfilment change, but an admin needs to see who marked it paid.
        $order->statusHistory()->create([
            'status'     => $order->status,
            'comment'    => trim("Payment marked as {$validated['payment_status']}. " . ($validated['note'] ?? '')),
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', "Payment marked as {$validated['payment_status']}.");
    }

    public function ship(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'carrier' => ['required', 'string', 'max:100'],
            'tracking_number' => ['required', 'string', 'max:100'],
        ]);

        $order->shipments()->create([
            'carrier' => $validated['carrier'],
            'tracking_number' => $validated['tracking_number'],
            'status' => 'in_transit',
            'shipped_at' => now(),
        ]);

        $order->updateStatus('shipped', auth('admin')->id(), "Shipped via {$validated['carrier']} - Tracking: {$validated['tracking_number']}");

        OrderShipped::dispatch($order, $validated['tracking_number']);

        return back()->with('success', 'Order marked as shipped');
    }

    public function invoice(Order $order): View
    {
        $order->load(['user', 'items.product']);

        return view('admin.orders.invoice', compact('order'));
    }

    public function assignPartner(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_partner_id' => 'nullable|exists:delivery_partners,id',
        ]);

        $order->update(['delivery_partner_id' => $validated['delivery_partner_id']]);

        // Also update latest shipment
        $shipment = $order->shipments()->latest()->first();
        if ($shipment) {
            $shipment->update(['delivery_partner_id' => $validated['delivery_partner_id']]);
        }

        if ($validated['delivery_partner_id']) {
            $partner = DeliveryPartner::with('user')->find($validated['delivery_partner_id']);
            if ($partner && $partner->user) {
                $order->statusHistory()->create([
                    'status' => $order->status,
                    'comment' => "Delivery partner assigned: {$partner->user->full_name} ({$partner->partner_id})",
                    'created_by' => auth('admin')->id(),
                ]);
            }
        }

        return back()->with('success', 'Delivery partner assigned successfully.');
    }

    public function setExpectedDelivery(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
        ]);

        $order->update(['expected_delivery_date' => $request->expected_delivery_date ?: null]);

        return back()->with('success', $request->expected_delivery_date
            ? 'Expected delivery date set to ' . Carbon::parse($request->expected_delivery_date)->format('M d, Y') . '.'
            : 'Expected delivery date cleared.');
    }

    public function packingSlip(Order $order): View
    {
        $order->load(['items.product']);

        return view('admin.orders.packing-slip', compact('order'));
    }

    /**
     * Manually push order to Shiprocket.
     */
    public function pushToShiprocket(Order $order): RedirectResponse
    {
        if (!ShiprocketService::isEnabled()) {
            return back()->with('error', 'Shiprocket is not enabled.');
        }

        if (!empty($order->metadata['shiprocket_order_id'])) {
            return back()->with('error', 'Order is already on Shiprocket (ID: ' . $order->metadata['shiprocket_order_id'] . ')');
        }

        try {
            $shiprocket = new ShiprocketService();
            $result = $shiprocket->pushOrder($order);

            $msg = 'Order pushed to Shiprocket.';
            if (!empty($result['awb_code'])) {
                $msg .= ' AWB: ' . $result['awb_code'] . ' via ' . ($result['courier_name'] ?? 'auto');
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Shiprocket: ' . $e->getMessage());
        }
    }

    /**
     * Sync tracking from Shiprocket.
     */
    public function syncShiprocketTracking(Order $order): RedirectResponse
    {
        if (empty($order->metadata['shiprocket_shipment_id'])) {
            return back()->with('error', 'No Shiprocket shipment found for this order.');
        }

        try {
            $shiprocket = new ShiprocketService();
            $tracking = $shiprocket->syncTracking($order);

            if ($tracking) {
                return back()->with('success', 'Tracking synced from Shiprocket.');
            }
            return back()->with('error', 'No tracking data available yet.');
        } catch (\Exception $e) {
            return back()->with('error', 'Shiprocket tracking: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order on Shiprocket.
     */
    public function cancelShiprocket(Order $order): RedirectResponse
    {
        $shiprocketOrderId = $order->metadata['shiprocket_order_id'] ?? null;
        if (!$shiprocketOrderId) {
            return back()->with('error', 'Order is not on Shiprocket.');
        }

        try {
            $shiprocket = new ShiprocketService();
            $shiprocket->cancelOrder($shiprocketOrderId);

            return back()->with('success', 'Order cancelled on Shiprocket.');
        } catch (\Exception $e) {
            return back()->with('error', 'Shiprocket cancel: ' . $e->getMessage());
        }
    }
}
