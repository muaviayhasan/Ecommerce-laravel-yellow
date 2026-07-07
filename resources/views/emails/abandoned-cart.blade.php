@extends('emails.layout')

@section('preheader', 'Your items are still waiting — complete your order in a couple of clicks.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">You left something behind</h1>
    <p style="margin:0 0 16px 0;">
        Hi {{ $cart->name ?: 'there' }}, we saved your cart so you don’t have to start over.
        Here’s what’s waiting for you:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px 0;">
        @foreach ($cart->items as $item)
            <tr>
                <td width="56" style="padding:8px 12px 8px 0; vertical-align:top;">
                    @if (! empty($item['image']))
                        <img src="{{ $item['image'] }}" width="48" height="48" alt=""
                            style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid #e7e4d6;">
                    @endif
                </td>
                <td style="padding:8px 0; vertical-align:top; color:#1c1b16; font-size:14px;">
                    <strong>{{ $item['name'] ?? 'Item' }}</strong><br>
                    <span style="color:#77746a; font-size:13px;">Qty {{ (int) ($item['qty'] ?? 1) }}</span>
                </td>
                <td style="padding:8px 0; vertical-align:top; text-align:right; color:#1c1b16; font-size:14px; white-space:nowrap;">
                    {{ format_money(($item['price'] ?? 0) * ($item['qty'] ?? 1)) }}
                </td>
            </tr>
        @endforeach
    </table>

    <div style="height:1px; background:#e7e4d6; margin:12px 0;"></div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="color:#1c1b16; font-size:15px; font-weight:800;">Subtotal</td>
            <td style="text-align:right; color:#1c1b16; font-size:15px; font-weight:800;">{{ format_money($cart->subtotal) }}</td>
        </tr>
    </table>

    @if ($coupon)
        <div style="margin:20px 0 0 0; padding:14px 16px; background:#fffbe6; border:1px dashed #6f5d00; border-radius:10px; text-align:center;">
            <div style="color:#77746a; font-size:13px;">Here’s a little something to finish up:</div>
            <div style="color:#6f5d00; font-size:20px; font-weight:800; letter-spacing:0.04em; margin-top:4px;">{{ $coupon->code }}</div>
            @if ($coupon->type === 'percent')
                <div style="color:#77746a; font-size:13px; margin-top:2px;">{{ rtrim(rtrim((string) $coupon->value, '0'), '.') }}% off your order</div>
            @elseif ($coupon->type === 'fixed')
                <div style="color:#77746a; font-size:13px; margin-top:2px;">{{ format_money($coupon->value) }} off your order</div>
            @endif
        </div>
    @endif

    @include('emails.partials.button', ['url' => $recoverUrl, 'label' => 'Complete your order'])

    <p style="margin:16px 0 0 0; color:#77746a; font-size:13px;">
        Prices and availability may change, so don’t wait too long. If you’ve already checked out, please ignore this.
    </p>
@endsection

@section('footer_extra')
    Don’t want cart reminders?
    <a href="{{ $unsubscribeUrl }}" style="color:#6f5d00; text-decoration:none;">Unsubscribe</a>.
@endsection
