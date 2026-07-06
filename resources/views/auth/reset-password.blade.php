@extends('layouts.storefront')
@section('robots', 'noindex, follow')

@section('title', 'Reset password — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <div class="bg-surface-container-low py-16 px-4 min-h-[60vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-surface-container-lowest p-8 lg:p-10 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40">
            <div class="text-center mb-8">
                <h1 class="text-headline-md font-bold mb-2">Set a new password</h1>
                <p class="text-body-base text-on-surface-variant">Choose a strong password you don’t use elsewhere.</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-6" x-data="{ show: false }">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="space-y-1.5">
                    <label for="email" class="block text-product-title font-semibold text-on-surface-variant">
                        Email <span class="text-error">*</span>
                    </label>
                    <input id="email" name="email" type="email" required value="{{ old('email', $email) }}"
                        autocomplete="email"
                        class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('email') border-error ring-1 ring-error @else border-outline-variant @enderror">
                    @error('email')
                        <p class="text-error text-label-sm flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="block text-product-title font-semibold text-on-surface-variant">
                        New password <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input id="password" name="password" required :type="show ? 'text' : 'password'"
                            autocomplete="new-password" placeholder="At least 8 characters"
                            class="w-full h-12 px-4 pr-12 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('password') border-error ring-1 ring-error @else border-outline-variant @enderror">
                        <button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary focus:outline-none">
                            <span class="material-symbols-outlined" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-error text-label-sm flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-product-title font-semibold text-on-surface-variant">
                        Confirm password <span class="text-error">*</span>
                    </label>
                    <input id="password_confirmation" name="password_confirmation" required :type="show ? 'text' : 'password'"
                        autocomplete="new-password" placeholder="Re-enter your new password"
                        class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base border-outline-variant">
                </div>

                <button type="submit"
                    class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:bg-primary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">lock_reset</span> Reset password
                </button>
            </form>
        </div>
    </div>
@endsection
