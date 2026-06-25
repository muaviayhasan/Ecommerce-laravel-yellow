@php
    $store = [
        'name' => setting('general', 'app_name', config('app.name')),
        'address' => setting('store', 'address', ''),
        'phone' => setting('store', 'phone', ''),
        'email' => setting('store', 'support_email', ''),
        'footer' => setting('store', 'invoice_footer', ''),
    ];
    $billing = $order->addresses->firstWhere('type', 'billing');
    $shipping = $order->addresses->firstWhere('type', 'shipping');
    $balance = (float) $order->grand_total - (float) $order->paid_total;
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
    $placed = $order->placed_at ?? $order->created_at;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #{{ $order->order_number }}</title>
    <style>
        @if ($billType === 'thermal')
            @page { size: 80mm auto; margin: 3mm; }
        @else
            @page { size: A4; margin: 14mm; }
        @endif

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #f3f4f6; color: #111827; }
        body { font-family: ui-sans-serif, system-ui, 'Segoe UI', Roboto, Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        a { color: inherit; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: 700; }

        /* Screen toolbar (never printed) */
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: center; padding: 12px; background: #1f2937; }
        .toolbar button, .toolbar a { font: inherit; font-size: 14px; font-weight: 600; padding: 8px 18px; border-radius: 8px; border: 0; cursor: pointer; text-decoration: none; }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-close { background: #374151; color: #e5e7eb; }
        @media print { .toolbar { display: none !important; } html, body { background: #fff; } }

        /* ===== A4 invoice ===== */
        .a4 .sheet { max-width: 760px; margin: 16px auto; background: #fff; padding: 40px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .a4 .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 20px; border-bottom: 2px solid #111827; }
        .a4 .brand { font-size: 24px; font-weight: 800; letter-spacing: -.01em; }
        .a4 .doc-title { font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .a4 .parties { display: flex; justify-content: space-between; gap: 24px; margin: 24px 0; }
        .a4 .label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: #6b7280; margin-bottom: 4px; }
        .a4 table.items th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding: 8px 6px; }
        .a4 table.items td { padding: 10px 6px; border-bottom: 1px solid #f3f4f6; font-size: 13px; vertical-align: top; }
        .a4 .totals { margin-left: auto; width: 280px; margin-top: 18px; }
        .a4 .totals td { padding: 5px 0; font-size: 13px; }
        .a4 .totals .grand td { border-top: 2px solid #111827; font-size: 15px; font-weight: 800; padding-top: 10px; }
        .a4 .footer { margin-top: 36px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }

        /* ===== Thermal 80mm receipt ===== */
        .thermal .sheet { width: 74mm; margin: 12px auto; background: #fff; padding: 6mm 4mm; font-family: 'Courier New', ui-monospace, monospace; font-size: 11px; line-height: 1.45; }
        .thermal .brand { font-size: 15px; font-weight: 700; }
        .thermal hr { border: 0; border-top: 1px dashed #9ca3af; margin: 8px 0; }
        .thermal .row { display: flex; justify-content: space-between; gap: 8px; }
        .thermal .item-name { margin-top: 4px; }
        .thermal .grand { font-weight: 700; font-size: 12px; }
    </style>
</head>
<body class="{{ $billType }}">
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">Print</button>
        <a class="btn-close" href="{{ route('admin.orders.show', $order) }}">Close</a>
    </div>

    @if ($billType === 'thermal')
        {{-- ===================== THERMAL RECEIPT ===================== --}}
        <div class="sheet">
            <div class="center">
                <div class="brand">{{ $store['name'] }}</div>
                @if ($store['address'])<div>{{ $store['address'] }}</div>@endif
                @if ($store['phone'])<div>Tel: {{ $store['phone'] }}</div>@endif
            </div>
            <hr>
            <div class="row"><span>Order</span><span class="bold">#{{ $order->order_number }}</span></div>
            <div class="row"><span>Date</span><span>{{ format_datetime($placed) }}</span></div>
            <div class="row"><span>Customer</span><span>{{ $order->customer?->name ?? 'Walk-in' }}</span></div>
            <div class="row"><span>Payment</span><span style="text-transform:capitalize">{{ str_replace('_', ' ', $order->payment_status) }}</span></div>
            <hr>
            @foreach ($order->items as $item)
                <div class="item-name">{{ $item->name_snapshot }}</div>
                <div class="row">
                    <span>{{ $qty($item->quantity) }} × {{ format_money($item->unit_price) }}</span>
                    <span>{{ format_money($item->line_total) }}</span>
                </div>
            @endforeach
            <hr>
            <div class="row"><span>Subtotal</span><span>{{ format_money($order->subtotal) }}</span></div>
            @if ((float) $order->discount_total > 0)
                <div class="row"><span>Discount</span><span>- {{ format_money($order->discount_total) }}</span></div>
            @endif
            <div class="row"><span>Tax</span><span>{{ format_money($order->tax_total) }}</span></div>
            <div class="row"><span>Shipping</span><span>{{ format_money($order->shipping_total) }}</span></div>
            <hr>
            <div class="row grand"><span>TOTAL</span><span>{{ format_money($order->grand_total) }}</span></div>
            <div class="row"><span>Paid</span><span>{{ format_money($order->paid_total) }}</span></div>
            @if ($balance > 0)
                <div class="row"><span>Balance</span><span>{{ format_money($balance) }}</span></div>
            @endif
            <hr>
            <div class="center muted">
                @if ($store['footer']){{ $store['footer'] }}@else Thank you for your purchase! @endif
            </div>
        </div>
    @else
        {{-- ===================== A4 INVOICE ===================== --}}
        <div class="sheet">
            <div class="head">
                <div>
                    <div class="brand">{{ $store['name'] }}</div>
                    <div class="muted" style="font-size:12px;margin-top:4px;line-height:1.5">
                        @if ($store['address'])<div>{{ $store['address'] }}</div>@endif
                        @if ($store['phone'])<div>Tel: {{ $store['phone'] }}</div>@endif
                        @if ($store['email'])<div>{{ $store['email'] }}</div>@endif
                    </div>
                </div>
                <div class="right">
                    <div class="doc-title">Invoice</div>
                    <div style="margin-top:8px;font-size:13px">
                        <div><span class="muted">Order #</span> <span class="bold">{{ $order->order_number }}</span></div>
                        <div><span class="muted">Date:</span> {{ format_date($placed) }}</div>
                        <div><span class="muted">Status:</span> <span style="text-transform:capitalize">{{ $order->status }}</span></div>
                        <div><span class="muted">Payment:</span> <span style="text-transform:capitalize">{{ str_replace('_', ' ', $order->payment_status) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="parties">
                <div>
                    <div class="label">Bill to</div>
                    <div class="bold">{{ ($billing?->name) ?? $order->customer?->name ?? 'Walk-in customer' }}</div>
                    @if ($billing)
                        <div style="font-size:12px;line-height:1.5;margin-top:2px">
                            {{ $billing->line1 }}@if ($billing->line2)<br>{{ $billing->line2 }}@endif<br>
                            {{ collect([$billing->city, $billing->state, $billing->zip])->filter()->implode(', ') }}<br>
                            {{ $billing->country }}@if ($billing->phone)<br>{{ $billing->phone }}@endif
                        </div>
                    @elseif ($order->customer?->email)
                        <div style="font-size:12px" class="muted">{{ $order->customer->email }}</div>
                    @endif
                </div>
                @if ($shipping)
                    <div>
                        <div class="label">Ship to</div>
                        <div class="bold">{{ $shipping->name }}</div>
                        <div style="font-size:12px;line-height:1.5;margin-top:2px">
                            {{ $shipping->line1 }}@if ($shipping->line2)<br>{{ $shipping->line2 }}@endif<br>
                            {{ collect([$shipping->city, $shipping->state, $shipping->zip])->filter()->implode(', ') }}<br>
                            {{ $shipping->country }}
                        </div>
                    </div>
                @endif
            </div>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width:50%">Item</th>
                        <th class="right">Qty</th>
                        <th class="right">Unit price</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                <div class="bold">{{ $item->name_snapshot }}</div>
                                <div class="muted" style="font-size:11px">
                                    {{ $item->sku_snapshot }}@if (! empty($item->attributes_snapshot)) · {{ collect($item->attributes_snapshot)->implode(', ') }}@endif
                                </div>
                            </td>
                            <td class="right">{{ $qty($item->quantity) }}</td>
                            <td class="right">{{ format_money($item->unit_price) }}</td>
                            <td class="right bold">{{ format_money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr><td class="muted">Subtotal</td><td class="right">{{ format_money($order->subtotal) }}</td></tr>
                @if ((float) $order->discount_total > 0)
                    <tr><td class="muted">Discount</td><td class="right">− {{ format_money($order->discount_total) }}</td></tr>
                @endif
                <tr><td class="muted">Tax</td><td class="right">{{ format_money($order->tax_total) }}</td></tr>
                <tr><td class="muted">Shipping</td><td class="right">{{ format_money($order->shipping_total) }}</td></tr>
                <tr class="grand"><td>Total</td><td class="right">{{ format_money($order->grand_total) }}</td></tr>
                <tr><td class="muted">Paid</td><td class="right">{{ format_money($order->paid_total) }}</td></tr>
                @if ($balance > 0)
                    <tr><td class="muted bold">Balance due</td><td class="right bold">{{ format_money($balance) }}</td></tr>
                @endif
            </table>

            <div class="footer">
                @if ($store['footer']){{ $store['footer'] }}@else Thank you for your business.@endif
            </div>
        </div>
    @endif

    <script>
        // Auto-open the print dialog once the page has rendered.
        window.addEventListener('load', () => setTimeout(() => window.print(), 350));
    </script>
</body>
</html>
