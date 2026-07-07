@props([
    'tone' => 'info',   // info | tip | warning | key
    'title' => null,
    'icon' => null,
])

@php
    $tones = [
        'info' => ['bg-primary-container/15 border-primary/30', 'text-primary', 'info'],
        'tip' => ['bg-secondary/10 border-secondary/30', 'text-secondary', 'lightbulb'],
        'warning' => ['bg-error/10 border-error/30', 'text-error', 'warning'],
        'key' => ['bg-tertiary/10 border-tertiary/30', 'text-tertiary', 'key'],
    ];
    [$box, $accent, $defaultIcon] = $tones[$tone] ?? $tones['info'];
@endphp

<div class="flex gap-3 rounded-xl border {{ $box }} p-4 my-5">
    <span class="material-symbols-outlined {{ $accent }} shrink-0">{{ $icon ?? $defaultIcon }}</span>
    <div class="text-sm text-on-surface-variant leading-relaxed [&_strong]:text-on-surface [&_code]:font-mono [&_code]:text-[12.5px] [&_code]:bg-surface-container-high [&_code]:px-1 [&_code]:rounded">
        @if ($title)
            <p class="font-bold text-on-surface mb-1">{{ $title }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
