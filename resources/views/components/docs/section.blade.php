@props([
    'id' => null,
    'title',
    'icon' => null,
])

{{-- A titled documentation section with an anchor id for in-page links. --}}
<section @if ($id) id="{{ $id }}" @endif class="scroll-mt-24 mt-12 first:mt-0">
    <div class="flex items-center gap-3 pb-3 mb-2 border-b border-outline-variant/60">
        @if ($icon)
            <span class="material-symbols-outlined text-primary">{{ $icon }}</span>
        @endif
        <h2 class="text-2xl font-bold text-on-surface">{{ $title }}</h2>
    </div>
    {{ $slot }}
</section>
