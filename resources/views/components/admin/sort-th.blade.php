@props([
    'col',            // dataset key on each <tr> (data-<col>)
    'label',          // header text
    'type' => 'text', // 'text' or 'num'
])

{{-- Client-side sortable header. Use inside a <table x-data="sortableTable"> whose
     <tbody> rows carry data-sortable + data-<col>="…" attributes. --}}
<button type="button" @click="sortBy(@js($col), @js($type))"
    class="group inline-flex items-center gap-1 hover:text-primary transition-colors"
    :class="sortCol === @js($col) ? 'text-primary' : ''">
    <span>{{ $label }}</span>
    <span class="material-symbols-outlined text-[15px] leading-none"
        :class="sortCol === @js($col) ? '' : 'opacity-30 group-hover:opacity-70'"
        x-text="sortCol === @js($col) ? (sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'">unfold_more</span>
</button>
