@extends('layouts.storefront')
@section('robots', 'noindex, follow')

@section('title', 'Unsubscribed — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <div class="bg-surface-container-low py-16 px-4 min-h-[50vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-surface-container-lowest p-8 lg:p-10 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40 text-center">
            <span class="material-symbols-outlined text-primary text-[48px]">unsubscribe</span>
            <h1 class="text-headline-md font-bold mt-4 mb-2">You’ve been unsubscribed</h1>
            <p class="text-body-base text-on-surface-variant">
                @if ($email)
                    <strong>{{ $email }}</strong> will no longer receive marketing emails from us.
                @else
                    This unsubscribe link is invalid or has already been used.
                @endif
                You’ll still get important order-related emails.
            </p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mt-6 px-6 h-12 bg-primary-container text-on-surface font-bold rounded hover:bg-primary-fixed-dim transition-all">
                <span class="material-symbols-outlined text-[20px]">home</span> Back to store
            </a>
        </div>
    </div>
@endsection
