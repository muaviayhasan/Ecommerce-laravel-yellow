@extends('layouts.admin')

@section('title', 'Blog categories')

@php $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none'; @endphp

@section('content')
    <div class="mb-2">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.blog.posts.index') }}" class="text-primary font-semibold hover:underline">Blog</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Categories</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Blog categories</h2>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        @can('blog-categories.create')
            <div class="col-span-12 lg:col-span-4">
                <x-admin.panel title="Add category">
                    <form method="POST" action="{{ route('admin.blog.categories.store') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant">Name <span class="text-error">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" maxlength="255" class="{{ $cell }}">
                            @error('name')<p class="text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-on-surface-variant">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug') }}" maxlength="255" placeholder="Auto from name" class="{{ $cell }}">
                            @error('slug')<p class="text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-sm font-medium text-on-surface-variant">Parent</label>
                                <select name="parent_id" class="{{ $cell }} cursor-pointer">
                                    <option value="">None</option>
                                    @foreach ($parents as $id => $name)<option value="{{ $id }}" @selected((string) old('parent_id') === (string) $id)>{{ $name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-sm font-medium text-on-surface-variant">Sort</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', $nextSort) }}" min="1" max="{{ $nextSort }}" step="1"
                                    oninput="if(this.value!==''){this.value=Math.min({{ $nextSort }},Math.max(1,parseInt(this.value)||1));}"
                                    class="{{ $cell }}">
                                <p class="text-xs text-outline">Lower shows first — between 1 and {{ $nextSort }}.</p>
                            </div>
                        </div>
                        <button type="submit" class="w-full px-4 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Add category</button>
                    </form>
                </x-admin.panel>
            </div>
        @endcan

        @php
            $canReorder = auth()->user()->can('blog-categories.edit');
            $rows = $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'parent' => $c->parent?->name,
                'posts' => $c->posts_count,
                'editUrl' => route('admin.blog.categories.edit', $c),
                'deleteUrl' => route('admin.blog.categories.destroy', $c),
            ])->values();
        @endphp
        <div class="col-span-12 {{ auth()->user()->can('blog-categories.create') ? 'lg:col-span-8' : '' }}"
            x-data="catSort({ rows: @js($rows), saveUrl: '{{ route('admin.blog.categories.reorder') }}', canReorder: @js($canReorder) })">
            <x-admin.panel class="!p-0 overflow-hidden">
                @if ($canReorder)
                    <div class="px-6 py-3 border-b border-outline-variant/60 flex items-center justify-between">
                        <p class="text-xs text-on-surface-variant flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">drag_indicator</span> Drag rows to reorder</p>
                        <p x-show="saved" x-cloak class="text-xs font-semibold text-secondary flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">check_circle</span> Order saved</p>
                    </div>
                @endif
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/60">
                            <tr><th class="px-6 py-3">Name</th><th class="px-6 py-3">Parent</th><th class="px-6 py-3 text-center">Posts</th><th class="px-6 py-3 text-right">Actions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40 text-sm">
                            <template x-for="(row, i) in rows" :key="row.id">
                                <tr :draggable="canReorder"
                                    @dragstart="dragStart(i, $event)" @dragover.prevent="dragOver(i)" @drop.prevent @dragend="dragEnd()"
                                    :class="[dragging === i ? 'opacity-40' : '', canReorder ? 'cursor-move' : '']"
                                    class="hover:bg-surface-container-high/60 transition-colors">
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <span x-show="canReorder" class="material-symbols-outlined text-outline text-[18px] cursor-grab select-none shrink-0">drag_indicator</span>
                                            <div class="min-w-0"><p class="font-semibold text-on-surface truncate" x-text="row.name"></p><p class="text-[11px] text-outline font-mono truncate" x-text="row.slug"></p></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-on-surface-variant" x-text="row.parent || '—'"></td>
                                    <td class="px-6 py-3 text-center text-on-surface-variant" x-text="row.posts"></td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @can('blog-categories.edit')<a :href="row.editUrl" title="Edit" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><span class="material-symbols-outlined text-[20px] leading-none">edit</span></a>@endcan
                                            @can('blog-categories.delete')
                                                <form :action="row.deleteUrl" method="POST" @submit="if (!confirm('Delete ' + row.name + '?')) $event.preventDefault()">@csrf @method('DELETE')<button type="submit" title="Delete" class="inline-flex items-center justify-center p-2 rounded-lg text-on-surface-variant hover:bg-error-container hover:text-error"><span class="material-symbols-outlined text-[20px] leading-none">delete</span></button></form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!rows.length">
                                <tr><td colspan="4" class="px-6 py-12 text-center text-on-surface-variant"><span class="material-symbols-outlined text-outline" style="font-size:40px;">category</span><p class="mt-2 text-sm">No categories yet.</p></td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </x-admin.panel>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('catSort', (cfg) => ({
                    rows: cfg.rows || [],
                    saveUrl: cfg.saveUrl,
                    canReorder: !!cfg.canReorder,
                    dragging: null,
                    saved: false,
                    _t: null,
                    dragStart(i, e) {
                        if (!this.canReorder) return;
                        this.dragging = i;
                        e.dataTransfer.effectAllowed = 'move';
                        try { e.dataTransfer.setData('text/plain', String(i)); } catch (err) {}
                    },
                    dragOver(i) {
                        if (this.dragging === null || this.dragging === i) return;
                        const moved = this.rows.splice(this.dragging, 1)[0];
                        this.rows.splice(i, 0, moved);
                        this.dragging = i;
                    },
                    dragEnd() {
                        if (this.dragging === null) return;
                        this.dragging = null;
                        this.save();
                    },
                    async save() {
                        const ids = this.rows.map(r => r.id);
                        const token = document.querySelector('meta[name="csrf-token"]')?.content;
                        try {
                            const res = await fetch(this.saveUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                                credentials: 'same-origin',
                                body: JSON.stringify({ ids }),
                            });
                            if (res.ok) {
                                this.saved = true;
                                clearTimeout(this._t);
                                this._t = setTimeout(() => { this.saved = false; }, 1800);
                            }
                        } catch (e) {}
                    },
                }));
            });
        </script>
    @endpush
@endsection
