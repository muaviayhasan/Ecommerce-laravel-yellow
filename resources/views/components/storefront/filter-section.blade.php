@props([
    'title',
    'open' => true, // expanded by default
])

{{-- Collapsible filter group with an animated (grid-rows) expand/collapse. --}}
<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="border-b border-gray-200 mb-4">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
        class="w-full flex items-center justify-between font-bold text-body-base py-3">
        <span>{{ $title }}</span>
        <span class="material-symbols-outlined text-[20px] text-on-surface-variant transition-transform duration-300"
            :class="{ 'rotate-180': open }">expand_more</span>
    </button>
    <div class="grid transition-[grid-template-rows] duration-300 ease-in-out"
        :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
        <div class="overflow-hidden">
            <div class="pb-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
