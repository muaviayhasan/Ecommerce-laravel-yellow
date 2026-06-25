@props([
    'status',
    'type' => 'order', // 'order' | 'payment'
])

@php
    $maps = [
        'order' => [
            'pending' => 'bg-tertiary-fixed text-tertiary',
            'processing' => 'bg-primary-container/15 text-primary',
            'shipped' => 'bg-primary-container/15 text-primary',
            'delivered' => 'bg-secondary-container text-on-secondary-container',
            'completed' => 'bg-secondary-container text-on-secondary-container',
            'cancelled' => 'bg-error-container text-on-error-container',
            'refunded' => 'bg-surface-container-high text-on-surface-variant',
        ],
        'payment' => [
            'paid' => 'bg-secondary-container text-on-secondary-container',
            'partial' => 'bg-tertiary-fixed text-tertiary',
            'unpaid' => 'bg-error-container text-on-error-container',
            'refunded' => 'bg-surface-container-high text-on-surface-variant',
            'partially_refunded' => 'bg-surface-container-high text-on-surface-variant',
        ],
    ];

    $classes = $maps[$type][$status] ?? 'bg-surface-container-high text-on-surface-variant';
@endphp

<span {{ $attributes->merge(['class' => "inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize {$classes}"]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
