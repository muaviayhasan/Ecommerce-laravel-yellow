@props([
    'url',
    'title',
    'text' => null,
])

{{-- Floating share button — sits just above the support-chat bubble (which is
     bottom-20 md:bottom-5). Native OS share sheet where available; otherwise a
     drop-up menu of share targets. Mirrors the chat widget's compare-tray shift. --}}
<div x-data="{
        open: false,
        copied: false,
        url: @js($url),
        title: @js($title),
        text: @js($text ?? 'Check out ' . $title),
        toggle() {
            if (navigator.share) { navigator.share({ title: this.title, text: this.text, url: this.url }).catch(() => {}); return; }
            this.open = ! this.open;
        },
        copy() {
            const ok = () => { this.copied = true; setTimeout(() => this.copied = false, 1500); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(this.url).then(ok).catch(() => {}); return; }
            try { const t = document.createElement('textarea'); t.value = this.url; t.style.position = 'fixed'; t.style.opacity = '0'; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); ok(); } catch (e) {}
        },
    }"
    @keydown.escape.window="open = false"
    :class="($store.compareBar && $store.compareBar.visible) ? '!bottom-60 md:!bottom-[11.75rem]' : ''"
    class="fixed bottom-40 md:bottom-24 right-5 z-[60] print:hidden transition-all duration-200">

    {{-- Drop-up menu (fallback when the OS share sheet is unavailable) --}}
    <div x-show="open" x-cloak @click.outside="open = false" x-transition
        class="absolute bottom-full right-0 mb-3 w-60 bg-white border border-outline-variant rounded-xl shadow-xl p-2 text-on-surface">
        <p class="px-2 pt-1 pb-1.5 text-[11px] font-bold uppercase tracking-wide text-outline">Share this page</p>
        <a :href="'https://wa.me/?text=' + encodeURIComponent(text + ' ' + url)" target="_blank" rel="noopener" @click="open = false"
            class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
            <svg viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="#25D366"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413z"/></svg>
            <span class="text-sm">WhatsApp</span>
        </a>
        <a :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url)" target="_blank" rel="noopener" @click="open = false"
            class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
            <svg viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            <span class="text-sm">Facebook</span>
        </a>
        <a :href="'https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(url)" target="_blank" rel="noopener" @click="open = false"
            class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
            <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 mx-0.5" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            <span class="text-sm">X (Twitter)</span>
        </a>
        <a :href="'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(text + ' ' + url)" @click="open = false"
            class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[20px] text-on-surface-variant mx-0.5">mail</span>
            <span class="text-sm">Email</span>
        </a>
        <button type="button" @click="copy()"
            class="w-full flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[20px] mx-0.5" :class="copied ? 'text-primary' : 'text-on-surface-variant'" x-text="copied ? 'check_circle' : 'link'"></span>
            <span class="text-sm" x-text="copied ? 'Link copied!' : 'Copy link'"></span>
        </button>
    </div>

    {{-- The floating button — a bare share arrow (no background), centred over
         the chat-bubble column below it; w/h only give it a comfortable tap area. --}}
    <button type="button" @click="toggle()" title="Share" aria-label="Share this page"
        class="w-14 h-14 grid place-items-center text-on-surface hover:text-primary active:scale-90 transition-all">
        <span class="material-symbols-outlined text-[34px]" style="font-variation-settings: 'FILL' 1;">shortcut</span>
    </button>
</div>
