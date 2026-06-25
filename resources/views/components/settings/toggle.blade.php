@props([
    'id',
    'name',
    'label',
    'description' => null,
    'checked' => false,
])

{{-- Submits value=1 when on, absent when off → resolve with $request->boolean() (§8). --}}
<div class="flex items-center justify-between gap-4 p-3 -mx-3 rounded-lg hover:bg-surface-container-low transition-colors">
    <div class="min-w-0">
        <p class="text-sm font-medium text-on-surface">{{ $label }}</p>
        @if ($description)
            <p class="text-xs text-on-surface-variant mt-0.5">{{ $description }}</p>
        @endif
    </div>
    <label for="{{ $id }}" class="relative inline-flex items-center cursor-pointer shrink-0">
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" class="sr-only peer" @checked($checked)>
        <div class="w-11 h-6 bg-outline-variant rounded-full peer peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-primary/40
                    after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                    after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
    </label>
</div>
