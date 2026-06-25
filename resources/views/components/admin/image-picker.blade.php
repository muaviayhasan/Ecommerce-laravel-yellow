@props([
    'name' => 'images',
    'selected' => [],      // array of media ids already attached (in order)
    'media' => [],         // gallery items: collection/array of ['id','url','title']
])

@php
    $items = collect($media)->map(fn ($m) => is_array($m)
        ? $m
        : ['id' => $m->id, 'url' => $m->url, 'title' => $m->title ?: basename($m->path)])->values();
    $selectedIds = collect($selected)->map(fn ($id) => (string) $id)->values();
@endphp

<div x-data="{
        open: false,
        selected: @js($selectedIds),
        items: @js($items),
        has(id) { return this.selected.includes(String(id)); },
        toggle(id) {
            id = String(id);
            const i = this.selected.indexOf(id);
            if (i > -1) { this.selected.splice(i, 1); } else { this.selected.push(id); }
        },
        remove(id) { const i = this.selected.indexOf(String(id)); if (i > -1) this.selected.splice(i, 1); },
        chosen() { return this.selected.map(id => this.items.find(m => String(m.id) === id)).filter(Boolean); },
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
                <button type="button" @click="remove(m.id)" title="Remove"
                    class="absolute top-1 right-1 w-5 h-5 grid place-items-center rounded-full bg-error text-on-error opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-[14px]">close</span>
                </button>
            </div>
        </template>

        {{-- Add tile --}}
        <button type="button" @click="open = true"
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

                @if ($items->isEmpty())
                    <div class="p-12 text-center">
                        <span class="material-symbols-outlined text-outline" style="font-size:48px;">image</span>
                        <p class="mt-3 text-sm text-on-surface-variant">No media yet. Upload images in the
                            <a href="{{ route('admin.gallery.index') }}" class="text-primary font-semibold hover:underline">Gallery</a> first.</p>
                    </div>
                @else
                    <div class="p-5 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4 max-h-[60vh] overflow-y-auto">
                        @foreach ($items as $m)
                            <button type="button" @click="toggle(@js($m['id']))"
                                :class="has(@js($m['id'])) ? 'ring-2 ring-primary border-primary' : 'border-outline-variant/50 hover:border-primary/50'"
                                class="cursor-pointer group relative text-left rounded-xl border overflow-hidden focus:outline-none transition-colors">
                                <div class="aspect-square bg-surface-container-low grid place-items-center overflow-hidden">
                                    <img src="{{ $m['url'] }}" alt="{{ $m['title'] }}" loading="lazy" class="w-full h-full object-cover">
                                </div>
                                <span x-show="has(@js($m['id']))" class="absolute top-1.5 right-1.5 w-5 h-5 grid place-items-center rounded-full bg-primary text-on-primary">
                                    <span class="material-symbols-outlined text-[14px]">check</span>
                                </span>
                                <p class="px-2 py-1.5 text-[11px] text-on-surface-variant truncate">{{ $m['title'] }}</p>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-between items-center gap-3 p-5 border-t border-outline-variant/60">
                    <span class="text-xs text-on-surface-variant" x-text="selected.length + ' selected'"></span>
                    <button type="button" @click="open = false" class="cursor-pointer px-5 py-2.5 bg-primary text-on-primary font-semibold text-sm rounded-lg hover:brightness-110 transition-all">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>
