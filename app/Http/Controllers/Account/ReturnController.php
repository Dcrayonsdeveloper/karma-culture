<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\Setting;
use App\Rules\ValidationRules as V;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnController extends Controller
{
    /**
     * The reasons the form offers.
     *
     * The list lived only in the blade, so the server took any string up to 255
     * characters - including markup - and wrote it to returns.reason, which the
     * admin queue then renders. The view now renders this constant and the
     * write validates against it.
     */
    public const REASONS = [
        'Defective or damaged product',
        'Wrong item received',
        "Item doesn't match description",
        'Allergic reaction',
        'Changed my mind',
        'Better price available',
        'Other',
    ];

    /** The two things a customer can ask for. */
    public const TYPES = ['return', 'exchange'];

    /** The condition options offered per item. */
    public const CONDITIONS = ['unopened', 'opened', 'damaged'];

    public function index(Request $request): View
    {
        $returns = OrderReturn::whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['order:id,order_number', 'items.orderItem.product:id,name,slug'])
            ->latest()
            ->paginate(10);

        return view('account.returns.index', compact('returns'));
    }

    public function create(Request $request): View
    {
        // Get IDs of order items that already have a return request (any status except rejected)
        $returnedItemIds = ReturnItem::whereHas('return', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
                ->where('status', '!=', 'rejected');
        })->pluck('order_item_id')->toArray();

        $returnWindowDays = (int) Setting::get('return_window_days', 7);
        $returnMinHours = (int) Setting::get('return_min_hours', 24);

        $orders = $request->user()->orders()
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', now()->subDays($returnWindowDays))
            ->where('delivered_at', '<=', now()->subHours($returnMinHours))
            ->with('items.product:id,name,slug')
            ->get();

        // Filter out items that already have return requests
        $orders->each(function ($order) use ($returnedItemIds) {
            $order->setRelation('items', $order->items->reject(fn ($item) => in_array($item->id, $returnedItemIds)));
        });

        // Remove orders with no returnable items left
        $orders = $orders->filter(fn ($order) => $order->items->isNotEmpty());

        return view('account.returns.create', [
            'orders' => $orders,
            'returnWindowDays' => $returnWindowDays,
            'returnMinHours' => $returnMinHours,
            'reasons' => self::REASONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // exists alone only proves the order is real. Scoping it to the
            // signed-in user means someone else's order number fails as a form
            // error here rather than reaching the query below.
            'order_id' => [
                'required',
                'integer',
                Rule::exists('orders', 'id')->where('user_id', $request->user()->id),
            ],
            'type' => V::option(self::TYPES),
            // Was a free string: now one of the reasons the select offers.
            'reason' => V::option(self::REASONS),
            // NoHtml, so a description later rendered in the admin queue or an
            // email cannot carry markup.
            'description' => V::textarea(required: false, max: 1000),
            // max:50 bounds the array itself - without it a hand-rolled post
            // could ask the server to validate an unlimited number of items.
            'items' => 'required|array|min:1|max:50',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.quantity' => V::quantity(),
            'items.*.reason' => V::text(required: false, max: 500),
            'items.*.condition' => V::option(self::CONDITIONS),
        ], [
            'order_id.exists' => 'Please choose one of your delivered orders.',
            'type.in' => 'Please choose whether you want a return or an exchange.',
            'reason.in' => 'Please choose a reason from the list.',
            'items.required' => 'Please select at least one item to return.',
        ]);

        // Verify the order belongs to the authenticated user and is still one a
        // return can be raised against.
        //
        // This was firstOrFail(). The `exists` rule above already proves the
        // order is real and the customer's, so the only thing left that can miss
        // here is the status: an order that was 'delivered' when the page was
        // rendered and has since been moved on - a support agent reopening it,
        // a courier reversal - which the form's own list would no longer offer.
        // That is a state failure the customer did nothing to cause, and a bare
        // 404 page answered it by throwing away every item, quantity, reason and
        // description they had just filled in. It is reported on the field that
        // chose the order, alongside the other messages on this form, with the
        // rest of the input flashed back.
        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'delivered')
            ->first();

        if (! $order) {
            return back()->withInput()->withErrors([
                'order_id' => 'This order is no longer eligible for a return. Please pick another order or contact support.',
            ]);
        }

        // Verify each item belongs to this order and return qty doesn't exceed ordered qty
        $submittedItemIds = collect($validated['items'])->pluck('order_item_id')->toArray();
        $orderItems = $order->items()->whereIn('id', $submittedItemIds)->get()->keyBy('id');

        foreach ($validated['items'] as $item) {
            if (! isset($orderItems[$item['order_item_id']])) {
                abort(403, 'One or more items do not belong to this order.');
            }
            if ($item['quantity'] > $orderItems[$item['order_item_id']]->quantity) {
                return back()->withInput()->withErrors(['items' => 'Return quantity cannot exceed the ordered quantity.']);
            }
        }

        // Check for duplicate return requests on the submitted items
        $alreadyReturned = ReturnItem::whereIn('order_item_id', $submittedItemIds)
            ->whereHas('return', fn ($q) => $q->where('status', '!=', 'rejected'))
            ->exists();

        if ($alreadyReturned) {
            return back()->withInput()->withErrors(['items' => 'One or more selected items already have a return request.']);
        }

        $return = OrderReturn::create([
            'order_id' => $validated['order_id'],
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'requested',
        ]);

        foreach ($validated['items'] as $item) {
            $return->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity' => $item['quantity'],
                'reason' => $item['reason'] ?? null,
                'condition' => $item['condition'],
            ]);
        }

        // A return the customer raises themselves used to notify nobody:
        // ReturnRequested is dispatched only when an ADMIN touches the row
        // (Admin\ReturnController), so a request made here sat in the queue
        // until somebody happened to look at the list.
        //
        // Logged and swallowed on failure. The return and its items are already
        // written by this point, so a notification that throws would show the
        // customer an error page for a request that was in fact submitted.
        try {
            app(NotificationService::class)->notifyAdmins(
                'new_return_request',
                'New Return Request',
                "{$request->user()->full_name} requested a {$return->type} (#{$return->return_number}) for order #{$order->order_number}",
                [
                    'return_id' => $return->id,
                    'return_number' => $return->return_number,
                    'order_id' => $order->id,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify admins of a return request', [
                'return_id' => $return->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('account.returns.show', $return)
            ->with('success', 'Return request submitted successfully.');
    }

    public function show(Request $request, OrderReturn $return): View
    {
        if ($return->order->user_id !== $request->user()->id) {
            abort(403);
        }

        $return->load(['order', 'items.orderItem.product:id,name,slug', 'pickupPartner.user']);

        return view('account.returns.show', compact('return'));
    }
}
