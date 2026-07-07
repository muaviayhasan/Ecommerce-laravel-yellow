@props([
    'title',
    'icon' => null,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    class="block rounded-xl border border-outline-variant/60 bg-surface-container-lowest dark:bg-surface-container-high p-4 {{ $href ? 'hover:border-primary/50 transition-colors' : '' }}">
    <div class="flex items-center gap-2 mb-1.5">
        @if ($icon)
            <span class="material-symbols-outlined text-primary text-[20px]">{{ $icon }}</span>
        @endif
        <span class="font-bold text-on-surface">{{ $title }}</span>
    </div>
    <div class="text-sm text-on-surface-variant leading-relaxed [&_code]:font-mono [&_code]:text-[12px] [&_code]:bg-surface-container-high [&_code]:px-1 [&_code]:rounded [&_strong]:text-on-surface [&_strong]:font-semibold">
        {{ $slot }}
    </div>
</{{ $tag }}>
