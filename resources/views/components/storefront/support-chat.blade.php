{{-- Floating support chat widget. Polls for new messages; guests enter a name first. --}}
@php
    // WhatsApp number for the deep-link button, digits only (wa.me needs no + or spaces).
    // Blank → the "Chat on WhatsApp" button stays hidden. Set it in Admin → Settings → Store.
    $waNumber = preg_replace('/\D+/', '', (string) setting('store', 'whatsapp'));
@endphp
<div x-data="supportChat({
        stateUrl: @js(route('support.state')),
        startUrl: @js(route('support.start')),
        sendUrl: @js(route('support.send')),
        historyUrl: @js(route('support.history')),
        authed: @js(auth()->check()),
        authName: @js(auth()->user()?->name),
        whatsapp: @js($waNumber),
    })"
    :class="($store.compareBar && $store.compareBar.visible) ? '!bottom-40 md:!bottom-28' : ''"
    class="fixed bottom-20 md:bottom-5 right-5 z-[60] flex flex-col items-end gap-3 print:hidden transition-all duration-200">

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition.origin.bottom.right
        @wheel="guardScroll($event)" @touchmove="guardTouch($event)"
        class="w-[22rem] max-w-[calc(100vw-2.5rem)] h-[30rem] max-h-[calc(100vh-7rem)] bg-white rounded-2xl shadow-2xl border border-outline-variant/60 flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="bg-primary-container text-on-primary-container px-5 py-4 flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-full bg-black/10 grid place-items-center shrink-0"><span class="material-symbols-outlined">support_agent</span></span>
                <div class="min-w-0">
                    <p class="font-semibold leading-tight">Support</p>
                    <p class="text-xs opacity-80 truncate">Typically replies in a few minutes</p>
                </div>
            </div>
            <button type="button" @click="open = false" class="w-8 h-8 grid place-items-center rounded-full hover:bg-black/10 transition shrink-0"><span class="material-symbols-outlined text-[20px]">close</span></button>
        </div>

        {{-- WhatsApp quick-connect strip: the single, always-visible hop to WhatsApp.
             Hidden until a number is set in Admin → Settings → Store. --}}
        <a x-show="whatsapp" x-cloak :href="waUrl()" target="_blank" rel="noopener"
            class="group flex items-center gap-3 px-5 py-3 bg-[#25D366] text-white shrink-0 hover:brightness-105 active:brightness-95 transition">
            <span class="w-8 h-8 rounded-full bg-white/20 grid place-items-center shrink-0">
                <svg viewBox="0 0 24 24" fill="currentColor" class="w-[18px] h-[18px]"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.945C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.512 5.26l-.999 3.648 3.736-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-sm font-semibold leading-tight">Chat on WhatsApp</span>
                <span class="block text-[11px] text-white/80 leading-tight">Get a faster reply on your phone</span>
            </span>
            <span class="material-symbols-outlined text-[20px] text-white/90 group-hover:translate-x-0.5 transition-transform shrink-0">chevron_right</span>
        </a>

        {{-- Guest name step --}}
        <template x-if="!authed && !started">
            <form @submit.prevent="startChat()" class="flex-1 flex flex-col justify-center gap-4 p-6 text-center">
                <span class="material-symbols-outlined text-primary mx-auto" style="font-size:44px;">chat</span>
                <div>
                    <p class="font-semibold text-on-surface">Hi there 👋</p>
                    <p class="text-sm text-on-surface-variant mt-1">Enter your name to start chatting with us.</p>
                </div>
                <input type="text" x-model="guestName" maxlength="80" placeholder="Your name"
                    class="w-full border border-outline-variant rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary-container focus:border-primary-container">
                <button type="submit" :disabled="!guestName.trim()" class="bg-primary-container text-on-primary-container rounded-lg px-4 py-2.5 text-sm font-semibold disabled:opacity-50 hover:brightness-110 transition">Start chat</button>
            </form>
        </template>

        {{-- Conversation --}}
        <template x-if="authed || started">
            <div class="flex-1 flex flex-col min-h-0">
                <div x-ref="scroll" @scroll.passive="onScroll($event)" class="flex-1 overflow-y-auto overscroll-contain p-4 space-y-3 bg-surface-container-low/40">
                    <div x-show="loadingMore" class="flex justify-center py-1">
                        <span class="material-symbols-outlined animate-spin text-outline text-[18px]">progress_activity</span>
                    </div>
                    <p x-show="!messages.length && !loadingMore" class="text-center text-sm text-on-surface-variant py-6">Send a message and we'll get right back to you.</p>
                    <template x-for="m in messages" :key="m.id">
                        <div :class="m.from_admin ? 'justify-start' : 'justify-end'" class="flex">
                            <div :class="m.from_admin ? 'bg-white text-on-surface rounded-tl-none' : 'bg-primary-container text-on-primary-container rounded-tr-none'"
                                class="max-w-[80%] rounded-2xl px-3.5 py-2 text-sm shadow-sm">
                                <p class="whitespace-pre-wrap break-words" x-html="linkify(m.body)"></p>
                                <p :class="m.from_admin ? 'text-outline' : 'text-on-primary-container/70'" class="text-[10px] mt-0.5 flex items-center justify-end gap-0.5">
                                    <span x-text="m.at"></span>
                                    <template x-if="!m.from_admin">
                                        <span class="material-symbols-outlined text-[14px] leading-none"
                                            :class="m.status === 'read' ? 'text-blue-700' : 'text-on-primary-container/50'"
                                            x-text="(m.status === 'sending' || m.status === 'sent') ? 'check' : 'done_all'"></span>
                                    </template>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="blocked" x-cloak class="p-4 border-t border-outline-variant/60 bg-error/5 text-center shrink-0">
                    <p class="text-xs text-error font-medium flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">block</span>
                        You can no longer send messages here.
                    </p>
                </div>
                <form x-show="!blocked" @submit.prevent="send()" class="p-3 border-t border-outline-variant/60 flex items-end gap-2 shrink-0 bg-white">
                    <div class="relative flex-1">
                        <textarea x-model="body" @keydown.enter.prevent="send()" rows="1" maxlength="1000" placeholder="Write a message…"
                            class="w-full resize-none border border-outline-variant rounded-lg px-3 py-2 pr-14 text-sm outline-none focus:ring-1 focus:ring-primary-container focus:border-primary-container max-h-24"></textarea>
                        <span class="absolute bottom-1.5 right-2 text-[10px] tabular-nums pointer-events-none select-none"
                            :class="body.length >= 1000 ? 'text-error' : (body.length >= 900 ? 'text-amber-500' : 'text-outline')"
                            x-text="body.length + '/1000'"></span>
                    </div>
                    <button type="submit" :disabled="!body.trim() || sending" class="w-10 h-10 grid place-items-center rounded-lg bg-primary-container text-on-primary-container disabled:opacity-50 hover:brightness-110 transition shrink-0"><span class="material-symbols-outlined text-[20px]">send</span></button>
                </form>
            </div>
        </template>
    </div>

    {{-- Toggle button --}}
    <button type="button" @click="toggle()"
        class="w-14 h-14 rounded-full bg-primary-container text-on-primary-container shadow-xl grid place-items-center hover:brightness-110 active:scale-95 transition relative">
        <span class="material-symbols-outlined" x-text="open ? 'close' : 'chat_bubble'"></span>
        <span x-show="!open && unread" x-cloak class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-error text-white text-[11px] font-bold grid place-items-center" x-text="unread"></span>
    </button>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('supportChat', (cfg) => ({
                    open: false,
                    authed: !!cfg.authed,
                    started: false,
                    guestName: cfg.authName || '',
                    whatsapp: cfg.whatsapp || '',
                    messages: [],
                    body: '',
                    unread: 0,
                    sending: false,
                    blocked: false,
                    hasMore: false,
                    loadingMore: false,
                    _poll: null,
                    _lastId: 0,
                    _minId: 0,
                    _primed: false,
                    _channel: null,
                    _bell: null,
                    token() { return document.querySelector('meta[name="csrf-token"]')?.content; },
                    // Deep-link to WhatsApp with a greeting that carries the visitor's name.
                    waUrl() {
                        const name = (this.guestName || '').trim();
                        const msg = (name ? `Hi, this is ${name}. ` : 'Hi! ') + "I'd like some help with my order.";
                        return 'https://wa.me/' + this.whatsapp + '?text=' + encodeURIComponent(msg);
                    },
                    // One reusable audio, unlocked on the first user gesture so later programmatic
                    // plays (from sockets/timers) aren't blocked by the browser autoplay policy.
                    mountBell() {
                        this._bell = new Audio('/assets/bells/user_notif.mp3');
                        this._bell.volume = 0.5;
                        const unlock = () => {
                            // Prime autoplay on the first gesture, but keep the priming play itself
                            // hard-muted: volume:0 alone can leak a brief click on some browsers
                            // (notably on Windows), which made the bell "ring" on every page load.
                            const b = this._bell, done = () => { b.pause(); b.currentTime = 0; b.muted = false; };
                            b.muted = true; b.play().then(done).catch(done);
                            window.removeEventListener('pointerdown', unlock, true);
                            window.removeEventListener('keydown', unlock, true);
                        };
                        window.addEventListener('pointerdown', unlock, true);
                        window.addEventListener('keydown', unlock, true);
                    },
                    ping() { if (!this._bell) return; try { this._bell.currentTime = 0; this._bell.play().catch(() => {}); } catch (e) {} },
                    subscribe(token) {
                        if (!window.Echo || !token || this._channel === token) return;
                        this._channel = token;
                        window.Echo.channel('support.conversation.' + token)
                            .listen('.message.sent', (m) => this.onEcho(m))
                            .listen('.receipt', (e) => this.onReceipt(e))
                            .listen('.blocked', (e) => { if (e) this.blocked = !!e.blocked; });
                    },
                    // Staff delivered/read our messages → advance our own ticks (double, then blue).
                    onReceipt(e) {
                        if (!e || e.by !== 'admin') return;
                        this.messages.forEach(m => {
                            if (m.from_admin) return;
                            if (e.type === 'read') m.status = 'read';
                            else if (e.type === 'delivered' && m.status !== 'read') m.status = 'delivered';
                        });
                    },
                    onEcho(m) {
                        if (!m || this.messages.some(x => x.id === m.id)) return;
                        this.messages.push(m);
                        this._lastId = Math.max(this._lastId, m.id);
                        if (m.from_admin) {
                            if (!this.open) { this.ping(); this.unread++; }
                            // Tell the server we received it → staff sees the double (or blue) tick.
                            this.refresh();
                        }
                        if (this.open) this.$nextTick(() => this.scrollDown());
                    },
                    // A staff reply is only "read" while the panel is open AND this window is focused.
                    viewing() { return this.open && document.hasFocus(); },
                    init() {
                        this.mountBell();
                        // When the visitor returns to this tab with the chat open, mark replies read.
                        this._onFocus = () => { if (this.open) this.refresh(); };
                        window.addEventListener('focus', this._onFocus);
                        this.refresh().then(() => { if (this.started) this.startPolling(); });
                    },
                    toggle() {
                        this.open = !this.open;
                        if (this.open) { this.unread = 0; this.refresh(); this.startPolling(); this.$nextTick(() => this.scrollDown()); }
                    },
                    // Echo is realtime; this slow poll is a self-healing fallback if the socket drops.
                    startPolling() { if (this._poll) return; this._poll = setInterval(() => this.refresh(), 15000); },
                    async refresh() {
                        try {
                            const url = cfg.stateUrl + (cfg.stateUrl.includes('?') ? '&' : '?') + 'open=' + (this.viewing() ? 1 : 0) + '&path=' + encodeURIComponent(location.pathname);
                            const r = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (!r.ok) return;
                            const d = await r.json();
                            if (d.token) this.subscribe(d.token);
                            const msgs = Array.isArray(d.messages) ? d.messages : [];
                            this.started = d.started;
                            this.authed = d.authenticated;
                            this.blocked = !!d.blocked;
                            if (this._primed) {
                                if (msgs.some(x => x.id > this._lastId && x.from_admin) && !this.open) this.ping();
                                this.applyServerRecent(msgs);
                            } else {
                                this.seed(msgs, d.has_more);
                                this._primed = true;
                            }
                            // Server is authoritative for the badge (unread staff replies); zero while open.
                            this.unread = this.open ? 0 : (d.unread || 0);
                            if (this.open) this.$nextTick(() => this.scrollDown());
                        } catch (e) {}
                    },
                    // Initial fill: newest page, oldest-first.
                    seed(list, hasMore) {
                        this.messages = Array.isArray(list) ? list.slice() : [];
                        this._lastId = this.messages.reduce((mx, m) => Math.max(mx, m.id), 0);
                        this._minId = this.messages.reduce((mn, m) => (m.id > 0 && (mn === 0 || m.id < mn)) ? m.id : mn, 0);
                        this.hasMore = !!hasMore;
                    },
                    // Merge the latest page: update statuses in place, append new messages.
                    applyServerRecent(list) {
                        if (!Array.isArray(list)) return false;
                        const byId = new Map(this.messages.map(m => [m.id, m]));
                        let appended = false;
                        list.forEach(sm => {
                            const ex = byId.get(sm.id);
                            if (ex) { ex.status = sm.status; ex.at = sm.at; }
                            else if (sm.id > this._lastId) { this.messages.push(sm); byId.set(sm.id, sm); appended = true; }
                        });
                        list.forEach(sm => { if (sm.id > this._lastId) this._lastId = sm.id; });
                        if (!this._minId) this._minId = this.messages.reduce((mn, m) => (m.id > 0 && (mn === 0 || m.id < mn)) ? m.id : mn, 0);
                        return appended;
                    },
                    onScroll(e) {
                        if (e.target.scrollTop < 48 && this.hasMore && !this.loadingMore) this.loadMore();
                    },
                    async loadMore() {
                        if (this.loadingMore || !this.hasMore || this._minId <= 0) return;
                        this.loadingMore = true;
                        const el = this.$refs.scroll;
                        const prev = el ? el.scrollHeight : 0;
                        try {
                            const r = await fetch(cfg.historyUrl + '?before=' + this._minId, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (r.ok) {
                                const d = await r.json();
                                const older = Array.isArray(d.messages) ? d.messages : [];
                                if (older.length) {
                                    this.messages = [...older, ...this.messages];
                                    this._minId = older[0].id;
                                    this.hasMore = !!d.has_more;
                                    this.$nextTick(() => { if (el) el.scrollTop = el.scrollHeight - prev; });
                                } else { this.hasMore = false; }
                            }
                        } catch (e) {}
                        this.loadingMore = false;
                    },
                    async startChat() {
                        if (!this.authed && !this.guestName.trim()) return;
                        await this.post(cfg.startUrl, { name: this.guestName });
                        this.started = true;
                        this.startPolling();
                        this.$nextTick(() => this.scrollDown());
                    },
                    async send() {
                        const body = this.body.trim();
                        if (!body || this.sending || this.blocked) return;
                        this.sending = true;
                        // Optimistic bubble with a single "sending" tick; the server response replaces it.
                        const tempId = -Date.now();
                        this.messages.push({ id: tempId, body, from_admin: false, at: '', status: 'sending' });
                        this.body = '';
                        this.$nextTick(() => this.scrollDown());
                        await this.post(cfg.sendUrl, { body, name: this.guestName });
                        this.messages = this.messages.filter(m => m.id !== tempId);   // real one merged on success; dropped on block/fail
                        this.started = true;
                        this.sending = false;
                        this.startPolling();
                        this.$nextTick(() => this.scrollDown());
                    },
                    async post(url, data) {
                        try {
                            const r = await fetch(url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.token() },
                                credentials: 'same-origin',
                                body: JSON.stringify(data),
                            });
                            const d = await r.json().catch(() => ({}));
                            if (d.token) this.subscribe(d.token);
                            if (typeof d.blocked === 'boolean') this.blocked = d.blocked;
                            this.applyServerRecent(d.messages || []);
                            return r.ok;
                        } catch (e) { return false; }
                    },
                    scrollDown() { const el = this.$refs.scroll; if (el) el.scrollTop = el.scrollHeight; },
                    // Keep the page still while the pointer is over the panel: scroll the messages,
                    // never the site behind. Blocks page scroll on non-scrollable areas (header/input)
                    // and when the list is already at its top/bottom edge.
                    guardScroll(e) {
                        const s = this.$refs.scroll;
                        if (!s || !s.contains(e.target)) { e.preventDefault(); return; }
                        const up = e.deltaY < 0;
                        const atTop = s.scrollTop <= 0;
                        const atBottom = Math.ceil(s.scrollTop + s.clientHeight) >= s.scrollHeight;
                        if ((up && atTop) || (!up && atBottom)) e.preventDefault();
                    },
                    // Touch equivalent: block the background from scrolling when dragging over a
                    // non-scrollable part of the panel. Inside the list, overscroll-contain handles the edges.
                    guardTouch(e) {
                        const s = this.$refs.scroll;
                        if (!s || !s.contains(e.target)) { if (e.cancelable) e.preventDefault(); }
                    },
                    // Escape the body, then turn bare URLs into clickable links (e.g. the checkout / order link).
                    linkify(text) {
                        const esc = (text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        return esc.replace(/(https?:\/\/[^\s<]+)/g, (u) => {
                            const m = u.match(/^(.*?)([.,;:!?)]*)$/);
                            return `<a href="${m[1]}" class="underline font-semibold hover:opacity-80">${m[1]}</a>${m[2] || ''}`;
                        });
                    },
                }));
            });
        </script>
    @endpush
@endonce
