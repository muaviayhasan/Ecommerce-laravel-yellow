@extends('emails.layout')

@section('preheader', 'Reset your password — this link expires soon.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">Reset your password</h1>
    <p style="margin:0 0 12px 0;">Hi {{ $user->name ?? 'there' }},</p>
    <p style="margin:0 0 12px 0;">
        We received a request to reset the password for your account. Click below to choose a new one.
    </p>

    @include('emails.partials.button', ['url' => $url, 'label' => 'Reset password'])

    <p style="margin:16px 0 8px 0; color:#77746a; font-size:13px;">
        This link expires in {{ $expires ?? 60 }} minutes. If the button doesn’t work, copy and paste
        this URL into your browser:
    </p>
    <p style="margin:0; word-break:break-all; font-size:12px;"><a href="{{ $url }}" style="color:#6f5d00;">{{ $url }}</a></p>

    <p style="margin:16px 0 0 0; color:#77746a; font-size:13px;">
        If you didn’t request a password reset, you can ignore this email — your password won’t change.
    </p>
@endsection
