@extends('layouts.admin')

@section('title', 'My profile')

@php
    $field = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $label = 'block text-sm font-medium text-on-surface-variant mb-1.5';
@endphp

@section('content')
    <div>
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">My profile</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">My profile</h2>
        <p class="text-sm text-on-surface-variant mt-1">Update your personal details, photo and password.</p>
    </div>

    @if (session('status'))
        <div class="flex items-center gap-2 bg-secondary-container text-on-secondary-container px-4 py-3 rounded-lg text-sm font-medium">
            <span class="material-symbols-outlined text-[18px]">check_circle</span> {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-2 bg-error-container/50 text-on-surface px-4 py-3 rounded-lg text-sm font-medium">
            <span class="material-symbols-outlined text-error text-[18px]">error</span> {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-6 items-start">
        {{-- Details --}}
        <div class="col-span-12 lg:col-span-7">
            <x-admin.panel title="Profile details">
                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-5"
                    x-data="{ preview: null }">
                    @csrf @method('PUT')

                    {{-- Avatar --}}
                    <div class="flex items-center gap-5">
                        <div class="w-20 h-20 rounded-full overflow-hidden border border-outline-variant bg-primary-container grid place-items-center shrink-0">
                            <template x-if="preview">
                                <img :src="preview" alt="" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!preview">
                                @if ($user->avatar_url)
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-white text-2xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                @endif
                            </template>
                        </div>
                        <div>
                            <label class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-outline-variant rounded-lg cursor-pointer hover:bg-surface-container-high transition-colors">
                                <span class="material-symbols-outlined text-[18px]">photo_camera</span> Change photo
                                <input type="file" name="avatar" accept="image/*" class="hidden"
                                    @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                            </label>
                            @if ($user->avatar)
                                <button type="submit" form="remove-avatar" class="ml-2 text-sm font-semibold text-error hover:underline">Remove</button>
                            @endif
                            <p class="text-xs text-outline mt-1.5">JPG, PNG or WebP · up to 2&nbsp;MB.</p>
                            @error('avatar')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="name" class="{{ $label }}">Name <span class="text-error">*</span></label>
                        <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $user->name) }}" class="{{ $field }}">
                        @error('name')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="email" class="{{ $label }}">Email <span class="text-error">*</span></label>
                            <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $user->email) }}" class="{{ $field }}">
                            @error('email')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="{{ $label }}">Phone</label>
                            <input id="phone" name="phone" type="text" maxlength="30" value="{{ old('phone', $user->phone) }}" class="{{ $field }}">
                            @error('phone')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-[20px]">save</span> Save changes
                        </button>
                    </div>
                </form>
            </x-admin.panel>

            {{-- Account meta --}}
            <x-admin.panel title="Account" class="mt-6">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-on-surface-variant">Role</dt><dd class="font-semibold text-on-surface capitalize">{{ $user->getRoleNames()->map(fn ($r) => str_replace('-', ' ', $r))->implode(', ') ?: '—' }}</dd></div>
                    <div><dt class="text-on-surface-variant">Member since</dt><dd class="font-semibold text-on-surface">{{ $user->created_at?->format('d M Y') }}</dd></div>
                    <div><dt class="text-on-surface-variant">Last login</dt><dd class="font-semibold text-on-surface">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd></div>
                    <div><dt class="text-on-surface-variant">Email status</dt><dd class="font-semibold {{ $user->email_verified_at ? 'text-secondary' : 'text-on-surface-variant' }}">{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</dd></div>
                </dl>
            </x-admin.panel>
        </div>

        {{-- Password --}}
        <div class="col-span-12 lg:col-span-5">
            <x-admin.panel title="Change password">
                <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-5">
                    @csrf @method('PUT')
                    <div>
                        <label for="current_password" class="{{ $label }}">Current password</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="{{ $field }}">
                        @error('current_password')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="{{ $label }}">New password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="{{ $field }}">
                        <p class="text-xs text-outline mt-1">At least 8 characters.</p>
                        @error('password')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $label }}">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $field }}">
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-[20px]">lock_reset</span> Update password
                        </button>
                    </div>
                </form>
            </x-admin.panel>
        </div>
    </div>

    {{-- Standalone form for removing the avatar (referenced by the button above) --}}
    <form id="remove-avatar" method="POST" action="{{ route('admin.profile.avatar.destroy') }}" class="hidden"
        onsubmit="return confirm('Remove your profile photo?')">
        @csrf @method('DELETE')
    </form>
@endsection
