@props([
    'name' => 'images',
    'selected' => [],      // array of media ids already attached (in order)
    'media' => [],         // gallery items: used only to seed previews of already-attached images
])

@php
    $items = collect($media)->map(fn ($m) => is_array($m)
        ? $m
        : ['id' => $m->id, 'url' => $m->url, 'title' => $m->title ?: basename($m->path)])->values();
    $selectedIds = collect($selected)->map(fn ($id) => (string) $id)->values();

    // Seed a small preview catalog with just the already-attached items so their
    // thumbnails render instantly, before the live grid has loaded them.
    $selInts = $selectedIds->map(fn ($id) => (int) $id)->all();
    $seedCatalog = $items
        ->filter(fn ($m) => in_array((int) $m['id'], $selInts, true))
        ->mapWithKeys(fn ($m) => [(string) $m['id'] => ['id' => $m['id'], 'url' => $m['url'], 'title' => $m['title']]])
        ->all();
@endphp

<div x-data="{
        open: false,
        selected: @js($selectedIds),
        catalog: @js((object) $seedCatalog),   // id(string) -> {id,url,title}, for previews
        items: [],                             // live browse grid (lazy, newest first)
        nextPage: 1,
        loading: false,
        loaded: false,
        endpoint: @js(route('admin.media.browse')),
        openModal() {
            this.open = true;
            // Always refresh from the gallery so images uploaded in another tab
            // show up without reloading this page.
            this.items = [];
            this.nextPage = 1;
            this.loaded = false;
            this.loadMore();
        },
        has(id) { return this.selected.includes(String(id)); },
        toggle(m) {
            const id = String(m.id);
            this.catalog[id] = { id: m.id, url: m.url, title: m.title };
            const i = this.selected.indexOf(id);
            if (i > -1) { this.selected.splice(i, 1); } else { this.selected.push(id); }
        },
        remove(id) { const i = this.selected.indexOf(String(id)); if (i > -1) this.selected.splice(i, 1); },
        makePrimary(id) {
            id = String(id);
            const i = this.selected.indexOf(id);
            if (i > 0) { this.selected.splice(i, 1); this.selected.unshift(id); }
        },
        chosen() { return this.selected.map(id => this.catalog[String(id)]).filter(Boolean); },
        async loadMore() {
            if (this.loading || this.nextPage === null) return;
            this.loading = true;
            try {
                const sep = this.endpoint.includes('?') ? '&' : '?';
                const r = await fetch(this.endpoint + sep + 'page=' + this.nextPage, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (r.ok) {
                    const d = await r.json();
                    const seen = new Set(this.items.map(i => i.id));
                    (d.data || []).forEach(m => { if (! seen.has(m.id)) { this.items.push(m); this.catalog[String(m.id)] = m; } });
                    this.nextPage = d.next;
                }
            } catch (e) {}
            this.loaded = true;
            this.loading = false;
            // Top up if the grid can't scroll yet, so scroll-to-load stays reachable.
            this.$nextTick(() => {
                const g = this.$refs.grid;
                if (this.nextPage !== null && g && g.scrollHeight <= g.clientHeight + 4) this.loadMore();
            });
        },
        onScroll(e) {
            const el = e.target;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 120) this.loadMore();
        },
    }">
    {{-- Submitted values (ordered; the first is treated as primary on save) --}}
    <template x-for="id in selected" :key="id">
        <input type="hidden" name="{{ $name }}[]" :value="id">
    </template>

    {{-- Selected previews --}}
    <div class="flex flex-wrap justify-center gap-3">
        <template x-for="(m, idx) in chosen()" :key="m.id">
            <div class="relative w-24 h-24 rounded-xl border border-outline-variant overflow-hidden group bg-surface-container-low">
                <img :src="m.url" alt="" class="w-full h-full object-cover">
                <span x-show="idx === 0" class="absolute top-1 left-1 px-1.5 py-0.5 rounded bg-primary text-on-primary text-[9px] font-bold uppercase tracking-wide">Primary</span>
                <button type="button" x-show="idx > 0" @click="makePrimary(m.id)" title="Make primary"
                    class="absolute top-1 left-1 w-5 h-5 grid place-items-center rounded-full bg-primary text-on-primary opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-[14px]">star</span>
                </button>
                <button type="button" @click="remove(m.id)" title="Remove"
                    class="absolute top-1 right-1 w-5 h-5 grid place-items-center rounded-full bg-error text-on-error opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-[14px]">close</span>
                </button>
            </div>
        </template>

        {{-- Add tile --}}
        <button type="button" @click="openModal()"
            class="cursor-pointer w-24 h-24 rounded-xl border-2 border-dashed border-outline-variant hover:border-primary bg-surface-container-low flex flex-col items-center justify-center gap-1 text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined">add_photo_alternate</span>
            <span class="text-[10px] font-semibold">Add</span>
        </button>
    </div>
    <p class="mt-2 text-xs text-on-surface-variant text-center" x-text="selected.length + ' image(s) selected · first is primary'"></p>

    {{-- Picker modal --}}
    <div x-show="open" x-cloak @keydown.escape.window="open = false" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50"></div>
        <div class="relative min-h-full flex items-start justify-center p-4 sm:p-6" @click.self="open = false">
            <div class="w-full max-w-3xl my-4 sm:my-8 bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-xl shadow-2xl">
                <div class="flex items-center justify-between p-5 border-b border-outline-variant/60">
                    <h3 class="text-lg font-bold text-on-surface">Choose images</h3>
                    <button type="button" @click="open = false" title="Close" class="cursor-pointer p-1 -mr-1 text-on-surface-variant hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Lazy grid: newest first, 12 per batch, 12 more each time you scroll near the bottom. --}}
                <div x-ref="grid" @scroll.passive="onScroll($event)" class="p-5 max-h-[60vh] overflow-y-auto">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4">
                        <template x-for="m in items" :key="m.id">
                            <button type="button" @click="toggle(m)"
                                :class="has(m.id) ? 'ring-2 ring-primary border-primary' : 'border-outline-variant/50 hover:border-primary/50'"
                                class="cursor-pointer group relative text-left rounded-xl border overflow-hidden focus:outline-none transition-colors">
                                <div class="aspect-square bg-surface-container-low grid place-items-center overflow-hidden">
                                    <img :src="m.url" :alt="m.title" loading="lazy" class="w-full h-full object-cover">
                                </div>
                                <span x-show="has(m.id)" class="absolute top-1.5 right-1.5 w-5 h-5 grid place-items-center rounded-full bg-primary text-on-primary">
                                    <span class="material-symbols-outlined text-[14px]">check</span>
                                </span>
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

                    {{-- End-of-list marker --}}
                    <p x-show="loaded && items.length > 0 && nextPage === null" class="text-center text-xs text-outline pt-4">That’s all your images.</p>
                </div>

                <div class="flex justify-between items-center gap-3 p-5 border-t border-outline-variant/60">
                    <span class="text-xs text-on-surface-variant" x-text="selected.length + ' selected'"></span>
                    <button type="button" @click="open = false" class="cursor-pointer px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 transition-all">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>
