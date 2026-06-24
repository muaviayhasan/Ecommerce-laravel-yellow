@props([
    'title',
    'count', // number of slides
])

{{--
    Reusable product slider shell. The caller passes the slide markup as the slot
    (each a `<div class="w-full shrink-0">…</div>`). Alpine `current` drives the
    track transform, the prev/next arrows, and the dots.
--}}
<div x-data="{
    current: 0,
    count: {{ (int) $count }},
    next() { this.current = (this.current + 1) % this.count; },
    prev() { this.current = (this.current - 1 + this.count) % this.count; },
    go(i) { this.current = i; },
}">
    <x-storefront.section-title :title="$title">
        <div class="flex gap-2 pb-2 text-on-surface-variant">
            <button type="button" @click="prev()" aria-label="Previous" class="hover:text-primary transition-colors">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
            <button type="button" @click="next()" aria-label="Next" class="hover:text-primary transition-colors">
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>
    </x-storefront.section-title>

    {{-- Track. overflow-x-clip (not hidden) so bottom-row hover panels aren't clipped. --}}
    <div class="overflow-x-clip">
        <div class="flex transition-transform duration-500 ease-in-out"
            :style="`transform: translateX(-${current * 100}%)`">
            {{ $slot }}
        </div>
    </div>

    {{-- Dots --}}
    <div class="flex justify-center gap-2 mt-8">
        <template x-for="i in count" :key="i">
            <button type="button" @click="go(i - 1)" :aria-label="`Go to slide ${i}`"
                class="h-2.5 rounded-full transition-all"
                :class="current === (i - 1) ? 'w-8 bg-primary-container' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"></button>
        </template>
    </div>
</div>
