@props([
    'id',
    'name',
    'hasValue' => false,
])

{{-- §4.3: never render the stored secret. Blank submit = keep existing value. --}}
<div class="relative" x-data="{ show: false }">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        :type="show ? 'text' : 'password'"
        autocomplete="new-password"
        placeholder="{{ $hasValue ? '•••••••• — leave blank to keep' : 'Not set' }}"
        {{ $attributes->merge(['class' => 'w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 pl-4 pr-11 text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary focus:border-primary outline-none transition']) }}>
    <button type="button" @click="show = !show" tabindex="-1"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
    </button>
</div>
