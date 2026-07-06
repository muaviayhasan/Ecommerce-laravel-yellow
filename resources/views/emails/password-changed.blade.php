@extends('emails.layout')

@section('preheader', 'Your password was just changed.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">Your password was changed</h1>
    <p style="margin:0 0 12px 0;">Hi {{ $user->name }},</p>
    <p style="margin:0 0 12px 0;">
        This is a confirmation that the password for your account was changed on
        <strong>{{ format_datetime(now()) }}</strong>.
    </p>
    <div style="background:#fff4d6; border:1px solid #f0dc8a; border-radius:10px; padding:14px 16px; color:#7a5b00; font-size:13px;">
        If this was you, no further action is needed. If you did <strong>not</strong> change your password,
        please reset it immediately and contact us.
    </div>

    @include('emails.partials.button', ['url' => route('account.profile'), 'label' => 'Review account security'])
@endsection
