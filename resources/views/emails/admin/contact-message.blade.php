@extends('emails.layout')

@section('preheader', 'New message from ' . ($data['name'] ?? 'a visitor'))

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">✉️ New contact message</h1>
    <p style="margin:0 0 16px 0;">Someone reached out through the website contact form. Reply directly to this email to answer them.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="color:#77746a; font-size:13px;">Name</td><td style="text-align:right; color:#1c1b16; font-size:14px; font-weight:700;">{{ $data['name'] ?? '—' }}</td></tr>
        <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Email</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $data['email'] ?? '—' }}</td></tr>
        @if (! empty($data['phone']))
            <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Phone</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $data['phone'] }}</td></tr>
        @endif
        <tr><td style="color:#77746a; font-size:13px; padding-top:4px;">Subject</td><td style="text-align:right; color:#1c1b16; font-size:13px; padding-top:4px;">{{ $data['subject'] ?? '—' }}</td></tr>
    </table>

    <div style="height:1px; background:#e7e4d6; margin:16px 0;"></div>

    <div style="background:#f4f3ea; border:1px solid #e7e4d6; border-radius:10px; padding:14px 16px; color:#3a382f; font-size:14px; line-height:1.6; white-space:pre-wrap;">{{ $data['message'] ?? '' }}</div>
@endsection
