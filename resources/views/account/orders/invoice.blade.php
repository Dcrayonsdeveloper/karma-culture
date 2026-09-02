<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; font-size: 14px; color: #1a1a1a; line-height: 1.6; background: #f3f4f6; }
        .invoice { max-width: 820px; margin: 0 auto; padding: 40px; background: #fff; min-height: 100vh; }

        /* Toolbar */
        .toolbar { max-width: 820px; margin: 0 auto; padding: 14px 40px; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .toolbar-left { font-size: 13px; color: #6b7280; }
        .toolbar-left strong { color: #111827; }
        .toolbar-actions { display: flex; gap: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.15s; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 28px; border-bottom: 1px solid #e5e7eb; margin-bottom: 28px; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .invoice-title { font-size: 11px; text-transform: uppercase; letter-spacing: 3px; color: #9ca3af; font-weight: 700; margin-bottom: 4px; }
        .invoice-number { font-size: 22px; font-weight: 800; color: #111827; letter-spacing: -0.5px; }
        .header-right { text-align: right; }
        .header-meta { font-size: 13px; color: #6b7280; line-height: 1.8; }
        .header-meta strong { color: #374151; }

        /* Status badges */
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
        .badge-success .badge-dot { background: #059669; }
        .badge-warning .badge-dot { background: #d97706; }
        .badge-error .badge-dot { background: #dc2626; }

        /* Addresses */
        .addresses { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .address-block { padding: 20px; background: #f9fafb; border-radius: 10px; border: 1px solid #f3f4f6; }
        .address-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #9ca3af; font-weight: 700; margin-bottom: 10px; }
        .address-name { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .address-line { font-size: 13px; color: #6b7280; line-height: 1.7; }

        /* Items Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { padding: 10px 14px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; font-weight: 700; border-bottom: 2px solid #e5e7eb; }
        thead th.text-right { text-align: right; }
        thead th.text-center { text-align: center; }
        tbody td { padding: 14px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #374151; }
        tbody td.text-right { text-align: right; }
        tbody td.text-center { text-align: center; }
        tbody tr:last-child td { border-bottom: none; }
        .product-name { font-weight: 600; color: #111827; font-size: 14px; }
        .product-variant { font-size: 12px; color: #9ca3af; margin-top: 2px; }

        /* Totals */
        .totals-wrapper { display: flex; justify-content: flex-end; }
        .totals { width: 320px; }
        .totals-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 13px; }
        .totals-label { color: #6b7280; }
        .totals-value { font-weight: 600; color: #374151; }
        .totals-row.discount .totals-value { color: #059669; }
        .totals-divider { border-top: 2px solid #111827; margin-top: 8px; padding-top: 12px; }
        .totals-row.grand { font-size: 18px; }
        .totals-row.grand .totals-label { font-weight: 700; color: #111827; }
        .totals-row.grand .totals-value { font-weight: 800; color: #111827; }

        /* Footer */
        .footer { margin-top: 40px; padding-top: 24px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer-thanks { font-size: 15px; color: #374151; font-weight: 600; margin-bottom: 4px; }
        .footer-url { font-size: 12px; color: #9ca3af; }

        /* Phones: the 820px sheet is wider than the screen, so the 40px
           paddings, the two-column addresses, the 320px totals box and the
           five-column table all ran off the right edge. Screen only - the
           printed page keeps its layout. */
        .invoice-number, .address-name, .address-line { overflow-wrap: anywhere; }
        .table-wrap { max-width: 100%; overflow-x: auto; }
        @media screen and (max-width: 639.98px) {
            .toolbar { padding: 12px 16px; flex-wrap: wrap; gap: 8px; }
            .btn { min-height: 40px; }
            .invoice { padding: 24px 16px; }
            .header { flex-wrap: wrap; gap: 16px; }
            .header-left { flex-wrap: wrap; gap: 12px; }
            .header-right { text-align: left; }
            .addresses { grid-template-columns: 1fr; gap: 12px; }
            .address-block { padding: 16px; }
            table { min-width: 520px; }
            .totals { width: 100%; }
        }

        @media print {
            body { background: #fff; }
            .invoice { padding: 20px; min-height: auto; }
            .toolbar { display: none !important; }
            .address-block { border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="toolbar-left">
            Invoice for <strong>{{ $order->order_number }}</strong>
        </div>
        <div class="toolbar-actions">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print
            </button>
        </div>
    </div>

    <div class="invoice">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <img src="/images/colorlogo.png" alt="{{ config('app.name') }}" height="55" style="flex-shrink: 0;">
                <div>
                    <div class="invoice-title">Invoice</div>
                    <div class="invoice-number">{{ $order->order_number }}</div>
                </div>
            </div>
            <div class="header-right">
                <div class="header-meta">
                    <strong>{{ $order->created_at->format('F d, Y') }}</strong><br>
                    @php
                        $badgeClass = match($order->payment_status) {
                            'paid' => 'badge-success',
                            'pending' => 'badge-warning',
                            default => 'badge-error',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}" style="margin-top: 6px;">
                        <span class="badge-dot"></span>
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Addresses --}}
        @php
            $billing = $order->billing_address_snapshot;
            $shipping = $order->shipping_address_snapshot;
        @endphp
        <div class="addresses">
            <div class="address-block">
                <div class="address-label">Bill To</div>
                @if($billing)
                    <div class="address-name">{{ $billing['name'] ?? ($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '') }}</div>
                    <div class="address-line">
                        @if(!empty($billing['phone'])){{ $billing['phone'] }}<br>@endif
                        @if(!empty($billing['address_line_1'])){{ $billing['address_line_1'] }}@endif
                        @if(!empty($billing['address_line_2'])), {{ $billing['address_line_2'] }}@endif
                        <br>
                        {{ $billing['city'] ?? '' }}{{ !empty($billing['state']) ? ', ' . $billing['state'] : '' }} {{ $billing['postal_code'] ?? '' }}
                        @if(!empty($billing['country']))<br>{{ $billing['country'] }}@endif
                    </div>
                @elseif($order->user)
                    <div class="address-name">{{ $order->user->full_name }}</div>
                    <div class="address-line">{{ $order->user->email }}</div>
                @else
                    <div class="address-line">N/A</div>
                @endif
            </div>
            <div class="address-block">
                <div class="address-label">Ship To</div>
                @if($shipping)
                    <div class="address-name">{{ $shipping['name'] ?? ($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '') }}</div>
                    <div class="address-line">
                        @if(!empty($shipping['phone'])){{ $shipping['phone'] }}<br>@endif
                        @if(!empty($shipping['address_line_1'])){{ $shipping['address_line_1'] }}@endif
                        @if(!empty($shipping['address_line_2'])), {{ $shipping['address_line_2'] }}@endif
                        <br>
                        {{ $shipping['city'] ?? '' }}{{ !empty($shipping['state']) ? ', ' . $shipping['state'] : '' }} {{ $shipping['postal_code'] ?? '' }}
                        @if(!empty($shipping['country']))<br>{{ $shipping['country'] }}@endif
                    </div>
                @else
                    <div class="address-line">N/A</div>
                @endif
            </div>
        </div>

        {{-- Items Table --}}
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 44%;">Item</th>
                    <th>SKU</th>
                    <th class="text-right">Price</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div class="product-name">{{ $item->product_name }}</div>
                            @if($item->variant_name)
                                <div class="product-variant">{{ $item->variant_name }}</div>
                            @endif
                        </td>
                        <td style="font-size: 11px; color: #9ca3af; font-family: monospace;">{{ $item->sku }}</td>
                        <td class="text-right">{{ format_price($item->price) }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right" style="font-weight: 600;">{{ format_price($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        {{-- Totals --}}
        <div class="totals-wrapper">
            <div class="totals">
                <div class="totals-row">
                    <span class="totals-label">Subtotal</span>
                    <span class="totals-value">{{ format_price($order->subtotal) }}</span>
                </div>
                @if($order->discount > 0)
                    <div class="totals-row discount">
                        <span class="totals-label">Discount</span>
                        <span class="totals-value">-{{ format_price($order->discount) }}</span>
                    </div>
                @endif
                <div class="totals-row">
                    <span class="totals-label">Shipping</span>
                    <span class="totals-value">{{ format_price($order->shipping_cost) }}</span>
                </div>
                @if($order->tax > 0)
                    <div class="totals-row">
                        <span class="totals-label">Tax</span>
                        <span class="totals-value">{{ format_price($order->tax) }}</span>
                    </div>
                @endif
                <div class="totals-row grand totals-divider">
                    <span class="totals-label">Total</span>
                    <span class="totals-value">{{ format_price($order->total) }}</span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-thanks">Thank you for shopping with {{ config('app.name') }}!</div>
            <div class="footer-url">{{ config('app.url') }}</div>
        </div>
    </div>
</body>
</html>
