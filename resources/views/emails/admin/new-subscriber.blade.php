@extends('emails.layout')

@section('preheader', 'New newsletter subscriber: ' . $subscriber->email)

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">📣 New newsletter signup</h1>
    <p style="margin:0 0 16px 0;">Someone just subscribed to your newsletter.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="color:#77746a; font-size:13px;">Email</td><td style="text-align:right; color:#1c1b16; font-size:14px; font-weight:700;">{{ $subscriber->email }}</td></tr>
        @if ($subscriber->name)
            <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Name</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $subscriber->name }}</td></tr>
        @endif
        <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Subscribed</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ format_datetime($subscriber->subscribed_at ?? $subscriber->created_at) }}</td></tr>
        @if ($subscriber->source)
            <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Source</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $subscriber->source }}</td></tr>
        @endif
    </table>
@endsection
