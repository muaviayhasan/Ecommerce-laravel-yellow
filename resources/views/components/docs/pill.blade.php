@props([
    'tone' => 'neutral',   // neutral | route | perm | model | service | table
    'icon' => null,
])

@php
    $tones = [
        'neutral' => 'bg-surface-container-high text-on-surface-variant',
        'route' => 'bg-primary-container/20 text-primary',
        'perm' => 'bg-error/10 text-error',
        'model' => 'bg-secondary/10 text-secondary',
        'service' => 'bg-tertiary/15 text-tertiary',
        'table' => 'bg-surface-container-highest text-on-surface-variant',
    ];
    $cls = $tones[$tone] ?? $tones['neutral'];
@endphp

<span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 font-mono text-[12px] font-medium {{ $cls }}">
    @if ($icon)<span class="material-symbols-outlined text-[14px]">{{ $icon }}</span>@endif{{ $slot }}</span>
