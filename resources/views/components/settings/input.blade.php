@props([
    'id',
    'name',
    'type' => 'text',
    'value' => null,
    'maxlength' => null,
    'placeholder' => null,
])

<input
    id="{{ $id }}"
    name="{{ $name }}"
    type="{{ $type }}"
    value="{{ $value }}"
    @if ($maxlength) maxlength="{{ $maxlength }}" @endif
    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
    {{ $attributes->merge(['class' => 'w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 px-4 text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary focus:border-primary outline-none transition']) }}>
