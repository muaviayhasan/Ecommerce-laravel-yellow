@props([
    'id',
    'name',
    'value' => null,
    'maxlength' => null,
    'rows' => 3,
])

<textarea
    id="{{ $id }}"
    name="{{ $name }}"
    rows="{{ $rows }}"
    @if ($maxlength) maxlength="{{ $maxlength }}" @endif
    {{ $attributes->merge(['class' => 'w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 px-4 text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary focus:border-primary outline-none transition resize-y']) }}>{{ $value }}</textarea>
