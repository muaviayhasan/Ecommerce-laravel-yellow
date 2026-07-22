@php($user = auth()->user())

<header class="h-20 flex items-center justify-between gap-4 px-4 sm:px-8 bg-surface-container-lowest dark:bg-surface
               border-b border-outline-variant sticky top-0 z-20">

    {{-- Hamburger (mobile menu toggle) --}}
    <div class="flex items-center flex-1 min-w-0">
        <button x-data @click="$store.adminNav.toggle()"
            class="md:hidden p-2 -ml-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors">
            <span class="material-symbols-outlined">menu</span>
        </button>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 sm:gap-5">
        <div class="flex items-center gap-1">
            {{-- Dark-mode toggle --}}
            <button x-data
                @click="
                    const dark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('admin-theme', dark ? 'dark' : 'light');
                "
                class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors"
                title="Toggle theme">
                <span class="material-symbols-outlined dark:hidden">dark_mode</span>
                <span class="material-symbols-outlined hidden dark:inline">light_mode</span>
            </button>

            {{-- Notifications: new orders + unread support messages (badge stores live in the sidebar). --}}
            <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
                <button @click="open = !open" title="Notifications"
                    class="relative p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    <template x-if="(($store.orderBadge?.count || 0) + ($store.supportBadge?.count || 0)) > 0">
                        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-error text-white text-[10px] font-bold rounded-full grid place-items-center"
                            x-text="Math.min(99, ($store.orderBadge?.count || 0) + ($store.supportBadge?.count || 0))"></span>
                    </template>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false" x-transition
                    class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-surface-container-lowest dark:bg-surface-container-high
                           border border-outline-variant rounded-xl shadow-lg z-30 overflow-hidden">
                    <div class="px-4 py-3 border-b border-outline-variant/60">
                        <span class="font-bold text-on-surface">Notifications</span>
                    </div>

                    <div class="max-h-96 overflow-y-auto divide-y divide-outline-variant/40">
                        @can('support.view')
                            <a href="{{ route('admin.support.index') }}" x-cloak x-show="($store.supportBadge?.count || 0) > 0"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-surface-container transition-colors">
                                <span class="w-9 h-9 rounded-full bg-primary-container/30 text-primary grid place-items-center shrink-0">
                                    <span class="material-symbols-outlined text-[20px]">forum</span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-on-surface">
                                        <span x-text="$store.supportBadge.count"></span> unread message<span x-show="$store.supportBadge.count != 1">s</span>
                                    </span>
                                    <span class="block text-xs text-on-surface-variant">Open the support inbox</span>
                                </span>
                            </a>
                        @endcan

                        @can('orders.view')
                            <template x-for="o in ($store.orderBadge?.items || [])" :key="o.id ?? o.number">
                                <a :href="o.url" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-container transition-colors">
                                    <span class="w-9 h-9 rounded-full bg-secondary-container/50 text-on-secondary-container grid place-items-center shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-on-surface truncate">New order <span x-text="o.number"></span></span>
                                        <span class="block text-xs text-on-surface-variant truncate"><span x-text="o.customer"></span> · <span x-text="o.total"></span></span>
                                    </span>
                                    <span class="text-[11px] text-outline shrink-0 whitespace-nowrap ml-1" x-text="o.at"></span>
                                </a>
                            </template>
                        @endcan

                        <div class="px-4 py-8 text-center"
                            x-show="(($store.orderBadge?.count || 0) + ($store.supportBadge?.count || 0)) === 0">
                            <span class="material-symbols-outlined text-outline" style="font-size:32px;">notifications_off</span>
                            <p class="mt-1 text-sm text-on-surface-variant">You're all caught up.</p>
                        </div>
                    </div>

                    @can('orders.view')
                        <a href="{{ route('admin.orders.index') }}"
                            class="block px-4 py-2.5 text-center text-xs font-semibold text-primary hover:bg-surface-container border-t border-outline-variant/60">
                            View all orders
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="h-8 w-px bg-outline-variant"></div>

        {{-- User menu --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-3">
                <div class="text-right hidden sm:block leading-tight">
                    <div class="text-sm font-bold text-on-surface">{{ $user?->name ?? 'Guest' }}</div>
                    <div class="text-[10px] text-on-surface-variant font-medium">
                        {{ $user?->getRoleNames()->first() ?? 'Admin' }}
                    </div>
                </div>
                @if ($user?->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                        class="w-10 h-10 rounded-full object-cover border border-outline-variant">
                @else
                    <span class="w-10 h-10 rounded-full bg-primary-container text-white grid place-items-center font-bold">
                        {{ strtoupper(substr($user?->name ?? 'A', 0, 1)) }}
                    </span>
                @endif
                <span class="material-symbols-outlined text-on-surface-variant text-base hidden sm:inline"
                    :class="open && 'rotate-180'">expand_more</span>
            </button>

            <div x-show="open" x-cloak @click.outside="open = false" x-transition
                class="absolute right-0 mt-2 w-48 bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant
                       rounded-xl shadow-lg py-2 z-30">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface-variant hover:bg-surface-container">
                    <span class="material-symbols-outlined text-base">storefront</span> View Store
                </a>
                <a href="{{ route('admin.profile.edit') }}"
                    class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface-variant hover:bg-surface-container">
                    <span class="material-symbols-outlined text-base">person</span> Profile
                </a>
                <div class="my-1 border-t border-outline-variant/50"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-error hover:bg-error-container/40">
                        <span class="material-symbols-outlined text-base">logout</span> Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
