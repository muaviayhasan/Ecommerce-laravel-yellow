@props([
    'id',
    'name',
    'options' => [],
    'selected' => null,
])

{{-- data-no-select2: native select (admin Select2 dark theming is a later task). --}}
<select
    id="{{ $id }}"
    name="{{ $name }}"
    data-no-select2
    {{ $attributes->merge(['class' => 'w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary focus:border-primary outline-none transition cursor-pointer']) }}>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
