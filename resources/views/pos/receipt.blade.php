<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt - {{ $sale->sale_number }}</title>
    <style>
        @page { margin: 5mm; size: 80mm auto; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            max-width: 300px;
            margin: 0 auto;
            padding: 10px;
            color: #000;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .item-row { margin: 3px 0; }
        .item-name { font-weight: bold; word-break: break-word; }
        .item-detail { font-size: 11px; color: #1a1a1a; }
        .total-row { font-size: 14px; font-weight: bold; }
        .footer { font-size: 10px; color: #333; margin-top: 10px; text-align: center; }
        .no-print button { min-height: 44px; min-width: 44px; }
        .no-print button:focus-visible { outline: 2px solid #6F9CA2; outline-offset: 2px; }
        @media print {
            body { max-width: 100%; padding: 0; }
            .no-print { display: none; }
        }
        @media screen and (max-width: 320px) {
            body { padding: 6px; font-size: 11px; }
            .total-row { font-size: 13px; }
        }
    </style>
</head>
<body role="main" aria-label="Receipt {{ $sale->sale_number }}">
    {{-- Store Header --}}
    <div class="center">
        <div class="bold" style="font-size: 16px;">{{ $sale->store->name ?? config('app.name') }}</div>
        @if($sale->store?->address)
        <div style="font-size: 10px; margin-top: 2px;">{{ $sale->store->address }}</div>
        @endif
        @if($sale->store?->gst_number)
        <div style="font-size: 10px;">GSTIN: {{ $sale->store->gst_number }}</div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Sale Info --}}
    <div class="row"><span>Bill #</span><span class="bold">{{ $sale->sale_number }}</span></div>
    <div class="row"><span>Date</span><span>{{ $sale->created_at->format('d/m/Y g:i A') }}</span></div>
    <div class="row"><span>Cashier</span><span>{{ $sale->staff?->user?->first_name ?? 'Staff' }}</span></div>
    @if($sale->customer)
    <div class="row"><span>Customer</span><span>{{ trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? '')) }}</span></div>
    @endif

    <div class="divider"></div>

    {{-- Items --}}
    @foreach($sale->items as $item)
    <div class="item-row">
        <div class="item-name">{{ $item->product_name }}</div>
        <div class="row item-detail">
            <span>{{ $item->quantity }} × ₹{{ number_format($item->price, 2) }}</span>
            <span>₹{{ number_format($item->total, 2) }}</span>
        </div>
        @if($item->hsn_code)
        <div class="item-detail">HSN: {{ $item->hsn_code }} | GST: {{ $item->tax_rate }}%</div>
        @endif
    </div>
    @endforeach

    <div class="divider"></div>

    {{-- Totals --}}
    <div class="row"><span>Subtotal</span><span>₹{{ number_format($sale->subtotal, 2) }}</span></div>
    @if($sale->discount > 0)
    <div class="row"><span>Discount</span><span>-₹{{ number_format($sale->discount, 2) }}</span></div>
    @endif
    @if($sale->tax > 0)
    <div class="row"><span>GST</span><span>₹{{ number_format($sale->tax, 2) }}</span></div>
    @endif

    <div class="divider"></div>
    <div class="row total-row"><span>TOTAL</span><span>₹{{ number_format($sale->total, 2) }}</span></div>
    <div class="divider"></div>

    {{-- Payment --}}
    <div class="row"><span>Payment</span><span>{{ strtoupper($sale->payment_method) }}</span></div>
    <div class="row"><span>Paid</span><span>₹{{ number_format($sale->paid_amount, 2) }}</span></div>
    @if($sale->change_amount > 0)
    <div class="row bold"><span>Change</span><span>₹{{ number_format($sale->change_amount, 2) }}</span></div>
    @endif

    {{-- Payment reference (card/UPI) --}}
    @php $paymentDetails = $sale->payment_details ?? []; @endphp
    @if(!empty($paymentDetails['reference']))
    <div class="row" style="font-size: 10px;"><span>Ref #</span><span>{{ $paymentDetails['reference'] }}</span></div>
    @endif

    {{-- GST Summary --}}
    @php
        $gstItems = $sale->items->where('tax_rate', '>', 0);
    @endphp
    @if($gstItems->count() > 0)
    <div class="divider"></div>
    <div class="center bold" style="font-size: 10px; margin-bottom: 3px;">GST BREAKUP</div>
    @foreach($gstItems->groupBy('tax_rate') as $rate => $items)
    @php $igstTotal = $items->sum('igst'); @endphp
    <div class="row" style="font-size: 10px;">
        <span>GST @ {{ $rate }}%</span>
        @if($igstTotal > 0)
        <span>IGST ₹{{ number_format($igstTotal, 2) }}</span>
        @else
        <span>CGST ₹{{ number_format($items->sum('cgst'), 2) }} + SGST ₹{{ number_format($items->sum('sgst'), 2) }}</span>
        @endif
    </div>
    @endforeach
    @endif

    <div class="divider"></div>

    {{-- Footer (configurable via settings) --}}
    @php
        $receiptFooter = \App\Models\Setting::get('pos_receipt_footer', 'Thank you for shopping with us!');
        $returnPolicy  = \App\Models\Setting::get('pos_return_policy', 'Exchange/Return within 7 days with receipt.');
    @endphp
    <div class="footer">
        <p>{{ $receiptFooter }}</p>
        <p>{{ $returnPolicy }}</p>
    </div>

    {{-- Print Button (screen only) --}}
    <div class="center no-print" style="margin-top: 20px;">
        <button onclick="window.print()" aria-label="Print receipt" style="padding: 8px 24px; font-size: 14px; cursor: pointer; background: #6F9CA2; color: white; border: none; border-radius: 6px;">
            Print Receipt
        </button>
        <button onclick="window.close()" aria-label="Close receipt window" style="padding: 8px 24px; font-size: 14px; cursor: pointer; background: #E2E8F0; color: #1a1a1a; border: none; border-radius: 6px; margin-left: 8px;">
            Close
        </button>
    </div>
</body>
</html>
