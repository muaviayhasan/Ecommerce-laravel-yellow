@props([
    'id',
    'name',
    'options' => [],
    'selected' => null,
    'select2' => true, // Select2 searchable widget by default; pass :select2="false" for a native select
])

{{-- Select2 by default (themed in app.css); pass :select2="false" to opt out (e.g. Alpine-bound). --}}
<select
    id="{{ $id }}"
    name="{{ $name }}"
    @unless ($select2) data-no-select2 @endunless
    {{ $attributes->merge(['class' => 'w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary focus:border-primary outline-none transition cursor-pointer']) }}>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
