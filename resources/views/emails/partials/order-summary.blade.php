{{-- Order line items + totals table. Param: $order (with items loaded). --}}
@php
    $muted = '#77746a';
    $ink = '#1c1b16';
    $border = '#e7e4d6';
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:8px 0 4px 0;">
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:12px 0; border-bottom:1px solid {{ $border }}; vertical-align:top;">
                <div style="color:{{ $ink }}; font-size:14px; font-weight:600;">{{ $item->name_snapshot }}</div>
                @if (! empty($item->attributes_snapshot))
                    <div style="color:{{ $muted }}; font-size:12px; margin-top:2px;">
                        {{ collect($item->attributes_snapshot)->map(fn ($v, $k) => is_string($k) ? "$k: $v" : $v)->implode(' · ') }}
                    </div>
                @endif
                <div style="color:{{ $muted }}; font-size:12px; margin-top:2px;">
                    {{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }} × {{ format_money($item->unit_price) }}
                </div>
            </td>
            <td style="padding:12px 0; border-bottom:1px solid {{ $border }}; vertical-align:top; text-align:right; color:{{ $ink }}; font-size:14px; font-weight:600; white-space:nowrap;">
                {{ format_money($item->line_total) }}
            </td>
        </tr>
    @endforeach

    @php
        $rows = [['Subtotal', $order->subtotal]];
        if ((float) $order->discount_total > 0) { $rows[] = ['Discount', '−' . format_money($order->discount_total)]; }
        if ((float) $order->tax_total > 0) { $rows[] = ['Tax', format_money($order->tax_total)]; }
        $rows[] = ['Shipping', (float) $order->shipping_total > 0 ? format_money($order->shipping_total) : 'Free'];
    @endphp
    @foreach ($rows as [$label, $value])
        <tr>
            <td style="padding:6px 0 0 0; color:{{ $muted }}; font-size:13px;">{{ $label }}</td>
            <td style="padding:6px 0 0 0; text-align:right; color:{{ $ink }}; font-size:13px; white-space:nowrap;">{{ is_string($value) ? $value : format_money($value) }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="padding:12px 0 0 0; color:{{ $ink }}; font-size:16px; font-weight:800;">Total</td>
        <td style="padding:12px 0 0 0; text-align:right; color:{{ $ink }}; font-size:16px; font-weight:800; white-space:nowrap;">{{ format_money($order->grand_total) }}</td>
    </tr>
</table>
