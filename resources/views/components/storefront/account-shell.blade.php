@props(['active' => 'dashboard'])
@php
    $nav = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'account'],
        'orders' => ['label' => 'My Orders', 'icon' => 'receipt_long', 'route' => 'account.orders'],
        'addresses' => ['label' => 'Addresses', 'icon' => 'location_on', 'route' => 'account.addresses'],
        'profile' => ['label' => 'Account Details', 'icon' => 'manage_accounts', 'route' => 'account.profile'],
    ];
    $user = auth()->user();
@endphp

<div class="bg-background py-8">
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
                    <div class="w-11 h-11 rounded-full bg-primary-container text-on-primary-container grid place-items-center font-bold shrink-0">
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold truncate">{{ $user->name }}</p>
                        <p class="text-label-sm text-on-surface-variant truncate">{{ $user->email }}</p>
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
                    <form method="POST" action="{{ route('logout') }}" class="mt-1 pt-1 border-t border-outline-variant/60">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-error hover:bg-error/10 transition-colors">
                            <span class="material-symbols-outlined text-[20px]">logout</span> Logout
                        </button>
                    </form>
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

                {{ $slot }}
            </div>
        </div>
    </div>
</div>
