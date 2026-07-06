@extends('emails.layout')

@section('preheader', 'Welcome to ' . setting('general', 'app_name', 'our store') . ' — your account is ready.')

@section('content')
    <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:800; color:#1c1b16;">Welcome, {{ $user->name }} 👋</h1>
    <p style="margin:0 0 12px 0;">
        Thanks for creating an account with <strong>{{ setting('general', 'app_name', config('app.name')) }}</strong>.
        You’re all set to shop, track your orders and check out faster next time.
    </p>
    <p style="margin:0 0 4px 0;">Here’s what you can do from your account:</p>
    <ul style="margin:0 0 8px 0; padding-left:20px; color:#3a382f;">
        <li style="margin-bottom:4px;">Track orders and view your history</li>
        <li style="margin-bottom:4px;">Save delivery addresses for one-tap checkout</li>
        <li style="margin-bottom:4px;">Build a wishlist and compare products</li>
    </ul>

    @include('emails.partials.button', ['url' => route('account'), 'label' => 'Go to my account'])

    <p style="margin:16px 0 0 0; color:#77746a; font-size:13px;">
        If you didn’t create this account, you can safely ignore this email.
    </p>
@endsection
