@extends('emails.layout')

@section('preheader', 'New quote request from ' . ($quotation->customer->name ?? 'a customer'))

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">📝 New quote request</h1>
    <p style="margin:0 0 16px 0;">A customer submitted a request for a quotation. It’s been saved as a draft.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="color:#77746a; font-size:13px;">Reference</td><td style="text-align:right; color:#1c1b16; font-size:14px; font-weight:700;">{{ $quotation->quotation_number }}</td></tr>
        <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Name</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $quotation->customer->name ?? '—' }}</td></tr>
        @if ($quotation->customer?->email)
            <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Email</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $quotation->customer->email }}</td></tr>
        @endif
        @if ($quotation->customer?->phone)
            <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Phone</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $quotation->customer->phone }}</td></tr>
        @endif
    </table>

    @if ($quotation->notes)
        <div style="background:#f4f3ea; border:1px solid #e7e4d6; border-radius:10px; padding:14px 16px; margin:16px 0; color:#3a382f; font-size:13px; white-space:pre-wrap;">{{ $quotation->notes }}</div>
    @endif

    @if ($quotation->items->count())
        <div style="height:1px; background:#e7e4d6; margin:16px 0;"></div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            @foreach ($quotation->items as $item)
                <tr>
                    <td style="padding:10px 0; border-bottom:1px solid #e7e4d6; color:#1c1b16; font-size:14px;">{{ $item->name_snapshot }}</td>
                    <td style="padding:10px 0; border-bottom:1px solid #e7e4d6; text-align:right; color:#77746a; font-size:13px; white-space:nowrap;">Qty {{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($url)
        @include('emails.partials.button', ['url' => $url, 'label' => 'Open in admin'])
    @endif
@endsection
