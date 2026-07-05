@props(['status'])
@php
    $map = [
        'pending' => ['Pending', 'bg-amber-100 text-amber-700'],
        'confirmed' => ['Confirmed', 'bg-blue-100 text-blue-700'],
        'processing' => ['Processing', 'bg-blue-100 text-blue-700'],
        'packed' => ['Packed', 'bg-indigo-100 text-indigo-700'],
        'shipped' => ['Shipped', 'bg-indigo-100 text-indigo-700'],
        'out_for_delivery' => ['Out for delivery', 'bg-indigo-100 text-indigo-700'],
        'delivered' => ['Delivered', 'bg-green-100 text-green-700'],
        'completed' => ['Completed', 'bg-green-100 text-green-700'],
        'cancelled' => ['Cancelled', 'bg-red-100 text-red-700'],
        'refunded' => ['Refunded', 'bg-gray-100 text-gray-600'],
    ];
    [$label, $classes] = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'bg-gray-100 text-gray-600'];
@endphp
<span {{ $attributes->class(['inline-flex items-center px-2.5 py-0.5 rounded-full text-label-sm font-semibold whitespace-nowrap', $classes]) }}>{{ $label }}</span>
