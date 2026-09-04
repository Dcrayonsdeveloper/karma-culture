<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\AbandonedCartService;
use App\Services\ReportExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbandonedCartController extends Controller
{
    /** Columns a list can be ordered by. orderBy() cannot bind an identifier. */
    private const SORTABLE = [
        'abandoned_at' => 'abandoned_carts.abandoned_at',
        'last_activity_at' => 'abandoned_carts.last_activity_at',
        'total' => 'abandoned_carts.total',
        'item_count' => 'abandoned_carts.item_count',
        'reminder_count' => 'abandoned_carts.reminder_count',
    ];

    /** The settings this screen owns, and their form field names. */
    private const SETTING_KEYS = [
        'threshold_hours' => 'abandoned_cart_threshold_hours',
        'expiry_days' => 'abandoned_cart_expiry_days',
        'reminder_cooldown_hours' => 'abandoned_cart_reminder_cooldown_hours',
        'max_reminders' => 'abandoned_cart_max_reminders',
        'recovery_link_days' => 'abandoned_cart_recovery_link_days',
        'recent_hours' => 'abandoned_cart_recent_hours',
    ];

    public function __construct(private AbandonedCartService $service) {}

    public function index(Request $request): View
    {
        // Neither `schedule:run` nor a queue worker runs on this host - the
        // scheduled detection command has never fired once - so opening the
        // screen is what keeps it current. Throttled inside the service, and it
        // swallows its own failures so a scan can never break the page.
        $this->service->syncThrottled();

        $filters = $request->validate($this->filterRules());

        $query = $this->filtered($filters);

        $sort = self::SORTABLE[$filters['sort'] ?? 'abandoned_at'] ?? self::SORTABLE['abandoned_at'];
        $direction = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $carts = $query
            ->with(['user', 'cart.items', 'recoveredOrder'])
            ->orderBy($sort, $direction)
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        // Tab counts come off the SAME filtered builder minus the status clause,
        // so a tab number can never contradict the rows underneath it.
        $counts = $this->statusCounts($filters);

        $stats = $this->service->stats();
        $range = ['from' => $filters['from'] ?? null, 'to' => $filters['to'] ?? null];

        return view('admin.abandoned-carts.index', compact('carts', 'counts', 'stats', 'filters', 'range'));
    }

    public function show(AbandonedCart $abandonedCart): View
    {
        // Deliberately a read. Cart::recalculate() would re-price the lines and
        // could attach a coupon the customer never chose - opening an admin page
        // must not change a customer's basket.
        $abandonedCart->load([
            'user',
            // product.images feeds the primary_image_url accessor. `variant` is
            // loaded bare on purpose: attributeValues is a virtual accessor
            // over the JSON column, not a relation, so eager-loading it throws.
            'cart.items.product.images',
            'cart.items.variant',
            'cart.coupon',
            'recoveredOrder',
        ]);

        $blockedReason = $this->service->reminderBlockedReason($abandonedCart);
        $linkExpiresAt = $this->service->recoveryLinkExpiresAt($abandonedCart);

        // Other episodes of the same basket. The cart row is recycled after
        // every checkout, so this is genuinely a history, not a duplicate.
        $history = AbandonedCart::where('cart_id', $abandonedCart->cart_id)
            ->where('id', '!=', $abandonedCart->id)
            ->orderByDesc('abandoned_at')
            ->limit(10)
            ->get();

        return view('admin.abandoned-carts.show', compact(
            'abandonedCart', 'blockedReason', 'linkExpiresAt', 'history'
        ));
    }

    /** Run detection and reconciliation now, rather than waiting for the throttle. */
    public function scan(): RedirectResponse
    {
        Cache::forget('abandoned_carts.last_sync');

        $result = $this->service->sync();

        return back()->with('success', sprintf(
            'Scan complete: %d newly abandoned, %d recovered, %d expired.',
            $result['detected'],
            $result['recovered'],
            $result['expired'],
        ));
    }

    public function remind(AbandonedCart $abandonedCart): RedirectResponse
    {
        if ($failure = $this->service->sendReminder($abandonedCart)) {
            // A blocked or failed send is a business outcome, not a 4xx - the
            // admin gets told what went wrong and the row keeps its state.
            return back()->with('warning', $failure);
        }

        return back()->with('success', 'Recovery email sent to '.$abandonedCart->contactEmail().'.');
    }

    public function markContacted(AbandonedCart $abandonedCart): RedirectResponse
    {
        if (! $abandonedCart->isOpen()) {
            return back()->with('warning', 'This cart is already closed, so it cannot be marked as contacted.');
        }

        $this->service->markContacted($abandonedCart);

        return back()->with('success', 'Marked as contacted.');
    }

    public function markRecovered(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
        ]);

        if ($abandonedCart->isRecovered()) {
            return back()->with('warning', 'This cart is already marked as recovered.');
        }

        $order = isset($validated['order_id']) ? Order::find($validated['order_id']) : null;

        // Guard the one thing that must never be wrong: an order belonging to
        // somebody else would credit this recovery to the wrong customer and
        // inflate recovered revenue.
        if ($order && $abandonedCart->user_id && $order->user_id !== $abandonedCart->user_id) {
            return back()->with('error', 'That order belongs to a different customer, so it cannot be linked to this cart.');
        }

        $this->service->markRecoveredManually($abandonedCart, $order);

        return back()->with('success', 'Marked as recovered.');
    }

    public function archive(AbandonedCart $abandonedCart): RedirectResponse
    {
        if ($abandonedCart->isRecovered()) {
            return back()->with('warning', 'A recovered cart is part of the recovery figures and cannot be archived.');
        }

        $this->service->archive($abandonedCart);

        return back()->with('success', 'Cart archived. It no longer counts towards the recovery rate.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['remind', 'contacted', 'archive'])],
            // One hidden ids[] input per ticked row, like the Newsletter screen.
            // The ceiling matters more here than there: each reminder is a
            // blocking SMTP round trip, so an unbounded list would hang the
            // request until it timed out.
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['integer', Rule::exists('abandoned_carts', 'id')],
        ]);

        $episodes = AbandonedCart::with(['cart.items', 'user'])
            ->whereIn('id', $validated['ids'])
            ->get();

        $done = 0;
        $skipped = 0;

        foreach ($episodes as $episode) {
            // Each branch reports whether it actually did something, so a cart
            // that was skipped for a good reason (already closed, no email,
            // still inside the reminder cooldown) is counted as skipped rather
            // than reported back as done.
            $ok = match ($validated['action']) {
                'remind' => $this->service->sendReminder($episode) === null,
                'contacted' => $this->applyIfOpen($episode),
                'archive' => $this->archiveIfNotRecovered($episode),
            };

            $ok ? $done++ : $skipped++;
        }

        $message = "{$done} cart(s) updated.";

        return $skipped > 0
            ? back()->with('warning', $message." {$skipped} were skipped - open one to see why.")
            : back()->with('success', $message);
    }

    public function export(Request $request, ReportExportService $exporter): StreamedResponse
    {
        // Same rules and the same builder as index(), so an export can never
        // disagree with the list it was taken from.
        $filters = $request->validate($this->filterRules());

        $rows = $this->filtered($filters)
            ->with(['user', 'cart.items', 'recoveredOrder'])
            ->orderByDesc('abandoned_at')
            ->get()
            ->map(fn (AbandonedCart $c) => [
                $c->id,
                $c->cart_id,
                $this->csvCell($c->customerName()),
                $this->csvCell($c->contactEmail()),
                $this->csvCell($c->contactPhone()),
                $c->item_count,
                $c->quantity,
                number_format((float) $c->total, 2, '.', ''),
                $c->currency,
                $c->last_activity_at?->format('Y-m-d H:i'),
                $c->abandoned_at->format('Y-m-d H:i'),
                $c->cartStatus(),
                $c->recovery_status,
                $c->reminder_count,
                $c->last_reminder_at?->format('Y-m-d H:i'),
                $c->recovered_at?->format('Y-m-d H:i'),
                $this->csvCell($c->recoveredOrder?->order_number),
            ]);

        return $exporter->exportCsv(
            ['Cart episode', 'Cart ID', 'Customer', 'Email', 'Phone', 'Items', 'Units', 'Total', 'Currency',
                'Last activity', 'Abandoned at', 'Cart status', 'Recovery status', 'Reminders',
                'Last reminder', 'Recovered at', 'Recovered order'],
            $rows,
            'abandoned-carts-'.now()->format('Y-m-d-His').'.csv',
        );
    }

    public function settings(): View
    {
        $settings = [
            'threshold_hours' => $this->service->thresholdHours(),
            'expiry_days' => $this->service->expiryDays(),
            'reminder_cooldown_hours' => $this->service->reminderCooldownHours(),
            'max_reminders' => $this->service->maxReminders(),
            'recovery_link_days' => $this->service->recoveryLinkDays(),
            'recent_hours' => $this->service->recentHours(),
        ];

        return view('admin.abandoned-carts.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'threshold_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            'reminder_cooldown_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'max_reminders' => ['required', 'integer', 'min:1', 'max:10'],
            'recovery_link_days' => ['required', 'integer', 'min:1', 'max:365'],
            'recent_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);

        foreach ($validated as $field => $value) {
            Setting::set(self::SETTING_KEYS[$field], (string) $value, 'integer', 'abandoned_cart');
        }

        // Setting::get() caches per key for an hour, and getGroup() caches the
        // group separately again - Setting::set() only clears the former.
        Cache::forget('settings.group.abandoned_cart');

        return back()->with('success', 'Abandoned cart settings updated.');
    }

    private function applyIfOpen(AbandonedCart $episode): bool
    {
        if (! $episode->isOpen()) {
            return false;
        }

        $this->service->markContacted($episode);

        return true;
    }

    private function archiveIfNotRecovered(AbandonedCart $episode): bool
    {
        // A recovered cart is part of the recovery figures; archiving it in
        // bulk would quietly move the recovery rate.
        if ($episode->isRecovered()) {
            return false;
        }

        $this->service->archive($episode);

        return true;
    }

    /** @return array<string,mixed> */
    private function filterRules(): array
    {
        return [
            'status' => ['nullable', Rule::in(array_merge(AbandonedCart::STATUSES, ['recent']))],
            'search' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'min_total' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max_total' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'min_items' => ['nullable', 'integer', 'min:1', 'max:999'],
            'customer' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'product' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTABLE))],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            // Unbounded per_page is a live denial-of-service class in this
            // codebase; several controllers carry the same clamp.
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    /** @param array<string,mixed> $filters */
    private function filtered(array $filters, bool $applyStatus = true): Builder
    {
        $query = AbandonedCart::query();

        if ($applyStatus && ! empty($filters['status'])) {
            if ($filters['status'] === 'recent') {
                $query->where('abandoned_at', '>=', now()->subHours($this->service->recentHours()))
                    ->open();
            } else {
                $query->where('recovery_status', $filters['status']);
            }
        }

        if (! empty($filters['search'])) {
            // % and _ are wildcards inside LIKE, so a search for "50%" would
            // otherwise match everything from "50" onwards.
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';
            $numeric = ctype_digit(trim($filters['search'])) ? (int) trim($filters['search']) : null;

            $query->where(function (Builder $q) use ($term, $numeric) {
                $q->whereHas('user', function (Builder $uq) use ($term) {
                    $uq->where('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term);
                });

                if ($numeric !== null) {
                    $q->orWhere('abandoned_carts.id', $numeric)
                        ->orWhere('abandoned_carts.cart_id', $numeric);
                }
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('abandoned_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('abandoned_at', '<=', $filters['to']);
        }

        if (isset($filters['min_total']) && $filters['min_total'] !== null) {
            $query->where('total', '>=', $filters['min_total']);
        }

        if (isset($filters['max_total']) && $filters['max_total'] !== null) {
            $query->where('total', '<=', $filters['max_total']);
        }

        if (! empty($filters['min_items'])) {
            $query->where('item_count', '>=', $filters['min_items']);
        }

        if (! empty($filters['customer'])) {
            $query->where('user_id', $filters['customer']);
        }

        if (! empty($filters['product'])) {
            // Against the live basket, not the snapshot: the snapshot stores
            // totals, not which products were in it.
            $query->whereHas('cart.items', fn (Builder $iq) => $iq->where('product_id', $filters['product']));
        }

        return $query;
    }

    /**
     * One grouped query for every tab, padded with zeros so a status with no
     * rows still renders "0" rather than disappearing.
     *
     * @param array<string,mixed> $filters
     * @return array<string,int>
     */
    private function statusCounts(array $filters): array
    {
        $counts = $this->filtered($filters, applyStatus: false)
            ->selectRaw('recovery_status, COUNT(*) as total')
            ->groupBy('recovery_status')
            ->pluck('total', 'recovery_status')
            ->all();

        $padded = array_replace(array_fill_keys(AbandonedCart::STATUSES, 0), $counts);
        $padded['all'] = array_sum($padded);
        $padded['recent'] = (int) $this->filtered($filters, applyStatus: false)
            ->where('abandoned_at', '>=', now()->subHours($this->service->recentHours()))
            ->open()
            ->count();

        return array_map('intval', $padded);
    }

    /**
     * De-fang one CSV field.
     *
     * A value opening with =, +, - or @ is a formula to Excel and Google
     * Sheets, so a customer who registers as `=HYPERLINK("http://evil","hi")`
     * gets that executed in the spreadsheet of whoever opens the export. A
     * leading tab makes it text and is invisible in the sheet. Quoting is left
     * to fputcsv inside ReportExportService - doubling it here would put
     * literal quotes in every cell.
     */
    private function csvCell(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            return "\t".$value;
        }

        return $value;
    }
}
