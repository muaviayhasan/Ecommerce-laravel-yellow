@php
    use Illuminate\Support\Str;
@endphp

<div class="space-y-6">
    {{-- Breadcrumb + page header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-label-sm mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
                <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
                <span class="text-on-surface-variant font-semibold">Gallery</span>
            </div>
            <h2 class="text-2xl font-bold text-on-surface">Media Gallery</h2>
        </div>

        <label class="cursor-pointer bg-primary text-on-primary px-5 py-2.5 rounded-lg font-semibold text-sm
                      flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-sm shadow-primary/20">
            <input type="file" wire:model="uploads" multiple accept="image/*" class="hidden">
            <span class="material-symbols-outlined">cloud_upload</span>
            Upload
        </label>
    </div>

    {{-- Flash --}}
    @if (session('gallery_status'))
        <div class="flex items-center gap-2 bg-secondary-container text-on-secondary-container px-4 py-2.5 rounded-lg text-sm font-medium"
            x-data x-init="setTimeout(() => $el.remove(), 4000)">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            {{ session('gallery_status') }}
        </div>
    @endif

    {{-- Toolbar --}}
    <x-admin.panel class="!p-4">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            {{-- Drag & drop zone --}}
            <label x-data="{ over: false }"
                x-on:dragover.prevent="over = true"
                x-on:dragleave.prevent="over = false"
                x-on:drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))"
                :class="over ? 'border-primary bg-primary-container/10' : 'border-outline-variant'"
                class="cursor-pointer flex-1 flex items-center gap-3 border-2 border-dashed rounded-xl px-4 py-2.5 transition-colors">
                <input x-ref="input" type="file" wire:model="uploads" multiple accept="image/*" class="hidden">
                <span class="material-symbols-outlined text-primary">cloud_upload</span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-on-surface">Drop images here or <span class="text-primary">browse</span></p>
                    <p class="text-[11px] text-outline">PNG, JPG, WEBP · up to 5 MB each</p>
                </div>
                <span wire:loading wire:target="uploads"
                    class="ml-auto flex items-center gap-1.5 text-primary text-xs font-semibold shrink-0">
                    <span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    Uploading…
                </span>
            </label>

            {{-- Filters --}}
            <div class="flex items-center gap-2 shrink-0">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search files…" maxlength="255"
                        class="w-44 lg:w-56 bg-surface-container-low border border-outline-variant/40 rounded-lg pl-10 pr-3 py-2
                               text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none">
                </div>

                @if ($this->folders->isNotEmpty())
                    <select wire:model.live="folder" data-no-select2
                        class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                        <option value="">All folders</option>
                        @foreach ($this->folders as $f)
                            <option value="{{ $f }}">{{ $f }}</option>
                        @endforeach
                    </select>
                @endif

                <select wire:model.live="sort" data-no-select2
                    class="bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                    <option value="recent">Recent first</option>
                    <option value="oldest">Oldest first</option>
                    <option value="name_asc">Name A–Z</option>
                    <option value="name_desc">Name Z–A</option>
                    <option value="largest">Largest</option>
                </select>

                <div class="flex items-center bg-surface-container-low rounded-lg p-1">
                    <button type="button" wire:click="$set('view', 'grid')" title="Grid view"
                        @class([
                            'p-1.5 rounded transition-colors',
                            'bg-primary-container text-white shadow-sm' => $view === 'grid',
                            'text-on-surface-variant hover:text-primary' => $view !== 'grid',
                        ])>
                        <span class="material-symbols-outlined text-[20px]">grid_view</span>
                    </button>
                    <button type="button" wire:click="$set('view', 'list')" title="List view"
                        @class([
                            'p-1.5 rounded transition-colors',
                            'bg-primary-container text-white shadow-sm' => $view === 'list',
                            'text-on-surface-variant hover:text-primary' => $view !== 'list',
                        ])>
                        <span class="material-symbols-outlined text-[20px]">view_list</span>
                    </button>
                </div>
            </div>
        </div>

        @error('uploads.*')
            <p class="mt-3 text-sm text-error flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">error</span>{{ $message }}
            </p>
        @enderror
    </x-admin.panel>

    {{-- Assets + detail --}}
    <div class="flex flex-col xl:flex-row gap-6 items-start">
        <x-admin.panel class="flex-1 w-full min-w-0 !p-0 overflow-hidden">
            {{-- Grid header --}}
            <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between gap-4">
                <div class="flex items-baseline gap-2">
                    <span class="text-sm font-bold text-on-surface uppercase tracking-wider">File Management</span>
                    <span class="text-xs text-outline">{{ number_format($this->media->total()) }} file(s)</span>
                </div>
            </div>

            @if ($this->media->isEmpty())
                <div class="flex flex-col items-center justify-center text-center py-20 px-6">
                    <span class="material-symbols-outlined text-outline" style="font-size:56px;">photo_library</span>
                    <p class="mt-4 font-semibold text-on-surface">No media yet</p>
                    <p class="text-sm text-on-surface-variant mt-1 max-w-xs">
                        Drag images onto the upload area above, or click <span class="font-semibold text-primary">Upload</span> to add your first files.
                    </p>
                </div>

            @elseif ($view === 'grid')
                {{-- GRID --}}
                <div class="p-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                    @foreach ($this->media as $m)
                        <button type="button" wire:key="media-{{ $m->id }}" wire:click="select({{ $m->id }})"
                            class="group text-left focus:outline-none">
                            <div @class([
                                    'relative aspect-square bg-surface-container-low rounded-xl border p-4 flex items-center justify-center overflow-hidden transition-all',
                                    'ring-2 ring-primary border-primary' => $selectedId === $m->id,
                                    'border-outline-variant/50 group-hover:border-primary/40 group-hover:shadow-md' => $selectedId !== $m->id,
                                ])>
                                <img src="{{ $m->url }}" alt="{{ $m->alt }}" loading="lazy"
                                    class="max-h-full max-w-full object-contain transition-transform group-hover:scale-105">
                            </div>
                            <p class="mt-2 text-sm font-medium text-on-surface-variant group-hover:text-primary line-clamp-1">
                                {{ $m->title ?: basename($m->path) }}
                            </p>
                            <p class="text-[11px] text-outline">
                                {{ format_bytes($m->size) }} · {{ Str::upper(Str::afterLast($m->mime ?? 'file', '/')) }}
                            </p>
                        </button>
                    @endforeach
                </div>

            @else
                {{-- LIST --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr>
                                <th class="px-6 py-3">File</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Size</th>
                                <th class="px-6 py-3 hidden md:table-cell">Uploaded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40 text-sm">
                            @foreach ($this->media as $m)
                                <tr wire:key="row-{{ $m->id }}" wire:click="select({{ $m->id }})"
                                    @class([
                                        'cursor-pointer transition-colors',
                                        'bg-primary-container/10' => $selectedId === $m->id,
                                        'hover:bg-surface-container-high' => $selectedId !== $m->id,
                                    ])>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-surface-container-low border border-outline-variant/40 overflow-hidden flex items-center justify-center shrink-0">
                                                <img src="{{ $m->url }}" alt="{{ $m->alt }}" loading="lazy" class="max-w-full max-h-full object-contain p-1">
                                            </div>
                                            <span class="font-semibold text-on-surface line-clamp-1">{{ $m->title ?: basename($m->path) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-on-surface-variant">{{ Str::upper(Str::afterLast($m->mime ?? 'file', '/')) }}</td>
                                    <td class="px-6 py-3 text-on-surface-variant">{{ format_bytes($m->size) }}</td>
                                    <td class="px-6 py-3 text-on-surface-variant hidden md:table-cell">{{ format_date($m->created_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Pagination --}}
            @if ($this->media->hasPages())
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-between gap-4">
                    <span class="text-xs text-on-surface-variant">
                        Showing {{ $this->media->firstItem() }}–{{ $this->media->lastItem() }} of {{ number_format($this->media->total()) }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button wire:click="previousPage" @disabled($this->media->onFirstPage())
                            class="w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant
                                   hover:bg-surface-container-high disabled:opacity-40 disabled:pointer-events-none transition-colors">
                            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                        </button>
                        <span class="px-3 text-sm font-semibold text-on-surface">{{ $this->media->currentPage() }} / {{ $this->media->lastPage() }}</span>
                        <button wire:click="nextPage" @disabled(! $this->media->hasMorePages())
                            class="w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant
                                   hover:bg-surface-container-high disabled:opacity-40 disabled:pointer-events-none transition-colors">
                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                        </button>
                    </div>
                </div>
            @endif
        </x-admin.panel>

        {{-- Detail panel --}}
        @if ($this->selected)
            @php($sel = $this->selected)
            <aside class="w-full xl:w-80 shrink-0" wire:key="detail-{{ $sel->id }}">
                <x-admin.panel class="xl:sticky xl:top-24">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-lg font-bold text-on-surface">Details</h3>
                        <button wire:click="deselect" class="text-on-surface-variant hover:text-primary p-1 -mr-1">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="bg-surface-container-low rounded-lg p-6 mb-5 flex items-center justify-center aspect-square overflow-hidden">
                        <img src="{{ $sel->url }}" alt="{{ $sel->alt }}" class="max-w-full max-h-full object-contain">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Uploaded</p>
                            <p class="text-sm text-on-surface">{{ format_date($sel->created_at) }}</p>
                            <p class="text-xs text-outline">{{ format_time($sel->created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Size · Type</p>
                            <p class="text-sm text-on-surface">{{ format_bytes($sel->size) }}</p>
                            <p class="text-xs text-outline">
                                {{ Str::upper(Str::afterLast($sel->mime ?? 'file', '/')) }}{{ $sel->width ? ' · '.$sel->width.'×'.$sel->height : '' }}
                            </p>
                        </div>
                    </div>

                    {{-- Full URL + copy --}}
                    <div class="mb-5" x-data="{ copied: false }">
                        <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Full URL</p>
                        <div class="flex items-center gap-2 p-2.5 bg-surface-container-low border border-outline-variant rounded-lg">
                            <span class="text-xs text-on-surface-variant truncate flex-1">{{ url($sel->url) }}</span>
                            <button type="button" title="Copy URL"
                                @click="navigator.clipboard.writeText('{{ url($sel->url) }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                class="text-primary hover:text-primary-container transition-colors shrink-0">
                                <span class="material-symbols-outlined text-[20px]" x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                            </button>
                        </div>
                    </div>

                    {{-- Edit metadata --}}
                    @can('gallery.edit')
                        <div class="space-y-3 pt-5 border-t border-outline-variant">
                            <div>
                                <label class="text-[10px] font-bold text-outline uppercase tracking-widest">Title</label>
                                <input type="text" wire:model="editTitle" maxlength="255"
                                    class="mt-1 w-full bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                                @error('editTitle') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-outline uppercase tracking-widest">Alt text</label>
                                <input type="text" wire:model="editAlt" maxlength="255"
                                    class="mt-1 w-full bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none">
                                @error('editAlt') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endcan

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-5">
                        @can('gallery.edit')
                            <button wire:click="saveMeta" wire:loading.attr="disabled" wire:target="saveMeta"
                                class="flex-1 bg-primary text-on-primary py-2.5 rounded-lg font-semibold text-sm flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition-all">
                                <span class="material-symbols-outlined text-[18px]">save</span> Save
                            </button>
                        @endcan
                        @can('gallery.delete')
                            <button wire:click="delete({{ $sel->id }})" wire:confirm="Delete this file? This cannot be undone."
                                class="p-2.5 rounded-lg bg-error-container text-on-error-container hover:brightness-95 transition-all"
                                title="Delete">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        @endcan
                    </div>
                </x-admin.panel>
            </aside>
        @endif
    </div>
</div>
