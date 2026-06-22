@props([
    'title',
    'arrows' => false, // show carousel chevrons on the right
])

<div class="flex items-center justify-between mb-8 border-b border-outline-variant pb-4">
    <h2 class="text-headline-md font-bold">{{ $title }}</h2>
    @if ($arrows)
        <div class="flex gap-2">
            <button type="button" aria-label="Previous"
                class="hover:text-primary transition-colors">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
            <button type="button" aria-label="Next" class="hover:text-primary transition-colors">
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>
    @endif
</div>
