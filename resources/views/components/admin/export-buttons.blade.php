{{-- Excel + PDF export links for an index toolbar. Carries the current filter
     query onto the export so the file matches what the admin is looking at. --}}
@props(['route', 'permission'])

@can($permission)
    <a href="{{ route($route, array_merge(request()->query(), ['format' => 'xlsx'])) }}"
        class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg flex items-center gap-2 hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-[20px]">download</span> Excel
    </a>
    <a href="{{ route($route, array_merge(request()->query(), ['format' => 'pdf'])) }}"
        class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg flex items-center gap-2 hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-[20px]">picture_as_pdf</span> PDF
    </a>
@endcan
