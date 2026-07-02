@php
    $store = [
        'name' => setting('general', 'app_name', config('app.name')),
        'address' => setting('store', 'address', ''),
        'phone' => setting('store', 'phone', ''),
        'email' => setting('store', 'support_email', ''),
        'footer' => trim((string) setting('store', 'invoice_footer', '')),
    ];
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
    $balance = (float) $purchase->grand_total - (float) $purchase->paid_total;
    $supplier = $purchase->supplier;
    $discountLabel = $purchase->discount_type === 'percent'
        ? 'Discount (' . rtrim(rtrim(number_format((float) $purchase->discount_value, 2), '0'), '.') . '%)'
        : 'Discount';
    $deliveryLabel = delivery_method_label($purchase->delivery_method);
@endphp

<x-print.document :bill-type="$billType" :title="'Purchase ' . $purchase->purchase_number" :back="route('admin.purchases.show', $purchase)">
    @if ($billType === 'thermal')
        {{-- ===================== THERMAL PURCHASE ===================== --}}
        <div class="sheet">
            <div class="center">
                <div class="brand">{{ $store['name'] }}</div>
                @if ($store['phone'])<div>Tel: {{ $store['phone'] }}</div>@endif
                <div class="doc-title">Purchase Order</div>
            </div>
            <hr>
            <div class="row tight"><span>PO</span><span class="bold">{{ $purchase->purchase_number }}</span></div>
            <div class="row tight"><span>Date</span><span>{{ format_date($purchase->purchase_date) }}</span></div>
            @if ($purchase->reference)<div class="row tight"><span>Ref</span><span>{{ $purchase->reference }}</span></div>@endif
            <div class="row tight"><span>Supplier</span><span>{{ $supplier?->name ?? '—' }}</span></div>
            <div class="row tight"><span>Status</span><span style="text-transform:capitalize">{{ $purchase->status }}</span></div>
            @if ($deliveryLabel)
                <div class="row tight"><span>Delivery</span><span>{{ $deliveryLabel }}</span></div>
                @if ($purchase->delivery_agent)<div class="row tight"><span>By</span><span>{{ $purchase->delivery_agent }}</span></div>@endif
                @if ($purchase->delivery_contact)<div class="row tight"><span>Contact</span><span>{{ $purchase->delivery_contact }}</span></div>@endif
            @endif
            <hr>
            @foreach ($purchase->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->variant?->product?->name ?? 'Item' }}</div>
                    <div class="row">
                        <span>{{ $qty($item->quantity) }} × {{ format_money($item->unit_cost) }}</span>
                        <span>{{ format_money($item->line_total) }}</span>
                    </div>
                </div>
            @endforeach
            <hr>
            <div class="row"><span>Subtotal</span><span>{{ format_money($purchase->subtotal) }}</span></div>
            @if ((float) $purchase->discount_total > 0)<div class="row"><span>{{ $discountLabel }}</span><span>- {{ format_money($purchase->discount_total) }}</span></div>@endif
            @if ((float) $purchase->tax_total > 0)<div class="row"><span>Tax</span><span>{{ format_money($purchase->tax_total) }}</span></div>@endif
            @if ((float) $purchase->delivery_charge > 0)<div class="row"><span>Delivery</span><span>{{ format_money($purchase->delivery_charge) }}</span></div>@endif
            <hr>
            <div class="row grand"><span>TOTAL</span><span>{{ format_money($purchase->grand_total) }}</span></div>
            <div class="row"><span>Paid</span><span>{{ format_money($purchase->paid_total) }}</span></div>
            @if ($balance > 0)<div class="row"><span>Payable</span><span>{{ format_money($balance) }}</span></div>@endif
            @if ($store['footer'])<hr><div class="foot">{{ $store['footer'] }}</div>@endif
        </div>
    @else
        {{-- ===================== A4 PURCHASE ORDER ===================== --}}
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
                    <div class="doc-title">Purchase Order</div>
                    <div style="margin-top:8px;font-size:13px">
                        <div><span class="muted">No.</span> <span class="bold">{{ $purchase->purchase_number }}</span></div>
                        <div><span class="muted">Date:</span> {{ format_date($purchase->purchase_date) }}</div>
                        @if ($purchase->reference)<div><span class="muted">Ref:</span> {{ $purchase->reference }}</div>@endif
                        <div><span class="muted">Status:</span> <span style="text-transform:capitalize">{{ $purchase->status }}</span></div>
                        @if ($deliveryLabel)<div><span class="muted">Delivery:</span> {{ $deliveryLabel }}@if ($purchase->delivery_agent) · {{ $purchase->delivery_agent }}@endif</div>@endif
                    </div>
                </div>
            </div>

            <div class="parties">
                <div>
                    <div class="label">Supplier</div>
                    <div class="bold">{{ $supplier?->name ?? '—' }}</div>
                    @if ($supplier)
                        <div style="font-size:12px;line-height:1.5;margin-top:2px" class="muted">
                            @if ($supplier->company){{ $supplier->company }}<br>@endif
                            @if ($supplier->phone)Tel: {{ $supplier->phone }}<br>@endif
                            @if ($supplier->email){{ $supplier->email }}<br>@endif
                            @if ($supplier->address){{ $supplier->address }}@endif
                        </div>
                    @endif
                </div>
                <div class="right">
                    <div class="label">Deliver to</div>
                    <div class="bold">{{ $store['name'] }}</div>
                    @if ($store['address'])<div style="font-size:12px;line-height:1.5;margin-top:2px" class="muted">{{ $store['address'] }}</div>@endif
                </div>
            </div>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width:50%">Item</th>
                        <th class="right">Qty</th>
                        <th class="right">Unit cost</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td>
                                <div class="bold">{{ $item->variant?->product?->name ?? 'Item' }}</div>
                                <div class="muted" style="font-size:11px">{{ $item->variant?->sku }}</div>
                            </td>
                            <td class="right">{{ $qty($item->quantity) }}</td>
                            <td class="right">{{ format_money($item->unit_cost) }}</td>
                            <td class="right bold">{{ format_money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr><td class="muted">Subtotal</td><td class="right">{{ format_money($purchase->subtotal) }}</td></tr>
                @if ((float) $purchase->discount_total > 0)
                    <tr><td class="muted">{{ $discountLabel }}</td><td class="right">− {{ format_money($purchase->discount_total) }}</td></tr>
                @endif
                @if ((float) $purchase->tax_total > 0)
                    <tr><td class="muted">Tax</td><td class="right">{{ format_money($purchase->tax_total) }}</td></tr>
                @endif
                @if ((float) $purchase->delivery_charge > 0)
                    <tr><td class="muted">Delivery</td><td class="right">{{ format_money($purchase->delivery_charge) }}</td></tr>
                @endif
                <tr class="grand"><td>Total</td><td class="right">{{ format_money($purchase->grand_total) }}</td></tr>
                <tr><td class="muted">Paid</td><td class="right">{{ format_money($purchase->paid_total) }}</td></tr>
                @if ($balance > 0)
                    <tr><td class="muted bold">Payable</td><td class="right bold">{{ format_money($balance) }}</td></tr>
                @endif
            </table>

            @if ($store['footer'])<div class="footer">{{ $store['footer'] }}</div>@endif
        </div>
    @endif
</x-print.document>
