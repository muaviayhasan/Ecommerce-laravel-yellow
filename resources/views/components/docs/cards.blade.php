@props(['cols' => 2])

@php
    $grid = [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 lg:grid-cols-3'][$cols] ?? 'sm:grid-cols-2';
@endphp

<div class="grid grid-cols-1 {{ $grid }} gap-4 my-5">
    {{ $slot }}
</div>
