@props([
    'id',
    'name',
    'options' => [],
    'selected' => null,
    'select2' => false, // opt in to the Select2 searchable widget (themed in app.css)
])

{{-- Native by default (data-no-select2); pass :select2="true" for the searchable widget. --}}
<select
    id="{{ $id }}"
    name="{{ $name }}"
    @unless ($select2) data-no-select2 @endunless
    {{ $attributes->merge(['class' => 'w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary focus:border-primary outline-none transition cursor-pointer']) }}>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
