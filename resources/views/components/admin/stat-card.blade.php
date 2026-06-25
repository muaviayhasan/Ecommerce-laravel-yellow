@props([
    'title',
    'value',
    'icon' => 'analytics',
    'tone' => 'primary',      // primary | tertiary | neutral | secondary
    'trend' => null,          // signed float (e.g. 1.56 / -1.56 / 0) or null
])

@php
    $chip = match ($tone) {
        'tertiary' => 'bg-tertiary-fixed text-tertiary',
        'neutral' => 'bg-surface-container-low text-primary',
        'secondary' => 'bg-secondary-container text-on-secondary-container',
        default => 'bg-surface-container-high text-primary',
    };

    $t = $trend === null ? null : (float) $trend;
    [$trendColor, $trendIcon] = match (true) {
        $t === null => ['text-outline', 'remove'],
        $t > 0 => ['text-secondary', 'trending_up'],
        $t < 0 => ['text-error', 'trending_down'],
        default => ['text-outline', 'remove'],
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface-container-lowest dark:bg-surface-container p-6 rounded-xl border border-outline-variant flex items-start justify-between gap-4 hover:shadow-lg transition-all']) }}>
    <div class="space-y-3 min-w-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 {{ $chip }} rounded-lg flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined">{{ $icon }}</span>
            </div>
            <span class="text-on-surface-variant font-semibold text-sm truncate">{{ $title }}</span>
        </div>
        <div>
            <div class="text-2xl font-bold text-on-surface">{{ $value }}</div>
            @if ($trend !== null)
                <div class="flex items-center text-xs {{ $trendColor }} font-bold mt-1">
                    <span class="material-symbols-outlined text-sm mr-1">{{ $trendIcon }}</span>
                    <span>{{ number_format(abs($t), 2) }}%</span>
                </div>
            @endif
        </div>
    </div>

    @isset($spark)
        <div class="h-12 w-20 shrink-0">{{ $spark }}</div>
    @endisset
</div>
