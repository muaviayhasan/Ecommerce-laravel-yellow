@extends('layouts.storefront')

@section('title', 'Login — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <div class="bg-surface-container-low py-16 px-4 min-h-[60vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-surface-container-lowest p-8 lg:p-10 rounded-lg shadow-[0_1px_3px_rgba(0,0,0,0.06)] border border-outline-variant/40">
            <div class="text-center mb-8">
                <h1 class="text-headline-md font-bold mb-2">Login</h1>
                <p class="text-body-base text-on-surface-variant">Welcome back! Sign in to your account.</p>
            </div>

            {{-- Failed / throttled login banner --}}
            @error('auth')
                <div class="mb-6 flex items-start gap-2 bg-error-container text-on-error-container px-4 py-3 rounded text-label-sm">
                    <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <form method="POST" action="{{ route('login') }}" class="space-y-6" x-data="{ show: false }">
                @csrf

                {{-- Email or phone --}}
                <div class="space-y-1.5">
                    <label for="identifier" class="block text-product-title font-semibold text-on-surface-variant">
                        Email or phone <span class="text-error">*</span>
                    </label>
                    <input id="identifier" name="identifier" type="text" required autofocus
                        value="{{ old('identifier') }}" autocomplete="username"
                        placeholder="Enter your email or phone"
                        class="w-full h-12 px-4 rounded border bg-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all text-body-base @error('identifier') border-error ring-1 ring-error @else border-outline-variant @enderror">
                    @error('identifier')
                        <p class="text-error text-label-sm flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="space-y-1.5">
                    <label for="password" class="block text-product-title font-semibold text-on-surface-variant">
                        Password <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input id="password" name="password" required :type="show ? 'text' : 'password'"
                            autocomplete="current-password" placeholder="Enter your password"
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

                {{-- Remember + forgot --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-outline-variant accent-primary-container cursor-pointer">
                        <span class="text-label-sm text-on-surface-variant group-hover:text-on-surface transition-colors">Remember me</span>
                    </label>
                    <a href="{{ route('login') }}" class="text-label-sm text-primary hover:underline font-medium">Forgot password?</a>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:bg-primary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">login</span> Login
                </button>

                {{-- Signup prompt --}}
                <div class="pt-6 border-t border-outline-variant/40 text-center">
                    <p class="text-body-base text-on-surface-variant">
                        Don't have an account?
                        <a href="{{ route('register') }}" class="text-primary font-bold hover:underline">Create an account</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
@endsection
