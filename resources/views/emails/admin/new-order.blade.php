@extends('emails.layout')

@section('preheader', 'New order ' . $order->order_number . ' · ' . format_money($order->grand_total))

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">🛒 New order received</h1>
    <p style="margin:0 0 16px 0;">A new order has just been placed on the store.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px 0;">
        <tr><td style="color:#77746a; font-size:13px;">Order</td><td style="text-align:right; color:#1c1b16; font-size:14px; font-weight:700;">{{ $order->order_number }}</td></tr>
        <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Customer</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $order->customer->name ?? '—' }}@if ($order->customer?->email) · {{ $order->customer->email }}@endif</td></tr>
        <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Payment</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ ucfirst((string) $order->payment_method) }} · {{ ucfirst((string) $order->payment_status) }}</td></tr>
        <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Total</td><td style="text-align:right; color:#1c1b16; font-size:16px; font-weight:800; padding-top:4px;">{{ format_money($order->grand_total) }}</td></tr>
    </table>

    <div style="height:1px; background:#e7e4d6; margin:16px 0;"></div>

    @include('emails.partials.order-summary', ['order' => $order])

    @if ($url)
        @include('emails.partials.button', ['url' => $url, 'label' => 'Open in admin'])
    @endif
@endsection
