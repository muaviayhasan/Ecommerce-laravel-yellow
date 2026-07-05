@extends('layouts.storefront')
@section('robots', 'noindex, follow')

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
            <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="p-5 space-y-5">
                @csrf @method('PUT')

                {{-- Avatar --}}
                <div class="flex items-center gap-4" x-data="{ preview: @js($user->avatar_url) }">
                    <div class="w-20 h-20 rounded-full overflow-hidden shrink-0 border border-outline-variant grid place-items-center bg-primary-container text-on-primary-container text-2xl font-bold">
                        <template x-if="preview"><img :src="preview" alt="" class="w-full h-full object-cover"></template>
                        <template x-if="!preview"><span>{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span></template>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-1.5 border border-primary text-primary px-4 py-2 rounded-full font-bold text-label-sm hover:bg-primary-container/20 transition cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">photo_camera</span> Upload photo
                            <input type="file" name="avatar" accept="image/*" class="hidden"
                                @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)">
                        </label>
                        <p class="text-label-sm text-on-surface-variant mt-1">JPG, PNG or WebP · up to 2 MB.</p>
                        @error('avatar')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-label-sm font-medium mb-1">Full name <span class="text-error">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('name') border-error @enderror">
                        @error('name')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-label-sm font-medium mb-1">Phone</label>
                        <x-storefront.phone-input :value="old('phone', $user->phone)" />
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
        <div class="bg-white rounded-lg border border-outline-variant overflow-hidden"
            x-data="{ show: {{ ($errors->has('password') || $errors->has('current_password')) ? 'true' : 'false' }} }">
            <div class="p-5 border-b border-outline-variant flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Password</h2>
                    <p class="text-label-sm text-on-surface-variant">
                        {{ $user->password ? 'Change the password you use to sign in.' : 'You signed in with a social account — set a password to also log in with your email.' }}
                    </p>
                </div>
                <button type="button" x-show="!show" @click="show = true"
                    class="shrink-0 inline-flex items-center gap-1.5 border border-primary text-primary px-4 py-2 rounded-full font-bold text-label-sm hover:bg-primary-container/20 transition">
                    <span class="material-symbols-outlined text-[18px]">lock_reset</span> {{ $user->password ? 'Change password' : 'Set password' }}
                </button>
            </div>
            <form method="POST" action="{{ route('account.password.update') }}" class="p-5 space-y-4" x-show="show" x-transition x-cloak>
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
                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-primary-container text-on-primary-container px-8 py-3 rounded-full font-bold hover:brightness-105 transition">{{ $user->password ? 'Update password' : 'Set password' }}</button>
                    <button type="button" @click="show = false" class="px-5 py-3 rounded-full font-bold text-on-surface-variant hover:bg-surface-container transition">Cancel</button>
                </div>
            </form>
        </div>
    </x-storefront.account-shell>
@endsection
