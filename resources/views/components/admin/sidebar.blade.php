@php
    use Illuminate\Support\Facades\Route;

    /** Visible if no permission gate, or the current user passes it. */
    $canSee = fn (array $item): bool => empty($item['permission']) || (auth()->check() && auth()->user()->can($item['permission']));

    /** Resolve a nav item's href: the named route if registered, else an inert placeholder. */
    $hrefFor = fn (array $item): string => ! empty($item['route']) && Route::has($item['route']) ? route($item['route']) : '#';

    $isActive = fn (array $item): bool => ! empty($item['active']) && request()->routeIs($item['active']);

    $items = config('navigation.admin', []);
@endphp

{{-- Shared off-canvas state for mobile (toggled from the header hamburger). --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('adminNav', { open: false, toggle() { this.open = !this.open } });
    });
</script>

{{-- Mobile backdrop --}}
<div x-data x-show="$store.adminNav.open" x-cloak @click="$store.adminNav.open = false"
    x-transition.opacity class="fixed inset-0 z-30 bg-black/40 md:hidden"></div>

<aside x-data
    :class="$store.adminNav.open ? 'translate-x-0' : '-translate-x-full'"
    class="w-64 bg-surface-container-lowest dark:bg-surface-container border-r border-outline-variant flex-shrink-0
           fixed inset-y-0 left-0 z-40 flex flex-col overflow-y-auto no-scrollbar
           transition-transform duration-200 md:static md:translate-x-0 md:h-screen md:sticky md:top-0">

    {{-- Brand --}}
    <div class="px-6 py-7 flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" class="text-2xl font-bold text-primary tracking-tight">
            {{ setting('general', 'app_name', config('app.name')) }}
        </a>
        <button @click="$store.adminNav.open = false" class="md:hidden p-1 text-on-surface-variant">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-4 space-y-1 pb-4">
        @foreach ($items as $item)
            @if (isset($item['heading']))
                <div class="pt-5 first:pt-0 text-[10px] font-bold text-outline uppercase tracking-widest px-3 py-2">
                    {{ $item['heading'] }}
                </div>

            @elseif (! empty($item['children']))
                @php
                    $children = array_values(array_filter($item['children'], $canSee));
                    $groupActive = collect($children)->contains(fn ($c) => $isActive($c));
                @endphp
                @if ($children)
                    <div x-data="{ open: @js($groupActive) }">
                        <button @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-colors
                                   text-on-surface-variant hover:bg-surface-container-high">
                            <span class="flex items-center gap-3">
                                <span class="material-symbols-outlined">{{ $item['icon'] ?? 'chevron_right' }}</span>
                                <span class="font-medium">{{ $item['label'] }}</span>
                            </span>
                            <span class="material-symbols-outlined text-base transition-transform"
                                :class="open && 'rotate-180'">expand_more</span>
                        </button>
                        <div x-show="open" x-collapse class="pl-11 space-y-1 pt-1">
                            @foreach ($children as $child)
                                <a href="{{ $hrefFor($child) }}"
                                    class="block px-3 py-1.5 text-sm rounded-md transition-colors
                                        {{ $isActive($child)
                                            ? 'text-primary font-semibold'
                                            : 'text-on-surface-variant hover:text-primary' }}">
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

            @elseif ($canSee($item))
                <a href="{{ $hrefFor($item) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                        {{ $isActive($item)
                            ? 'bg-primary-container text-white font-semibold'
                            : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined">{{ $item['icon'] ?? 'chevron_right' }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    {{-- Help card --}}
    <div class="m-4 p-5 bg-surface-container dark:bg-surface-container-high rounded-xl text-center border border-outline-variant">
        <div class="flex justify-center mb-3">
            <span class="material-symbols-outlined text-primary" style="font-size:40px;">support_agent</span>
        </div>
        <h4 class="font-bold text-on-surface mb-1">Need Help?</h4>
        <p class="text-xs text-on-surface-variant mb-4 leading-relaxed">
            Contact us if you need any assistance, we'll get back to you soon.
        </p>
        <a href="#" class="block w-full py-2.5 bg-primary text-on-primary font-bold text-sm rounded-lg hover:opacity-90 transition-opacity">
            Contact Us
        </a>
    </div>
</aside>
