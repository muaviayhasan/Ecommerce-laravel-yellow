@props(['active' => 'dashboard'])
@php
    $nav = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'account'],
        'profile' => ['label' => 'Profile', 'icon' => 'manage_accounts', 'route' => 'account.profile'],
        'orders' => ['label' => 'My Orders', 'icon' => 'receipt_long', 'route' => 'account.orders'],
        'addresses' => ['label' => 'Addresses', 'icon' => 'location_on', 'route' => 'account.addresses'],
    ];
    $user = auth()->user();
@endphp

<div class="bg-background pt-8 pb-16 md:pb-12">
    <div class="app-container">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-label-sm text-on-surface-variant mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            @if ($active === 'dashboard')
                <span class="text-on-surface">My Account</span>
            @else
                <a href="{{ route('account') }}" class="hover:text-primary transition-colors">My Account</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface">{{ $nav[$active]['label'] ?? 'Account' }}</span>
            @endif
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            {{-- Sidebar --}}
            <aside class="bg-white rounded-lg border border-outline-variant overflow-hidden">
                <div class="p-5 border-b border-outline-variant flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full overflow-hidden bg-primary-container text-on-primary-container grid place-items-center font-bold shrink-0 border-2 border-outline">
                        @if ($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold truncate">{{ $user->name }}</p>
                        <p class="text-label-sm text-on-surface-variant flex items-center gap-1 min-w-0">
                            <span class="truncate">{{ $user->email }}</span>
                            @if ($user->hasVerifiedEmail())
                                <span class="material-symbols-outlined text-blue-600 text-[15px] shrink-0"
                                    style="font-variation-settings:'FILL' 1;" title="Email verified">verified</span>
                            @endif
                        </p>
                    </div>
                </div>
                <nav class="p-2">
                    @foreach ($nav as $key => $item)
                        <a href="{{ route($item['route']) }}" @class([
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors',
                            'bg-primary-container/30 text-on-surface font-semibold' => $active === $key,
                            'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' => $active !== $key,
                        ])>
                            <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span> {{ $item['label'] }}
                        </a>
                    @endforeach
                    {{-- Opens the header's shared logout-confirm dialog instead of logging out directly. --}}
                    <div class="mt-1 pt-1 border-t border-outline-variant/60" x-data>
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-logout-confirm'))"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-error hover:bg-error/10 transition-colors">
                            <span class="material-symbols-outlined text-[20px]">logout</span> Logout
                        </button>
                    </div>
                </nav>
            </aside>

            {{-- Content --}}
            <div class="lg:col-span-3 space-y-6">
                @if (session('status'))
                    <div class="p-4 rounded-lg bg-secondary-container/40 text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">check_circle</span> {{ session('status') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="p-4 rounded-lg bg-error-container/40 text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-error">error</span> {{ session('error') }}
                    </div>
                @endif

                {{-- Optional, non-blocking nudge to verify the email (dismissible for the session). --}}
                @if (! $user->hasVerifiedEmail() && setting('emails', 'email_verification', true))
                    <div x-data="{ show: sessionStorage.getItem('hideVerifyBanner') !== '1' }" x-show="show" x-cloak
                        class="p-4 rounded-lg bg-primary-container/20 border border-primary-container/60 flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary shrink-0">mark_email_unread</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-on-surface">Verify your email address</p>
                            <p class="text-label-sm text-on-surface-variant mt-0.5">
                                We’ve emailed a confirmation link to <span class="font-semibold">{{ $user->email }}</span>. It’s optional — but verifying helps keep your account secure and easier to recover.
                            </p>
                            <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 text-primary font-bold text-label-sm hover:underline">
                                    <span class="material-symbols-outlined text-[18px]">outgoing_mail</span> Resend verification email
                                </button>
                            </form>
                        </div>
                        <button type="button" @click="show = false; sessionStorage.setItem('hideVerifyBanner','1')"
                            class="shrink-0 text-on-surface-variant hover:text-on-surface transition-colors" title="Dismiss">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
    </div>
</div>
