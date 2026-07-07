@extends('emails.layout')

@section('preheader', 'Update on your order ' . $order->order_number . '.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">Your order has an update</h1>
    <p style="margin:0 0 16px 0;">
        Hi {{ $order->customer->name ?? 'there' }}, here’s the latest on order
        <strong>{{ $order->order_number }}</strong>:
    </p>

    <div style="text-align:center; margin:8px 0 20px 0;">
        @include('emails.partials.status-badge', ['status' => $order->status])
    </div>

    @if (! empty($note))
        <p style="margin:0 0 12px 0;">{{ $note }}</p>
    @endif

    @if ($order->status === 'shipped' && $order->tracking_number)
        <div style="background:#f4f3ea; border:1px solid #e7e4d6; border-radius:10px; padding:14px 16px; margin:8px 0;">
            <div style="color:#77746a; font-size:12px;">Tracking number{{ $order->courier ? ' · ' . $order->courier : '' }}</div>
            <div style="color:#1c1b16; font-size:15px; font-weight:700; margin-top:2px;">{{ $order->tracking_number }}</div>
        </div>
    @endif

    @if ($url)
        @include('emails.partials.button', ['url' => $url, 'label' => 'View your order'])
    @endif
@endsection
