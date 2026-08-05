@extends('layouts.admin')

@section('title', $title)

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <a href="{{ route($indexRoute) }}" class="text-primary font-semibold hover:underline">{{ ucfirst($entity) }}</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Import</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">{{ $title }}</h2>
        </div>
        <a href="{{ route($templateRoute) }}"
            class="px-4 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg flex items-center gap-2 hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[20px]">download</span> Download template
        </a>
    </div>

    {{-- Result of the last run --}}
    @if ($result = session('import_result'))
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <x-admin.stat-card title="Created" tone="secondary" icon="add_circle" :value="number_format($result['created'])" />
            <x-admin.stat-card title="Updated" tone="primary" icon="edit" :value="number_format($result['updated'])" />
            <x-admin.stat-card title="Failed" tone="tertiary" icon="error" :value="number_format($result['failed'])" />
        </div>

        @if (! empty($result['errors']))
            <x-admin.panel class="!p-0 overflow-hidden">
                <div class="p-4 border-b border-outline-variant/60">
                    <h3 class="font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-error text-[20px]">error</span>
                        Rows with problems
                    </h3>
                    <p class="text-label-sm text-on-surface-variant mt-0.5">Row numbers match your spreadsheet (row 1 is the header). Fix these rows and import the file again — successful rows can be re-imported safely.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-label-sm text-on-surface-variant border-b border-outline-variant/60">
                                <th class="px-4 py-2.5 w-20">Row</th>
                                <th class="px-4 py-2.5">Problem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @foreach (array_slice($result['errors'], 0, 100) as $error)
                                <tr>
                                    <td class="px-4 py-2 font-semibold tabular-nums">{{ $error['row'] }}</td>
                                    <td class="px-4 py-2 text-on-surface-variant">{{ $error['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($result['errors']) > 100)
                        <p class="px-4 py-3 text-label-sm text-on-surface-variant">…and {{ number_format(count($result['errors']) - 100) }} more.</p>
                    @endif
                </div>
            </x-admin.panel>
        @endif
    @endif

    {{-- Upload --}}
    <x-admin.panel>
        <form method="POST" action="{{ route($postRoute) }}" enctype="multipart/form-data"
            x-data="{ file: null, dragging: false }" class="space-y-4">
            @csrf

            <label
                @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                @drop.prevent="dragging = false; file = $event.dataTransfer.files[0]?.name ?? null; $refs.input.files = $event.dataTransfer.files"
                :class="dragging ? 'border-primary bg-primary/5' : 'border-outline-variant'"
                class="flex flex-col items-center justify-center gap-2 border-2 border-dashed rounded-xl px-6 py-10 cursor-pointer hover:border-primary/60 transition-colors text-center">
                <span class="material-symbols-outlined text-primary text-[40px]">cloud_upload</span>
                <span class="font-semibold text-on-surface" x-text="file ?? 'Drop your .xlsx file here, or click to browse'"></span>
                <span class="text-label-sm text-on-surface-variant">Excel (.xlsx) only · max 10&nbsp;MB · up to {{ number_format($maxRows) }} rows</span>
                <input type="file" name="file" accept=".xlsx" required class="hidden" x-ref="input"
                    @change="file = $event.target.files[0]?.name ?? null">
            </label>
            @error('file')
                <p class="text-sm text-error font-medium">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-between gap-3 flex-wrap">
                <p class="text-label-sm text-on-surface-variant">
                    Tip: export your current {{ $entity }} first — the export file is also the edit-and-re-import template.
                </p>
                <button type="submit"
                    class="bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20 cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">cloud_upload</span>
                    Import {{ $entity }}
                </button>
            </div>
        </form>
    </x-admin.panel>

    {{-- How it works --}}
    <x-admin.panel class="!p-0 overflow-hidden">
        <div class="p-4 border-b border-outline-variant/60">
            <h3 class="font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">info</span>
                How this import works
            </h3>
            <ul class="mt-2 space-y-1.5">
                @foreach ($notes as $note)
                    <li class="text-sm text-on-surface-variant flex gap-2">
                        <span class="material-symbols-outlined text-[16px] text-primary mt-0.5">check_circle</span>
                        <span>{{ $note }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-label-sm text-on-surface-variant border-b border-outline-variant/60">
                        <th class="px-4 py-2.5">Column</th>
                        <th class="px-4 py-2.5 w-28">Required</th>
                        <th class="px-4 py-2.5">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @foreach ($columns as [$column, $required, $description])
                        <tr>
                            <td class="px-4 py-2 font-mono text-[12.5px] font-semibold whitespace-nowrap">{{ $column }}</td>
                            <td class="px-4 py-2 text-on-surface-variant">{{ $required }}</td>
                            <td class="px-4 py-2 text-on-surface-variant">{{ $description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.panel>
@endsection
