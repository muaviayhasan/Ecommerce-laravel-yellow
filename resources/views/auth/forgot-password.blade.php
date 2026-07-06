@extends('layouts.storefront')
@section('robots', 'noindex, follow')

@section('title', 'Forgot password — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <div class="bg-surface-container-low py-16 px-4 min-h-[60vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-surface-container-lowest p-8 lg:p-10 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40">
            <div class="text-center mb-8">
                <h1 class="text-headline-md font-bold mb-2">Forgot your password?</h1>
                <p class="text-body-base text-on-surface-variant">Enter your email and we’ll send you a link to reset it.</p>
            </div>

            @if (session('status'))
                <div class="mb-6 flex items-start gap-2 bg-secondary-container text-on-secondary-container px-4 py-3 rounded text-label-sm">
                    <span class="material-symbols-outlined text-[18px] shrink-0">mark_email_read</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf
                <div class="space-y-1.5">
                    <label for="email" class="block text-product-title font-semibold text-on-surface-variant">
                        Email <span class="text-error">*</span>
                    </label>
                    <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
                        autocomplete="email" placeholder="Enter your email"
                        class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('email') border-error ring-1 ring-error @else border-outline-variant @enderror">
                    @error('email')
                        <p class="text-error text-label-sm flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:bg-primary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">send</span> Email reset link
                </button>

                <div class="pt-6 border-t border-outline-variant/40 text-center">
                    <a href="{{ route('login') }}" class="text-primary font-bold hover:underline text-body-base">Back to login</a>
                </div>
            </form>
        </div>
    </div>
@endsection
