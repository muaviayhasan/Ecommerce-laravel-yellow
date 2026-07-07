@extends('emails.layout')

@section('preheader', 'Confirm your email address to finish setting up your account.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">Verify your email</h1>
    <p style="margin:0 0 12px 0;">Hi {{ $user->name }},</p>
    <p style="margin:0 0 12px 0;">
        Please confirm your email address by clicking the button below. This helps us keep your
        account secure and lets us send you order updates.
    </p>

    @include('emails.partials.button', ['url' => $url, 'label' => 'Verify email address'])

    <p style="margin:16px 0 8px 0; color:#77746a; font-size:13px;">
        This link expires in {{ $expires ?? 60 }} minutes. If the button doesn’t work, copy and paste
        this URL into your browser:
    </p>
    <p style="margin:0; word-break:break-all; font-size:12px;"><a href="{{ $url }}" style="color:#6f5d00;">{{ $url }}</a></p>

    <p style="margin:16px 0 0 0; color:#77746a; font-size:13px;">
        If you didn’t create an account, no action is needed.
    </p>
@endsection
