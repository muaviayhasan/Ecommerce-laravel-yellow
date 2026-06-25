@props([
    'title' => null,
    'viewAll' => null,   // href for an optional "View all" link
])

<div {{ $attributes->merge(['class' => 'bg-surface-container-lowest dark:bg-surface-container p-6 rounded-xl border border-outline-variant']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between mb-6 gap-4">
            <h3 class="text-lg font-bold text-on-surface">{{ $title }}</h3>
            @isset($actions)
                {{ $actions }}
            @elseif ($viewAll)
                <a href="{{ $viewAll }}" class="text-xs font-bold text-primary flex items-center gap-1 group">
                    View all
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                </a>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
