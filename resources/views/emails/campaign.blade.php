@extends('emails.layout')

@section('preheader', $preheader ?? $subject)

@section('content')
    {{-- Admin-authored body. Merge tags are already substituted; HTML is intentional. --}}
    <div style="font-size:15px; line-height:1.6; color:#1c1b16;">
        {!! $bodyHtml !!}
    </div>

    @if ($coupon)
        @php
            $value = $coupon->type === 'percent'
                ? rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.') . '% off'
                : format_money($coupon->value) . ' off';
        @endphp
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
            <tr>
                <td style="background:#fff9df; border:2px dashed #e8c400; border-radius:14px; padding:22px; text-align:center;">
                    <div style="color:#7a5b00; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;">{{ $value }}</div>
                    <div style="margin:8px 0; font-size:26px; font-weight:800; letter-spacing:0.08em; color:#1c1b16;">{{ $coupon->code }}</div>
                    @if ((float) $coupon->min_subtotal > 0)
                        <div style="color:#77746a; font-size:12px;">On orders over {{ format_money($coupon->min_subtotal) }}</div>
                    @endif
                    @if ($coupon->expires_at)
                        <div style="color:#77746a; font-size:12px; margin-top:2px;">Valid until {{ format_date($coupon->expires_at) }}</div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    @include('emails.partials.button', ['url' => rtrim(config('app.url'), '/'), 'label' => 'Shop now'])
@endsection

@section('footer_extra')
    You’re receiving this because you subscribed or shopped with us.
    <a href="{{ $unsubscribeUrl }}" style="color:#77746a; text-decoration:underline;">Unsubscribe</a>.
@endsection
