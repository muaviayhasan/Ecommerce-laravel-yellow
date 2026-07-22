{{-- Shared yes/no confirmation dialog. Include once per page, then trigger from
     any element: $store.pageConfirm.ask(title, message, () => window.__postForm(url))
     Optional icon/tone via ask(title, message, fn) — styled for light + dark. --}}
<div x-data x-show="$store.pageConfirm.open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4"
    @keydown.escape.window="$store.pageConfirm.close()" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50" @click="$store.pageConfirm.close()" x-transition.opacity></div>
    <div class="relative w-full max-w-sm bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-2xl shadow-2xl p-6 text-center"
        x-show="$store.pageConfirm.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="w-14 h-14 mx-auto rounded-full bg-primary-container/30 grid place-items-center mb-4">
            <span class="material-symbols-outlined text-primary text-[28px]" x-text="$store.pageConfirm.icon"></span>
        </div>
        <h3 class="text-lg font-bold text-on-surface mb-1" x-text="$store.pageConfirm.title"></h3>
        <p class="text-sm text-on-surface-variant mb-6" x-text="$store.pageConfirm.message"></p>
        <div class="flex gap-3">
            <button type="button" @click="$store.pageConfirm.close()"
                class="flex-1 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors">No</button>
            <button type="button" @click="$store.pageConfirm.yes()"
                class="flex-1 py-2.5 bg-primary text-on-primary font-bold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Yes, continue</button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('pageConfirm', {
                    open: false, title: '', message: '', icon: 'help', _fn: null,
                    ask(title, message, fn, icon = 'content_copy') {
                        this.title = title; this.message = message; this._fn = fn; this.icon = icon; this.open = true;
                    },
                    close() { this.open = false; this._fn = null; },
                    yes() { const f = this._fn; this.close(); if (f) f(); },
                });
            });

            // Build + submit a real form so the action flows through the normal
            // redirect + flash cycle (and the audit middleware).
            window.__postForm = window.__postForm || function (url, fields = {}, method = 'POST') {
                const f = document.createElement('form');
                f.method = 'POST'; f.action = url; f.style.display = 'none';
                const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
                add('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                if (method !== 'POST') add('_method', method);
                Object.entries(fields).forEach(([k, v]) => Array.isArray(v) ? v.forEach(x => add(k + '[]', x)) : add(k, v));
                document.body.appendChild(f);
                f.submit();
            };
        </script>
    @endpush
@endonce
