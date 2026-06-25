@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <span class="text-xs text-on-surface-variant">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}
        </span>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-outline opacity-40">
                    <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    class="w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                </a>
            @endif

            <span class="px-3 text-sm font-semibold text-on-surface">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    class="w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                </a>
            @else
                <span class="w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-outline opacity-40">
                    <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                </span>
            @endif
        </div>
    </div>
@endif
