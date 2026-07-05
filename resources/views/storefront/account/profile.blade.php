@extends('layouts.storefront')

@section('title', 'Profile — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <x-storefront.account-shell active="profile">
        {{-- Details --}}
        <div class="bg-white rounded-lg border border-outline-variant overflow-hidden">
            <div class="p-5 border-b border-outline-variant">
                <h1 class="text-xl font-bold">Profile</h1>
                <p class="text-label-sm text-on-surface-variant">Update your personal information.</p>
            </div>
            <form method="POST" action="{{ route('account.profile.update') }}" class="p-5 space-y-4">
                @csrf @method('PUT')
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-label-sm font-medium mb-1">Full name <span class="text-error">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('name') border-error @enderror">
                        @error('name')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-label-sm font-medium mb-1">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" data-mask="phone" maxlength="12" inputmode="tel" placeholder="0300-0000000"
                            class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('phone') border-error @enderror">
                        @error('phone')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-label-sm font-medium mb-1">Email</label>
                    <input type="email" value="{{ $user->email }}" disabled
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low px-3 py-2.5 text-on-surface-variant cursor-not-allowed">
                    <p class="text-label-sm text-on-surface-variant mt-1">Email can't be changed here. Contact support if you need to update it.</p>
                </div>
                <div>
                    <button type="submit" class="bg-primary-container text-on-primary-container px-8 py-3 rounded-full font-bold hover:brightness-105 transition">Save changes</button>
                </div>
            </form>
        </div>

        {{-- Password --}}
        <div class="bg-white rounded-lg border border-outline-variant overflow-hidden">
            <div class="p-5 border-b border-outline-variant">
                <h2 class="text-lg font-bold">{{ $user->password ? 'Change Password' : 'Set a Password' }}</h2>
                <p class="text-label-sm text-on-surface-variant">
                    {{ $user->password ? 'Choose a strong password you don\'t use elsewhere.' : 'You signed in with a social account. Set a password to also log in with your email.' }}
                </p>
            </div>
            <form method="POST" action="{{ route('account.password.update') }}" class="p-5 space-y-4">
                @csrf @method('PUT')
                @if ($user->password)
                    <div>
                        <label class="block text-label-sm font-medium mb-1">Current password <span class="text-error">*</span></label>
                        <input type="password" name="current_password" autocomplete="current-password"
                            class="w-full max-w-md rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('current_password') border-error @enderror">
                        @error('current_password')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div class="grid sm:grid-cols-2 gap-4 max-w-2xl">
                    <div>
                        <label class="block text-label-sm font-medium mb-1">New password <span class="text-error">*</span></label>
                        <input type="password" name="password" autocomplete="new-password"
                            class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('password') border-error @enderror">
                        @error('password')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-label-sm font-medium mb-1">Confirm password <span class="text-error">*</span></label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    </div>
                </div>
                <div>
                    <button type="submit" class="bg-primary-container text-on-primary-container px-8 py-3 rounded-full font-bold hover:brightness-105 transition">{{ $user->password ? 'Update password' : 'Set password' }}</button>
                </div>
            </form>
        </div>
    </x-storefront.account-shell>
@endsection
