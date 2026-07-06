@extends('layouts.storefront')
@section('robots', 'noindex, follow')

@section('title', 'Verify your email — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <div class="bg-surface-container-low py-16 px-4 min-h-[60vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-surface-container-lowest p-8 lg:p-10 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40 text-center">
            <span class="material-symbols-outlined text-primary text-[48px]">mark_email_unread</span>
            <h1 class="text-headline-md font-bold mt-4 mb-2">Verify your email</h1>
            <p class="text-body-base text-on-surface-variant">
                We’ve sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
                Click it to confirm your address. Didn’t get it? Request a new one below.
            </p>

            @if (session('status'))
                <div class="mt-6 flex items-start gap-2 bg-secondary-container text-on-secondary-container px-4 py-3 rounded text-label-sm text-left">
                    <span class="material-symbols-outlined text-[18px] shrink-0">check_circle</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                @csrf
                <button type="submit"
                    class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:bg-primary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">forward_to_inbox</span> Resend verification email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="text-label-sm text-on-surface-variant hover:text-primary transition-colors">Log out</button>
            </form>
        </div>
    </div>
@endsection
