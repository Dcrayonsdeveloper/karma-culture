<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackOrderController extends Controller
{
    public function index(): View
    {
        return view('track-order.index');
    }

    public function track(Request $request): View
    {
        $rules = [
            'order_number' => ['required', 'string', 'max:40'],
            // Signed-in customers can look up their own orders with just the
            // number; everyone else proves ownership with the mobile number.
            'phone' => [auth()->check() ? 'nullable' : 'required', 'string', 'max:20'],
        ];

        $validated = $request->validate($rules, [
            'phone.required' => 'Please enter the mobile number used for the order.',
        ]);

        $order = Order::where('order_number', trim($validated['order_number']))
            ->with(['items.product', 'shipments', 'statusHistory', 'deliveryPartner.user'])
            ->first();

        if ($order && ! $this->canView($order, $validated['phone'] ?? null)) {
            $order = null;
        }

        if (! $order) {
            // Deliberately the same message whether the order does not exist or
            // the mobile number does not match, so this cannot be used to probe
            // which order numbers are real.
            return view('track-order.index', [
                'error' => 'We could not find that order. Check the order number and the mobile number used when ordering.',
            ]);
        }

        // Remember that this visitor proved ownership of the order, so the guest
        // return form can trust them without asking for the details again.
        $request->session()->push('tracked_orders', $order->id);

        return view('track-order.show', [
            'order' => $order,
            'latestShipment' => $order->shipments->first(),
        ]);
    }

    /**
     * An order is viewable when it belongs to the signed-in customer, or when
     * the supplied mobile number matches the one the order was placed with.
     */
    private function canView(Order $order, ?string $phone): bool
    {
        if (auth()->check() && $order->user_id === auth()->id()) {
            return true;
        }

        $given = $this->normalisePhone($phone);

        if ($given === '') {
            return false;
        }

        foreach ($this->orderPhones($order) as $candidate) {
            if ($this->normalisePhone($candidate) === $given) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every phone number associated with an order. Guest checkout stores the
     * contact details on the address snapshots rather than against a user.
     */
    private function orderPhones(Order $order): array
    {
        return array_filter([
            data_get($order->shipping_address_snapshot, 'phone'),
            data_get($order->billing_address_snapshot, 'phone'),
            $order->shippingAddress?->phone,
            $order->user?->phone,
        ]);
    }

    /**
     * Compare on the last 10 digits so spacing, dashes and a +91 country code
     * do not stop a customer finding their own order.
     */
    private function normalisePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }
}
