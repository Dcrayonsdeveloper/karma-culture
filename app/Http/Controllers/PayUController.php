<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayUController extends Controller
{
    private function getConfig(): array
    {
        return [
            'key'  => Setting::get('payu_merchant_key', ''),
            'salt' => Setting::get('payu_merchant_salt', ''),
            'mode' => Setting::get('payu_mode', 'test'),
        ];
    }

    private function getBaseUrl(string $mode): string
    {
        return $mode === 'live'
            ? 'https://secure.payu.in/_payment'
            : 'https://test.payu.in/_payment';
    }

    /**
     * Generate PayU hash: sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)
     */
    private function generateHash(array $params, string $salt): string
    {
        $hashString = $params['key'] . '|' .
            $params['txnid'] . '|' .
            $params['amount'] . '|' .
            $params['productinfo'] . '|' .
            $params['firstname'] . '|' .
            $params['email'] . '|' .
            ($params['udf1'] ?? '') . '|' .
            ($params['udf2'] ?? '') . '|' .
            ($params['udf3'] ?? '') . '|' .
            ($params['udf4'] ?? '') . '|' .
            ($params['udf5'] ?? '') . '|' .
            '|||||' . $salt;

        return strtolower(hash('sha512', $hashString));
    }

    /**
     * Verify PayU response hash (reverse hash)
     */
    private function verifyHash(array $params, string $salt): bool
    {
        $status = $params['status'] ?? '';
        $hashString = $salt . '|' .
            $status . '|' .
            '|||||' .
            ($params['udf5'] ?? '') . '|' .
            ($params['udf4'] ?? '') . '|' .
            ($params['udf3'] ?? '') . '|' .
            ($params['udf2'] ?? '') . '|' .
            ($params['udf1'] ?? '') . '|' .
            ($params['email'] ?? '') . '|' .
            ($params['firstname'] ?? '') . '|' .
            ($params['productinfo'] ?? '') . '|' .
            ($params['amount'] ?? '') . '|' .
            ($params['txnid'] ?? '') . '|' .
            ($params['key'] ?? '');

        $expectedHash = strtolower(hash('sha512', $hashString));

        return $expectedHash === ($params['hash'] ?? '');
    }

    /**
     * Initiate PayU payment — redirects user to PayU payment page
     */
    public function initiate(Order $order)
    {
        // Checkout is guest-friendly: the owner is either the logged-in user
        // or the guest session that placed the order (same scheme as
        // CheckoutController::success). A bare null === null comparison would
        // have let any guest initiate payment for any guest order.
        $ownsAsUser  = auth()->check() && $order->user_id === auth()->id();
        $ownsAsGuest = in_array($order->id, session('guest_order_ids', []), true);
        abort_unless($ownsAsUser || $ownsAsGuest, 403);
        abort_unless($order->payment_status === 'pending', 400, 'Order already paid.');

        $config = $this->getConfig();

        if (empty($config['key']) || empty($config['salt']) || Setting::get('payu_enabled', '0') !== '1') {
            return redirect()->route('checkout.failed')
                ->with('error', 'Payment gateway not configured. Please contact support.');
        }

        // Customer details come from the order's own address snapshot, which
        // exists for guests and users alike.
        $snap = $order->shipping_address_snapshot ?? [];
        $txnid = 'TXN-' . $order->order_number . '-' . time();

        $params = [
            'key'         => $config['key'],
            'txnid'       => $txnid,
            'amount'      => number_format($order->total, 2, '.', ''),
            'productinfo' => 'Order #' . $order->order_number,
            'firstname'   => $snap['name'] ?? 'Customer',
            'email'       => $snap['email'] ?? auth()->user()?->email ?? '',
            'phone'       => $snap['phone'] ?? '',
            'surl'        => route('payu.success'),
            'furl'        => route('payu.failure'),
            'udf1'        => (string) $order->id,
            'udf2'        => '',
            'udf3'        => '',
            'udf4'        => '',
            'udf5'        => '',
        ];

        $params['hash'] = $this->generateHash($params, $config['salt']);

        // Create a pending payment record
        Payment::create([
            'order_id'       => $order->id,
            'transaction_id' => $txnid,
            'gateway'        => 'payu',
            'method'         => 'card', // placeholder — updated on callback with actual method
            'amount'         => $order->total,
            'currency'       => 'INR',
            'status'         => 'pending',
            'ip_address'     => request()->ip(),
        ]);

        // Store txnid in order metadata for later verification
        $metadata = $order->metadata ?? [];
        $metadata['payu_txnid'] = $txnid;
        $order->update(['metadata' => $metadata]);

        $payuUrl = $this->getBaseUrl($config['mode']);

        // Return an auto-submitting form that posts to PayU
        return view('checkout.payu-redirect', compact('params', 'payuUrl'));
    }

    /**
     * PayU success callback (surl)
     */
    public function success(Request $request)
    {
        $config = $this->getConfig();
        $params = $request->all();

        Log::info('PayU Success Callback', ['params' => $params]);

        // Verify hash
        if (!$this->verifyHash($params, $config['salt'])) {
            Log::warning('PayU hash verification failed on success callback', ['txnid' => $params['txnid'] ?? 'unknown']);
            return redirect()->route('checkout.failed')
                ->with('error', 'Payment verification failed. If money was debited, it will be refunded in 5-7 business days.');
        }

        $orderId = $params['udf1'] ?? null;
        $txnid = $params['txnid'] ?? '';
        $mihpayid = $params['mihpayid'] ?? '';
        $status = $params['status'] ?? '';

        $order = Order::find($orderId);

        if (!$order) {
            Log::error('PayU success callback: Order not found', ['order_id' => $orderId]);
            return redirect()->route('checkout.failed')
                ->with('error', 'Order not found. Please contact support.');
        }

        // Find the payment record
        $payment = Payment::where('transaction_id', $txnid)
            ->where('order_id', $order->id)
            ->first();

        if ($status === 'success') {
            // Map PayU mode to our method enum
            $payuMode = $params['mode'] ?? 'CC';
            $methodMap = ['CC' => 'card', 'DC' => 'card', 'NB' => 'netbanking', 'UPI' => 'upi', 'WALLET' => 'wallet', 'EMI' => 'emi', 'BNPL' => 'bnpl'];

            if ($payment) {
                // A replayed callback must not re-capture an already-settled
                // payment; PayU can retry, and the browser can resubmit.
                if ($payment->status === 'captured') {
                    return redirect()->route('checkout.success', $order);
                }

                $payment->update(['method' => $methodMap[strtoupper($payuMode)] ?? 'card']);
                $payment->markAsAuthorized($mihpayid, $params);
                $payment->markAsCaptured();

                // markAsCaptured() sets payment_status but not the amount, so
                // without this a paid order still reads as ₹0 received in admin.
                $order->update(['paid_amount' => $order->total]);
            } else {
                // Create payment if missing
                $payuMode = $params['mode'] ?? 'CC';
                $methodMap = ['CC' => 'card', 'DC' => 'card', 'NB' => 'netbanking', 'UPI' => 'upi', 'WALLET' => 'wallet', 'EMI' => 'emi', 'BNPL' => 'bnpl'];
                $payment = Payment::create([
                    'order_id'               => $order->id,
                    'transaction_id'         => $txnid,
                    'gateway'                => 'payu',
                    'gateway_transaction_id' => $mihpayid,
                    'method'                 => $methodMap[strtoupper($payuMode)] ?? 'card',
                    'amount'                 => $order->total,
                    'currency'               => 'INR',
                    'status'                 => 'captured',
                    'gateway_response'       => $params,
                    'ip_address'             => $request->ip(),
                    'authorized_at'          => now(),
                    'captured_at'            => now(),
                ]);
                $order->update(['payment_status' => 'paid', 'paid_amount' => $order->total]);
            }

            // Update metadata
            $metadata = $order->metadata ?? [];
            $metadata['payu_mihpayid'] = $mihpayid;
            $metadata['payu_mode'] = $params['mode'] ?? '';
            $order->update(['metadata' => $metadata]);

            return redirect()->route('checkout.success', $order);
        }

        // Payment failed
        if ($payment) {
            $payment->markAsFailed($params['error_Message'] ?? 'Payment failed', $params);
        }

        return redirect()->route('checkout.failed')
            ->with('error', $params['error_Message'] ?? 'Payment was not successful. Please try again.');
    }

    /**
     * PayU failure callback (furl)
     */
    public function failure(Request $request)
    {
        $config = $this->getConfig();
        $params = $request->all();

        Log::info('PayU Failure Callback', ['params' => $params]);

        // This route is CSRF-exempt and cancels an order + restores its stock,
        // so it must prove the POST came from PayU. Without the hash check any
        // visitor could cancel any order by guessing its (sequential) id.
        if (! $this->verifyHash($params, $config['salt'])) {
            Log::warning('PayU hash verification failed on failure callback', [
                'txnid' => $params['txnid'] ?? 'unknown',
                'ip'    => $request->ip(),
            ]);

            return redirect()->route('checkout.failed')
                ->with('error', 'Payment was not successful. Please try again.');
        }

        $orderId = $params['udf1'] ?? null;
        $txnid = $params['txnid'] ?? '';

        $order = Order::find($orderId);

        if ($order) {
            $payment = Payment::where('transaction_id', $txnid)
                ->where('order_id', $order->id)
                ->first();

            if ($payment) {
                $payment->markAsFailed($params['error_Message'] ?? 'Payment failed', $params);
            }

            // Restore stock since payment failed
            $this->restoreOrderStock($order);
        }

        $errorMsg = $params['error_Message'] ?? 'Payment was not successful.';

        return redirect()->route('checkout.failed')
            ->with('error', $errorMsg . ' No money was captured. Please place your order again.');
    }

    /**
     * Restore stock for a failed payment order
     */
    private function restoreOrderStock(Order $order): void
    {
        if ($order->status === 'cancelled') {
            return; // Already restored
        }

        $order->load('items');

        foreach ($order->items as $item) {
            if ($item->variant_id) {
                \App\Models\ProductVariant::where('id', $item->variant_id)
                    ->increment('stock_quantity', $item->quantity);
            } else {
                \App\Models\Product::where('id', $item->product_id)
                    ->increment('stock_quantity', $item->quantity);
            }

            \App\Models\Product::where('id', $item->product_id)
                ->decrement('sales_count', $item->quantity);
        }

        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }
}
