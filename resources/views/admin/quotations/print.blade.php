@php
    $store = [
        'name' => setting('general', 'app_name', config('app.name')),
        'address' => setting('store', 'address', ''),
        'phone' => setting('store', 'phone', ''),
        'email' => setting('store', 'support_email', ''),
        'footer' => trim((string) setting('store', 'invoice_footer', '')),
    ];
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.');
    $created = $quotation->created_at;
    $notes = $quotation->notes ?: $store['footer'];
    $discountLabel = $quotation->discount_type === 'percent'
        ? 'Discount (' . rtrim(rtrim(number_format((float) $quotation->discount_value, 2), '0'), '.') . '%)'
        : 'Discount';
@endphp

<x-print.document :bill-type="$billType" :title="'Quotation ' . $quotation->quotation_number" :back="route('admin.quotations.show', $quotation)">
    @if ($billType === 'thermal')
        {{-- ===================== THERMAL QUOTE ===================== --}}
        <div class="sheet">
            <div class="center">
                <div class="brand">{{ $store['name'] }}</div>
                @if ($store['address'])<div>{{ $store['address'] }}</div>@endif
                @if ($store['phone'])<div>Tel: {{ $store['phone'] }}</div>@endif
                <div class="doc-title">Quotation</div>
            </div>
            <hr>
            <div class="row tight"><span>Quote</span><span class="bold">{{ $quotation->quotation_number }}</span></div>
            <div class="row tight"><span>Date</span><span>{{ format_date($created) }}</span></div>
            @if ($quotation->valid_until)<div class="row tight"><span>Valid until</span><span>{{ format_date($quotation->valid_until) }}</span></div>@endif
            <div class="row tight"><span>Customer</span><span>{{ $quotation->customer?->name ?? 'Prospect' }}</span></div>
            <div class="row tight"><span>Tier</span><span style="text-transform:capitalize">{{ $quotation->price_tier }}</span></div>
            <hr>
            @foreach ($quotation->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->name_snapshot }}</div>
                    <div class="row">
                        <span>{{ $qty($item->quantity) }} × {{ format_money($item->unit_price) }}</span>
                        <span>{{ format_money($item->line_total) }}</span>
                    </div>
                </div>
            @endforeach
            <hr>
            <div class="row"><span>Subtotal</span><span>{{ format_money($quotation->subtotal) }}</span></div>
            @if ((float) $quotation->discount_total > 0)<div class="row"><span>{{ $discountLabel }}</span><span>- {{ format_money($quotation->discount_total) }}</span></div>@endif
            @if ((float) $quotation->tax_total > 0)<div class="row"><span>Tax</span><span>{{ format_money($quotation->tax_total) }}</span></div>@endif
            <hr>
            <div class="row grand"><span>TOTAL</span><span>{{ format_money($quotation->grand_total) }}</span></div>
            @if ($notes)<hr><div class="foot">{{ $notes }}</div>@endif
        </div>
    @else
        {{-- ===================== A4 QUOTATION ===================== --}}
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
                    <div class="doc-title">Quotation</div>
                    <div style="margin-top:8px;font-size:13px">
                        <div><span class="muted">No.</span> <span class="bold">{{ $quotation->quotation_number }}</span></div>
                        <div><span class="muted">Date:</span> {{ format_date($created) }}</div>
                        @if ($quotation->valid_until)<div><span class="muted">Valid until:</span> {{ format_date($quotation->valid_until) }}</div>@endif
                        <div><span class="muted">Status:</span> <span style="text-transform:capitalize">{{ $quotation->status }}</span></div>
                    </div>
                </div>
            </div>

            <div class="parties">
                <div>
                    <div class="label">Prepared for</div>
                    <div class="bold">{{ $quotation->customer?->name ?? 'Prospective customer' }}</div>
                    @if ($quotation->customer)
                        <div style="font-size:12px;line-height:1.5;margin-top:2px" class="muted">
                            @if ($quotation->customer->phone){{ $quotation->customer->phone }}<br>@endif
                            @if ($quotation->customer->email){{ $quotation->customer->email }}<br>@endif
                            @if ($quotation->customer->address){{ $quotation->customer->address }}@endif
                        </div>
                    @endif
                </div>
                <div class="right">
                    <div class="label">Price tier</div>
                    <div class="bold" style="text-transform:capitalize">{{ $quotation->price_tier }}</div>
                </div>
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
                    @foreach ($quotation->items as $item)
                        <tr>
                            <td>
                                <div class="bold">{{ $item->name_snapshot }}</div>
                                @if ($item->description)<div class="muted" style="font-size:11px">{{ $item->description }}</div>@endif
                            </td>
                            <td class="right">{{ $qty($item->quantity) }}</td>
                            <td class="right">{{ format_money($item->unit_price) }}</td>
                            <td class="right bold">{{ format_money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr><td class="muted">Subtotal</td><td class="right">{{ format_money($quotation->subtotal) }}</td></tr>
                @if ((float) $quotation->discount_total > 0)
                    <tr><td class="muted">{{ $discountLabel }}</td><td class="right">− {{ format_money($quotation->discount_total) }}</td></tr>
                @endif
                @if ((float) $quotation->tax_total > 0)
                    <tr><td class="muted">Tax</td><td class="right">{{ format_money($quotation->tax_total) }}</td></tr>
                @endif
                <tr class="grand"><td>Total</td><td class="right">{{ format_money($quotation->grand_total) }}</td></tr>
            </table>

            @if ($notes)<div class="footer">{{ $notes }}</div>@endif
        </div>
    @endif
</x-print.document>
