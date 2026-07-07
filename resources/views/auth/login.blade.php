@extends('layouts.storefront')
@section('robots', 'noindex, follow')

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
            @if (session('error'))
                <div class="mb-6 flex items-start gap-2 bg-error-container text-on-error-container px-4 py-3 rounded text-label-sm">
                    <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

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
                    <a href="{{ route('password.request') }}" class="text-label-sm text-primary hover:underline font-medium">Forgot password?</a>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full h-12 bg-primary-container text-on-surface font-bold rounded shadow-sm hover:bg-primary-fixed-dim active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">login</span> Login
                </button>

                {{-- Social sign-in (enabled from admin Settings → Social login) --}}
                @php
                    $googleOn = \App\Support\SocialLogin::enabled('google');
                    $facebookOn = \App\Support\SocialLogin::enabled('facebook');
                @endphp
                @if ($googleOn || $facebookOn)
                    <div class="relative py-1 flex items-center">
                        <div class="flex-grow border-t border-outline-variant/60"></div>
                        <span class="mx-4 text-label-sm text-on-surface-variant">Or continue with</span>
                        <div class="flex-grow border-t border-outline-variant/60"></div>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        @if ($googleOn)
                            <a href="{{ route('social.redirect', 'google') }}"
                                class="w-full h-12 border border-outline-variant rounded flex items-center justify-center gap-3 text-body-base hover:bg-surface-container transition-colors active:scale-[0.98]">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"></path>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"></path>
                                </svg>
                                Continue with Google
                            </a>
                        @endif
                        @if ($facebookOn)
                            <a href="{{ route('social.redirect', 'facebook') }}"
                                class="w-full h-12 border border-outline-variant rounded flex items-center justify-center gap-3 text-body-base hover:bg-surface-container transition-colors active:scale-[0.98]">
                                <svg class="w-5 h-5 fill-[#1877F2]" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path>
                                </svg>
                                Continue with Facebook
                            </a>
                        @endif
                    </div>
                @endif

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
