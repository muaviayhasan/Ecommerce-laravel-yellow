@extends('emails.layout')

@section('preheader', 'Your quotation ' . $quotation->quotation_number . ' is ready.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">Your quotation is ready</h1>
    <p style="margin:0 0 16px 0;">
        Hi {{ $quotation->customer->name ?? 'there' }}, please find your quotation
        <strong>{{ $quotation->quotation_number }}</strong> below.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        @foreach ($quotation->items as $item)
            <tr>
                <td style="padding:12px 0; border-bottom:1px solid #e7e4d6; vertical-align:top;">
                    <div style="color:#1c1b16; font-size:14px; font-weight:600;">{{ $item->name_snapshot }}</div>
                    @if ($item->description)
                        <div style="color:#77746a; font-size:12px; margin-top:2px;">{{ $item->description }}</div>
                    @endif
                    <div style="color:#77746a; font-size:12px; margin-top:2px;">
                        {{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }} × {{ format_money($item->unit_price) }}
                    </div>
                </td>
                <td style="padding:12px 0; border-bottom:1px solid #e7e4d6; text-align:right; color:#1c1b16; font-size:14px; font-weight:600; white-space:nowrap;">
                    {{ format_money($item->line_total) }}
                </td>
            </tr>
        @endforeach
        @if ((float) $quotation->discount_total > 0)
            <tr>
                <td style="padding:6px 0 0 0; color:#77746a; font-size:13px;">Discount</td>
                <td style="padding:6px 0 0 0; text-align:right; color:#1c1b16; font-size:13px;">−{{ format_money($quotation->discount_total) }}</td>
            </tr>
        @endif
        @if ((float) $quotation->tax_total > 0)
            <tr>
                <td style="padding:6px 0 0 0; color:#77746a; font-size:13px;">Tax</td>
                <td style="padding:6px 0 0 0; text-align:right; color:#1c1b16; font-size:13px;">{{ format_money($quotation->tax_total) }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:12px 0 0 0; color:#1c1b16; font-size:16px; font-weight:800;">Total</td>
            <td style="padding:12px 0 0 0; text-align:right; color:#1c1b16; font-size:16px; font-weight:800; white-space:nowrap;">{{ format_money($quotation->grand_total) }}</td>
        </tr>
    </table>

    @if ($quotation->valid_until)
        <p style="margin:16px 0 0 0; color:#77746a; font-size:13px;">
            This quotation is valid until <strong style="color:#1c1b16;">{{ format_date($quotation->valid_until) }}</strong>.
        </p>
    @endif
    <p style="margin:12px 0 0 0;">Reply to this email to accept the quote or ask any questions — we’re happy to help.</p>
@endsection
