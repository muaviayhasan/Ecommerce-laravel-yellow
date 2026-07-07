@extends('emails.layout')

@section('preheader', 'We’ve received your order ' . $order->order_number . '.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">Thanks for your order!</h1>
    <p style="margin:0 0 16px 0;">
        Hi {{ $order->customer->name ?? 'there' }}, we’ve received your order and are getting it ready.
        Here’s a summary:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px 0;">
        <tr>
            <td style="color:#77746a; font-size:13px;">Order number</td>
            <td style="text-align:right; color:#1c1b16; font-size:14px; font-weight:700;">{{ $order->order_number }}</td>
        </tr>
        <tr>
            <td style="color:#77746a; font-size:13px; padding-top:4px;">Placed on</td>
            <td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ format_datetime($order->placed_at ?? $order->created_at) }}</td>
        </tr>
        <tr>
            <td style="color:#77746a; font-size:13px; padding-top:4px;">Status</td>
            <td style="text-align:right; padding-top:4px;">@include('emails.partials.status-badge', ['status' => $order->status])</td>
        </tr>
    </table>

    <div style="height:1px; background:#e7e4d6; margin:16px 0;"></div>

    @include('emails.partials.order-summary', ['order' => $order])

    @if ($url)
        @include('emails.partials.button', ['url' => $url, 'label' => 'View your order'])
    @endif

    <p style="margin:16px 0 0 0; color:#77746a; font-size:13px;">
        We’ll email you again when your order status changes. Thanks for shopping with us!
    </p>
@endsection
