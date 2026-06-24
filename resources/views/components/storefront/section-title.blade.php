@props([
    'title',
    'size' => 'lg', // 'lg' = section heading, 'sm' = column heading
])

@php
    $isSmall = $size === 'sm';
@endphp

<div {{ $attributes->class(['flex items-end justify-between border-b border-gray-300', 'mb-6' => $isSmall, 'mb-8' => ! $isSmall]) }}>
    <h2 class="{{ $isSmall ? 'text-lg' : 'text-headline-md' }} font-bold pb-3 -mb-px border-b-2 border-primary-container">
        {{ $title }}
    </h2>
    {{ $slot }}
</div>
