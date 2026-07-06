@extends('emails.layout')

@section('preheader', 'Test email from ' . setting('general', 'app_name', 'your store'))

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">✅ Your email settings work</h1>
    <p style="margin:0 0 12px 0;">
        This is a test message from <strong>{{ setting('general', 'app_name', config('app.name')) }}</strong>.
        If you’re reading this, your SMTP settings are configured correctly and the store can send email.
    </p>
    <p style="margin:0; color:#77746a; font-size:13px;">Sent {{ format_datetime(now()) }}.</p>
@endsection
