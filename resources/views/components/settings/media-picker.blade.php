@props([
    'id',
    'name',
    'selected' => null,
    'media' => [],
    'placeholder' => 'Choose an image',
])

@php
    // Resolve the currently-selected item (for the preview) from whatever media the
    // page passed. The browse grid itself is lazy-loaded from admin.media.browse.
    $items = collect($media)->map(fn ($m) => is_array($m)
        ? $m
        : ['id' => $m->id, 'url' => $m->url, 'title' => $m->title ?: basename($m->path)]);
    $current = $items->firstWhere('id', (int) $selected);
@endphp

<div x-data="{
        open: false,
        id: @js((string) ($selected ?? '')),
        url: @js($current['url'] ?? ''),
        title: @js($current['title'] ?? ''),
        pages: {},
        page: 1,
        last: null,
        loading: false,
        loaded: false,
        endpoint: @js(route('admin.media.browse')),
        get items() { return this.pages[this.page] || []; },
        openModal() { this.open = true; if (! this.loaded) this.goto(1); },
        choose(m) { this.id = String(m.id); this.url = m.url; this.title = m.title; this.open = false; },
        clear() { this.id = ''; this.url = ''; this.title = ''; },
        async goto(p) {
            if (this.loading || p < 1 || (this.last !== null && p > this.last)) return;
            if (this.pages[p]) { this.page = p; return; }
            this.loading = true;
            try {
                const sep = this.endpoint.includes('?') ? '&' : '?';
                const r = await fetch(this.endpoint + sep + 'page=' + p, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (r.ok) {
                    const d = await r.json();
                    this.pages[p] = d.data || [];
                    this.last = d.last ?? (d.next === null ? p : null);
                    this.page = p;
                }
            } catch (e) {}
            this.loaded = true;
            this.loading = false;
        },
    }">
    <input type="hidden" name="{{ $name }}" :value="id">

    {{-- Preview + actions --}}
    <div class="flex items-center gap-3">
        <button type="button" @click="openModal()"
            class="cursor-pointer group relative w-20 h-20 shrink-0 rounded-xl border border-outline-variant bg-surface-container-low overflow-hidden grid place-items-center hover:border-primary transition-colors">
            <template x-if="url"><img :src="url" alt="" class="w-full h-full object-cover"></template>
            <template x-if="!url"><span class="material-symbols-outlined text-outline">add_photo_alternate</span></template>
            <span class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity grid place-items-center text-white text-[11px] font-semibold">Change</span>
        </button>

        <div class="min-w-0">
            <p class="text-sm text-on-surface truncate" x-text="title || @js($placeholder)"></p>
            <div class="flex items-center gap-3 mt-1">
                <button type="button" @click="openModal()" class="cursor-pointer text-xs font-semibold text-primary hover:underline">Browse library</button>
                <button type="button" x-show="id" x-cloak @click="clear()" class="cursor-pointer text-xs font-semibold text-on-surface-variant hover:text-error">Remove</button>
            </div>
        </div>
    </div>

    {{-- Picker modal --}}
    <div x-show="open" x-cloak @keydown.escape.window="open = false" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50"></div>
        <div class="relative min-h-full flex items-start justify-center p-4 sm:p-6" @click.self="open = false">
            <div class="w-full max-w-3xl my-4 sm:my-8 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-xl shadow-2xl">
                <div class="flex items-center justify-between p-5 border-b border-outline-variant/60">
                    <h3 class="text-lg font-bold text-on-surface">Choose an image</h3>
                    <button type="button" @click="open = false" title="Close" class="cursor-pointer p-1 -mr-1 text-on-surface-variant hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Paged grid: newest first, 10 per page (5 per row on desktop). --}}
                <div class="p-5 max-h-[60vh] overflow-y-auto">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4">
                        <template x-for="m in items" :key="m.id">
                            <button type="button" @click="choose(m)"
                                :class="id === String(m.id) ? 'ring-2 ring-primary border-primary' : 'border-outline-variant/50 hover:border-primary/50'"
                                class="cursor-pointer group text-left rounded-xl border overflow-hidden focus:outline-none transition-colors">
                                <div class="aspect-square bg-surface-container-low grid place-items-center overflow-hidden">
                                    <img :src="m.url" :alt="m.title" loading="lazy" class="w-full h-full object-cover">
                                </div>
                                <p class="px-2 py-1.5 text-[11px] text-on-surface-variant truncate" x-text="m.title"></p>
                            </button>
                        </template>
                    </div>

                    {{-- Loading spinner --}}
                    <div x-show="loading" class="flex justify-center py-6">
                        <span class="material-symbols-outlined animate-spin text-outline">progress_activity</span>
                    </div>

                    {{-- Empty state (only after the first load returns nothing) --}}
                    <div x-show="loaded && !loading && items.length === 0" class="p-12 text-center">
                        <span class="material-symbols-outlined text-outline" style="font-size:48px;">image</span>
                        <p class="mt-3 text-sm text-on-surface-variant">No media yet. Upload images in the
                            <a href="{{ route('admin.gallery.index') }}" class="text-primary font-semibold hover:underline">Gallery</a> first.</p>
                    </div>

                    {{-- Pagination --}}
                    <div x-show="loaded && last !== null && last > 1" class="flex items-center justify-center gap-1 pt-5">
                        <button type="button" @click="goto(page - 1)" :disabled="loading || page <= 1" aria-label="Previous page"
                            class="cursor-pointer w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high disabled:opacity-40 disabled:pointer-events-none transition-colors">
                            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                        </button>
                        <span class="px-3 text-sm font-semibold text-on-surface tabular-nums" x-text="page + ' / ' + (last ?? 1)"></span>
                        <button type="button" @click="goto(page + 1)" :disabled="loading || (last !== null && page >= last)" aria-label="Next page"
                            class="cursor-pointer w-9 h-9 grid place-items-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high disabled:opacity-40 disabled:pointer-events-none transition-colors">
                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 p-5 border-t border-outline-variant/60">
                    <button type="button" @click="clear(); open = false" class="cursor-pointer px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-error transition-colors">Use none</button>
                    <button type="button" @click="open = false" class="cursor-pointer px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 transition-all">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>
