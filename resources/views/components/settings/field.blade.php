@props([
    'group',
    'name',
    'meta' => [],
    'value' => null,
])

@php
    use Illuminate\Support\Str;

    $input = $meta['input'] ?? 'text';
    $label = $meta['label'] ?? Str::headline($name);
    $id = $group . '_' . $name;
    $current = old($name, $value);
@endphp

@if ($input === 'toggle')
    <x-settings.toggle :id="$id" :name="$name" :label="$label" :description="$meta['help'] ?? null" :checked="(bool) $current" />
@else
    <div class="space-y-1.5">
        <label for="{{ $id }}" class="block text-sm font-medium text-on-surface-variant">{{ $label }}</label>

        @switch($input)
            @case('select')
                <x-settings.select :id="$id" :name="$name" :options="$meta['options'] ?? []" :selected="$current" />
                @break

            @case('textarea')
                <x-settings.textarea :id="$id" :name="$name" :value="$current" :maxlength="$meta['max'] ?? null" :rows="$meta['rows'] ?? 3" />
                @break

            @case('secret')
                <x-settings.secret :id="$id" :name="$name" :has-value="filled($value)" />
                @break

            @case('media')
                <x-settings.media-picker :id="$id" :name="$name" :selected="$current"
                    :media="$meta['media'] ?? []" :placeholder="$meta['placeholder'] ?? 'Choose an image'" />
                @break

            @default
                <x-settings.input :id="$id" :name="$name" :type="$input" :value="$current"
                    :maxlength="$meta['max'] ?? null" :placeholder="$meta['placeholder'] ?? null" />
        @endswitch

        @if (! empty($meta['help']))
            <p class="text-xs text-outline">{{ $meta['help'] }}</p>
        @endif

        @error($name)
            <p class="text-xs text-error flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}
            </p>
        @enderror
    </div>
@endif
