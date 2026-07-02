@props([
    'base',                 // print URL (without query)
    'label' => 'Print',
])
@php $sep = str_contains($base, '?') ? '&' : '?'; @endphp
{{-- A4 / thermal chooser. Opens the print view in a new tab with ?format=. --}}
<div x-data="{ open: false }" class="relative" @click.outside="open = false">
    <button type="button" @click="open = !open"
        class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors flex items-center gap-2">
        <span class="material-symbols-outlined text-[20px]">print</span> {{ $label }}
        <span class="material-symbols-outlined text-[18px] text-outline">expand_more</span>
    </button>
    <div x-show="open" x-cloak x-transition.origin.top
        class="absolute right-0 mt-1 w-52 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-lg shadow-xl z-30 py-1">
        <a href="{{ $base }}{{ $sep }}format=a4" target="_blank" @click="open = false"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-on-surface hover:bg-surface-container-low">
            <span class="material-symbols-outlined text-[20px] text-primary">description</span>
            <span><span class="font-medium">A4 invoice</span><span class="block text-[11px] text-outline">Full-page bill</span></span>
        </a>
        <a href="{{ $base }}{{ $sep }}format=thermal" target="_blank" @click="open = false"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-on-surface hover:bg-surface-container-low">
            <span class="material-symbols-outlined text-[20px] text-primary">receipt_long</span>
            <span><span class="font-medium">Thermal receipt</span><span class="block text-[11px] text-outline">80mm printer</span></span>
        </a>
    </div>
</div>
