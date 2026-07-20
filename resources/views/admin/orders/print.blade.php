@php
    $store = [
        'name' => setting('general', 'app_name', config('app.name')),
        'address' => setting('store', 'address', ''),
        'phone' => setting('store', 'phone', ''),
        'email' => setting('store', 'support_email', ''),
        'footer' => trim(setting('store', 'invoice_footer', '') . ($order->channel === 'pos' && setting('pos', 'receipt_footer') ? "\n" . setting('pos', 'receipt_footer') : '')),
    ];
    $billing = $order->addresses->firstWhere('type', 'billing');
    $shipping = $order->addresses->firstWhere('type', 'shipping');
    $balance = (float) $order->grand_total - (float) $order->paid_total;
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
    $placed = $order->placed_at ?? $order->created_at;
    $discountLabel = $order->discount_type === 'percent'
        ? 'Discount (' . rtrim(rtrim(number_format((float) $order->discount_value, 2), '0'), '.') . '%)'
        : 'Discount';
    $deliveryLabel = delivery_method_label($order->shipping_method);
@endphp

<x-print.document :bill-type="$billType" :title="'Bill #' . $order->order_number" :back="route('admin.orders.show', $order)">
    @if ($billType === 'thermal')
        {{-- ===================== THERMAL RECEIPT ===================== --}}
        <div class="sheet">
            <div class="center">
                <div class="brand">{{ $store['name'] }}</div>
                @if ($store['address'])<div>{{ $store['address'] }}</div>@endif
                @if ($store['phone'])<div>Tel: {{ $store['phone'] }}</div>@endif
                <div class="doc-title">Sales Receipt</div>
            </div>
            <hr>
            <div class="row tight"><span>Order</span><span class="bold">#{{ $order->order_number }}</span></div>
            <div class="row tight"><span>Date</span><span>{{ format_datetime($placed) }}</span></div>
            <div class="row tight"><span>Customer</span><span>{{ $order->customer?->name ?? 'Walk-in' }}</span></div>
            <div class="row tight"><span>Payment</span><span style="text-transform:capitalize">{{ str_replace('_', ' ', $order->payment_status) }}</span></div>
            @if ($deliveryLabel)
                <div class="row tight"><span>Delivery</span><span>{{ $deliveryLabel }}</span></div>
                @if ($order->courier)<div class="row tight"><span>By</span><span>{{ $order->courier }}</span></div>@endif
                @if ($order->tracking_number)<div class="row tight"><span>Contact</span><span>{{ $order->tracking_number }}</span></div>@endif
            @endif
            <hr>
            @foreach ($order->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->name_snapshot }}</div>
                    <div class="row">
                        <span>{{ $qty($item->quantity) }} × {{ format_money($item->unit_price) }}</span>
                        <span>{{ format_money($item->line_total) }}</span>
                    </div>
                </div>
            @endforeach
            <hr>
            <div class="row"><span>Subtotal</span><span>{{ format_money($order->subtotal) }}</span></div>
            @if ((float) $order->discount_total > 0)<div class="row"><span>{{ $discountLabel }}</span><span>- {{ format_money($order->discount_total) }}</span></div>@endif
            @if ((float) $order->tax_total > 0)<div class="row"><span>Tax</span><span>{{ format_money($order->tax_total) }}</span></div>@endif
            @if ((float) $order->shipping_total > 0)<div class="row"><span>Shipping</span><span>{{ format_money($order->shipping_total) }}</span></div>@endif
            <hr>
            <div class="row grand"><span>TOTAL</span><span>{{ format_money($order->grand_total) }}</span></div>
            <div class="row"><span>Paid</span><span>{{ format_money($order->paid_total) }}</span></div>
            @if ($balance > 0)<div class="row"><span>Balance</span><span>{{ format_money($balance) }}</span></div>@endif
            <hr>
            <div class="foot">@if ($store['footer']){{ $store['footer'] }}@else Thank you for your purchase!@endif</div>
            <div class="barcode">*{{ $order->order_number }}*</div>
        </div>
    @else
        {{-- ===================== A4 INVOICE ===================== --}}
        @php
            $payStamp = match ($order->payment_status) {
                'paid' => ['paid', 'Paid'],
                'partial' => ['partial', 'Partial'],
                'refunded', 'partially_refunded' => ['refunded', 'Refunded'],
                default => ['unpaid', 'Unpaid'],
            };
        @endphp
        <div class="sheet">
            <div class="pay-stamp {{ $payStamp[0] }}">{{ $payStamp[1] }}</div>
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
                        @if ($deliveryLabel)<div><span class="muted">Delivery:</span> {{ $deliveryLabel }}@if ($order->courier) · {{ $order->courier }}@endif</div>@endif
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
                    <tr><td class="muted">{{ $discountLabel }}</td><td class="right">− {{ format_money($order->discount_total) }}</td></tr>
                @endif
                <tr><td class="muted">Tax</td><td class="right">{{ format_money($order->tax_total) }}</td></tr>
                <tr><td class="muted">Shipping</td><td class="right">{{ format_money($order->shipping_total) }}</td></tr>
                <tr class="grand"><td>Total</td><td class="right">{{ format_money($order->grand_total) }}</td></tr>
                <tr><td class="muted">Paid</td><td class="right">{{ format_money($order->paid_total) }}</td></tr>
                @if ($balance > 0)
                    <tr><td class="muted bold">Balance due</td><td class="right bold">{{ format_money($balance) }}</td></tr>
                @endif
            </table>

            <div class="footer">@if ($store['footer']){{ $store['footer'] }}@else Thank you for your business.@endif</div>
        </div>
    @endif
</x-print.document>
