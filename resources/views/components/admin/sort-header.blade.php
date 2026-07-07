@props([
    'column',          // sort key sent as ?sort=
    'label',           // header text
])

{{-- A clickable table header that toggles ?sort=<column>&dir=asc|desc, preserving
     all other query params (filters, search, per_page). Drop it inside a <th>. --}}
@php
    $current = request('sort');
    $currentDir = request('dir') === 'asc' ? 'asc' : 'desc';
    $active = $current === $column;
    $nextDir = $active && $currentDir === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir, 'page' => 1]);
    $icon = ! $active ? 'unfold_more' : ($currentDir === 'asc' ? 'arrow_upward' : 'arrow_downward');
@endphp
<a href="{{ $url }}"
    {{ $attributes->merge(['class' => 'group inline-flex items-center gap-1 hover:text-primary transition-colors ' . ($active ? 'text-primary' : '')]) }}>
    <span>{{ $label }}</span>
    <span class="material-symbols-outlined text-[15px] leading-none {{ $active ? '' : 'opacity-30 group-hover:opacity-70' }}">{{ $icon }}</span>
</a>
