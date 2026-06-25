@php($user = auth()->user())

<header class="h-16 flex items-center justify-between gap-4 px-4 sm:px-8 bg-surface-container-lowest dark:bg-surface
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

            {{-- Notifications --}}
            <button class="relative p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
            </button>
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
                @if ($user?->avatar)
                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}"
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
                <a href="#"
                    class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface-variant hover:bg-surface-container">
                    <span class="material-symbols-outlined text-base">person</span> Profile
                </a>
                <div class="my-1 border-t border-outline-variant/50"></div>
                <form method="POST" action="{{ route('logout') }}">
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
